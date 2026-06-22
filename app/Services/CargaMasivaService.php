<?php

namespace App\Services;

use App\Models\DocumentoExpediente;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Jobs\ExtractPdfDataJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Procesa una carga masiva de archivos para un expediente.
 *
 * Flujo:
 *   1. Recibe array de UploadedFile con sus rutas relativas
 *   2. Detecta la categoría por el nombre de carpeta (ACREDITADA, VENDEDOR, etc.)
 *   3. Guarda cada archivo en storage/app/private/expedientes/{id}/docs/
 *   4. Crea o actualiza el registro en documentos_expediente
 *   5. Intenta extraer datos del PDF y los acumula para pre-rellenar el expediente
 */
class CargaMasivaService
{
    public function __construct(
        private PdfExtractorService $extractor
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Crear expediente nuevo desde carpeta de archivos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Crea un expediente nuevo extrayendo datos de los PDFs,
     * luego sube todos los archivos vinculados a él.
     *
     * @param  array<array{file: UploadedFile, ruta_relativa: string}>  $archivos
     * @param  array  $datosBase  Campos que el usuario capturó en el formulario (asesor_id, tipo_tramite_id, etc.)
     * @return array{expediente: Expediente, documentos_creados: int, datos_extraidos: array}
     */
    public function crearExpedienteDesdeArchivos(array $archivos, array $datosBase): array
    {
        // ── 1. Detectar nombre del acreditado desde la carpeta raíz ──────────────
        $nombreCarpetaRaiz = $this->detectarNombreCarpetaRaiz($archivos);

        // ── 2. Construir datos del expediente con lo que se puede detectar sin OCR ──
        $extraidos = []; // OCR corre en background

        $datosExpediente = array_merge([
            'acreditado_nombre'           => $extraidos['acreditado_nombre'] ?? $extraidos['nombre'] ?? $nombreCarpetaRaiz,
            'acreditado_curp'             => $extraidos['curp']              ?? null,
            'acreditado_rfc'              => $extraidos['rfc']               ?? null,
            'acreditado_fecha_nacimiento' => $extraidos['fecha_nacimiento']  ?? null,
            'vivienda_calle'              => $extraidos['vivienda_calle']    ?? null,
            'vivienda_numero'             => $extraidos['vivienda_numero']   ?? null,
            'vivienda_colonia'            => $extraidos['vivienda_colonia']  ?? null,
            'vivienda_cp'                 => $extraidos['vivienda_cp']       ?? null,
            'vivienda_municipio'          => $extraidos['vivienda_municipio'] ?? null,
            'vivienda_estado'             => $extraidos['vivienda_estado']   ?? null,
            'vendedor_nombre'             => $extraidos['vendedor_nombre']   ?? null,
            'cuv'                         => $extraidos['cuv']               ?? null,
            'estado'                      => 'en_proceso',
            'fecha_apertura'              => now()->toDateString(),
            'asesor_id'                   => Auth::id(),
        ], array_filter($datosBase));

        // ── 4. Resolver tipo de trámite y primera etapa ───────────────────────────
        $tipoTramiteId = $datosExpediente['tipo_tramite_id'] ?? null;

        if (! $tipoTramiteId) {
            $tipoTramiteId = $this->detectarTipoTramite($extraidos);
            $datosExpediente['tipo_tramite_id'] = $tipoTramiteId;
        }

        if ($tipoTramiteId) {
            $primeraEtapa = EtapaTramite::where('tipo_tramite_id', $tipoTramiteId)
                ->orderBy('orden')
                ->first();
            $datosExpediente['etapa_tramite_id'] = $primeraEtapa?->id;
        }

        // ── 5. Crear el expediente ────────────────────────────────────────────────
        $expediente = Expediente::create(array_filter($datosExpediente, fn ($v) => ! is_null($v)));

        // ── 6. Subir todos los archivos vinculados al expediente recién creado ────
        $resultado = $this->procesar($expediente, $archivos);

        // ── 7. Despachar OCR en background (no bloquea la request) ───────────────
        ExtractPdfDataJob::dispatch($expediente->id);

        return [
            'expediente'         => $expediente,
            'documentos_creados' => $resultado['documentos_creados'],
            'datos_extraidos'    => [], // el OCR corre en background
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Subir archivos a un expediente existente
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  array<array{file: UploadedFile, ruta_relativa: string}>  $archivos
     */
    public function procesar(Expediente $expediente, array $archivos): array
    {
        $creados        = 0;
        $actualizados   = 0;
        $datosExtraidos = [];

        // Extensiones válidas — ignorar archivos del sistema y sin extensión reconocida
        $extensionesValidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic', 'gif', 'doc', 'docx', 'xls', 'xlsx'];

        foreach ($archivos as $item) {
            /** @var UploadedFile $file */
            $file         = $item['file'];
            $nombreArchivo = $file->getClientOriginalName();

            // Ignorar archivos del sistema macOS/Windows y archivos ocultos
            if (str_starts_with($nombreArchivo, '.') || str_starts_with($nombreArchivo, '_')) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());

            // Ignorar archivos sin extensión válida
            if (! in_array($extension, $extensionesValidas)) {
                continue;
            }

            $rutaRelativa = $item['ruta_relativa'] ?? $nombreArchivo;

            $partes    = array_values(array_filter(explode('/', str_replace('\\', '/', $rutaRelativa))));
            $categoria = $this->detectarCategoria($partes);
            $seccion   = $this->categoriaASeccion($categoria);

            $nombreSinExt   = pathinfo($nombreArchivo, PATHINFO_FILENAME);

            // Ignorar si el nombre queda vacío
            if (empty(trim($nombreSinExt))) {
                continue;
            }

            $dir            = "expedientes/{$expediente->id}/docs/{$categoria}";
            $nombreGuardado = Str::slug($nombreSinExt) . '_' . time() . '.' . $extension;
            $rutaArchivo    = Storage::disk('local')->putFileAs($dir, $file, $nombreGuardado);

            $tipo = Str::upper($nombreSinExt);

            $existente = DocumentoExpediente::where('expediente_id', $expediente->id)
                ->where('tipo', $tipo)
                ->where('seccion', $seccion)
                ->where('categoria', $categoria)
                ->first();

            if ($existente) {
                if ($existente->ruta_archivo && Storage::disk('local')->exists($existente->ruta_archivo)) {
                    Storage::disk('local')->delete($existente->ruta_archivo);
                }
                $existente->update(['ruta_archivo' => $rutaArchivo, 'estado' => 'recibido']);
                $actualizados++;
            } else {
                DocumentoExpediente::create([
                    'expediente_id' => $expediente->id,
                    'tipo'          => $tipo,
                    'nombre'        => $this->nombreLegible($nombreSinExt),
                    'seccion'       => $seccion,
                    'categoria'     => $categoria,
                    'estado'        => 'recibido',
                    'ruta_archivo'  => $rutaArchivo,
                ]);
                $creados++;
            }

            if (strtolower($extension) === 'pdf') {
                // OCR se procesa en background via ExtractPdfDataJob
                // No hacer OCR síncrono aquí para evitar timeout
            }
        }

        return [
            'documentos_creados'      => $creados,
            'documentos_actualizados' => $actualizados,
            'datos_extraidos'         => $datosExtraidos,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function extraerDatosDeArchivos(array $archivos): array
    {
        $datos = [];

        foreach ($archivos as $item) {
            /** @var UploadedFile $file */
            $file         = $item['file'];
            $nombreArchivo = $file->getClientOriginalName();

            // Ignorar archivos del sistema
            if (str_starts_with($nombreArchivo, '.') || str_starts_with($nombreArchivo, '_')) {
                continue;
            }

            if (strtolower($file->getClientOriginalExtension()) !== 'pdf') {
                continue;
            }

            $rutaAbsoluta = $file->getRealPath();
            if (! $rutaAbsoluta || ! file_exists($rutaAbsoluta)) {
                continue;
            }

            $resultado = $this->extractor->extraer($rutaAbsoluta, $nombreArchivo);

            if (! empty($resultado['datos'])) {
                $datos = array_merge($datos, $resultado['datos']);
                $datos['_fuentes'][$resultado['tipo']][] = $nombreArchivo;
            }
        }

        return $datos;
    }

    private function detectarNombreCarpetaRaiz(array $archivos): ?string
    {
        foreach ($archivos as $item) {
            $ruta   = $item['ruta_relativa'] ?? '';
            $partes = array_values(array_filter(explode('/', str_replace('\\', '/', $ruta))));

            if (count($partes) >= 2) {
                $raiz      = $partes[0];
                $raizUpper = strtoupper(trim($raiz));

                $carpetasConocidas = ['ACREDITAD', 'VENDEDOR', 'VIVIENDA', 'SOFOM', 'NOTARI', 'AVALUO', 'CATASTRO'];
                $esCarpetaConocida = false;
                foreach ($carpetasConocidas as $conocida) {
                    if (str_starts_with($raizUpper, $conocida)) {
                        $esCarpetaConocida = true;
                        break;
                    }
                }

                if (! $esCarpetaConocida && strlen($raiz) > 3) {
                    return ucwords(strtolower(trim($raiz)));
                }
            }
        }

        return null;
    }

    private function detectarTipoTramite(array $extraidos): ?int
    {
        if (isset($extraidos['cuv'])) {
            $tipo = TipoTramite::where('nombre', 'like', '%Tradicional%FOVISSSTE%')
                ->orWhere('nombre', 'like', '%FOVISSSTE%Tradicional%')
                ->first();
            return $tipo?->id;
        }

        return null;
    }

    private function detectarCategoria(array $partes): string
    {
        // Excluir el nombre del archivo (último elemento) y la carpeta raíz del acreditado (primer elemento)
        // Ej: ["ANGELICA CRUZ CORTEZ", "SOFOM", "ACREDITADA", "CURP.pdf"]
        //   → carpetas intermedias: ["SOFOM", "ACREDITADA"]
        //   → categoria: "sofom/acreditada"
        //
        // Ej: ["ANGELICA CRUZ CORTEZ", "ACREDITADA ", "CURP.pdf"]
        //   → carpetas intermedias: ["ACREDITADA "]
        //   → categoria: "acreditada"

        $carpetasConocidas = [
            'ACREDITAD' => 'acreditada',
            'VENDEDOR'  => 'vendedor',
            'VIVIENDA'  => 'vivienda',
            'SOFOM'     => 'sofom',
            'NOTARIA'   => 'notaria',
            'NOTARÍA'   => 'notaria',
            'AVALUO'    => 'avaluo',
            'AVALÚO'    => 'avaluo',
            'CATASTRO'  => 'catastro',
            'PAGOS'     => 'pagos',
        ];

        // Segmentos de carpeta (sin el archivo final)
        $segmentosCarpeta = array_slice($partes, 0, count($partes) - 1);

        // Ignorar el primer segmento si es el nombre del acreditado (no es carpeta conocida)
        if (count($segmentosCarpeta) >= 2) {
            $primerUpper = strtoupper(trim($segmentosCarpeta[0]));
            $esCarpetaConocida = false;
            foreach ($carpetasConocidas as $clave => $valor) {
                if (str_starts_with($primerUpper, $clave)) {
                    $esCarpetaConocida = true;
                    break;
                }
            }
            if (! $esCarpetaConocida) {
                $segmentosCarpeta = array_slice($segmentosCarpeta, 1);
            }
        }

        // Construir la categoría uniendo todos los segmentos conocidos con "/"
        $partesCat = [];
        foreach ($segmentosCarpeta as $segmento) {
            $upper = strtoupper(trim($segmento));
            foreach ($carpetasConocidas as $clave => $valor) {
                if (str_starts_with($upper, $clave)) {
                    $partesCat[] = $valor;
                    break;
                }
            }
        }

        return count($partesCat) > 0
            ? implode('/', $partesCat)
            : 'general';
    }

    private function categoriaASeccion(string $categoria): string
    {
        // Tomar el último segmento para determinar la sección
        // "sofom/acreditada" → "acreditado"
        // "sofom/vendedor"   → "vendedor"
        // "sofom/vivienda"   → "vivienda"
        $ultimo = explode('/', $categoria);
        $ultimo = end($ultimo);

        return match ($ultimo) {
            'acreditada'                     => 'acreditado',
            'vendedor'                       => 'vendedor',
            'vivienda', 'catastro', 'avaluo' => 'vivienda',
            'sofom'                          => 'tramite',
            default                          => 'tramite',
        };
    }

    private function nombreLegible(string $nombre): string
    {
        return ucwords(strtolower(str_replace(['_', '-'], ' ', $nombre)));
    }
}
