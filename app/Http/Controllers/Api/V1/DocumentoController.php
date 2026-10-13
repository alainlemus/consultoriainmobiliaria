<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractPdfDataJob;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use App\Notifications\DocumentoSubido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class DocumentoController extends Controller
{
    private const DISK = 'local';

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeAccess(Request $request, Expediente $exp): void
    {
        $user = $request->user();
        if (! $user->hasRole('super_admin') && $exp->asesor_id !== $user->id) {
            abort(403);
        }
    }

    private function serialize(DocumentoExpediente $doc): array
    {
        $arr = $doc->toArray();
        // Nunca devolvemos la ruta real ni una URL pública directa.
        // La app obtiene acceso mediante el endpoint /ver (URL firmada).
        return [
            ...$arr,
            'tipo_documento' => $arr['tipo'],
            'url'            => null,   // se obtiene via /ver
            'tiene_archivo'  => (bool) $doc->ruta_archivo,
        ];
    }

    /**
     * Guarda el archivo del request en el disco privado (convirtiendo HEIC a JPEG)
     * y devuelve la ruta relativa.
     */
    private function guardarArchivo(Request $request, int $expedienteId): string
    {
        $archivo = $request->file('archivo');

        // Detectar si el archivo es HEIC aunque venga con extensión .jpeg (iPhone)
        $mimeReal = mime_content_type($archivo->getRealPath()) ?: '';
        $heicMimes = ['image/heic', 'image/heif', 'image/x-heic', 'image/x-heif'];
        $esHeic    = in_array(strtolower($mimeReal), $heicMimes);

        if ($esHeic && class_exists(\Imagick::class)) {
            // Convertir HEIC → JPEG con Imagick
            try {
                $im = new \Imagick($archivo->getRealPath());
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality(90);
                $jpegContent = $im->getImageBlob();
                $im->clear();

                $ext    = 'jpg';
                $nombre = Str::slug($request->tipo_documento) . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(5)) . '.' . $ext;
                $ruta   = "expedientes/{$expedienteId}/docs/{$nombre}";

                Storage::disk(self::DISK)->put($ruta, $jpegContent);
            } catch (\Throwable) {
                // Si falla la conversión, guardar el original
                $ext    = $archivo->getClientOriginalExtension() ?: 'heic';
                $nombre = Str::slug($request->tipo_documento) . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(5)) . '.' . $ext;
                $ruta   = "expedientes/{$expedienteId}/docs/{$nombre}";
                Storage::disk(self::DISK)->putFileAs("expedientes/{$expedienteId}/docs", $archivo, $nombre);
            }
        } else {
            $ext    = $archivo->getClientOriginalExtension();
            $nombre = Str::slug($request->tipo_documento) . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(5)) . '.' . $ext;
            $ruta   = "expedientes/{$expedienteId}/docs/{$nombre}";
            Storage::disk(self::DISK)->putFileAs("expedientes/{$expedienteId}/docs", $archivo, $nombre);
        }

        return $ruta;
    }

    // ── Endpoints ─────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/expedientes/{expedienteId}/documentos
     */
    public function index(Request $request, int $expedienteId): JsonResponse
    {
        $exp = Expediente::findOrFail($expedienteId);
        $this->authorizeAccess($request, $exp);

        $docs = DocumentoExpediente::where('expediente_id', $expedienteId)
            ->get()
            ->map(fn ($d) => $this->serialize($d));

        return response()->json(['data' => $docs]);
    }

    /**
     * POST /api/v1/expedientes/{expedienteId}/documentos
     */
    public function store(Request $request, int $expedienteId): JsonResponse
    {
        $exp = Expediente::findOrFail($expedienteId);
        $this->authorizeAccess($request, $exp);

        $request->validate([
            'archivo'        => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,heic,webp'],
            'tipo_documento' => ['required', 'string', 'max:150'],
            'seccion'        => ['nullable', 'string', 'in:acreditado,vendedor,vivienda,otros'],
            'notas'          => ['nullable', 'string'],
        ]);

        $ruta = $this->guardarArchivo($request, $expedienteId);

        $seccion = $request->input('seccion');

        // Sin sección (app actual): cada subida es un documento nuevo; si ya hay uno del mismo
        // tipo se guarda como "INE (2)", "INE (3)"… y la nota los distingue (ej. "INE del dueño").
        // Con sección (checklist del panel y apps anteriores): reemplaza el de ese tipo y sección.
        if (! $seccion) {
            $seccion = 'acreditado';
            $tipo    = $request->tipo_documento;
            for ($n = 2; DocumentoExpediente::where('expediente_id', $expedienteId)->where('tipo', $tipo)->where('seccion', $seccion)->exists(); $n++) {
                $tipo = "{$request->tipo_documento} ({$n})";
            }

            $doc = DocumentoExpediente::create([
                'expediente_id' => $expedienteId,
                'tipo'          => $tipo,
                'nombre'        => $tipo,
                'seccion'       => $seccion,
                'estado'        => 'recibido',
                'notas'         => $request->input('notas'),
                'ruta_archivo'  => $ruta,
            ]);
        } else {
            // Si ya existe un doc de este tipo+sección, borrar el archivo anterior
            $existente = DocumentoExpediente::where('expediente_id', $expedienteId)
                ->where('tipo', $request->tipo_documento)
                ->where('seccion', $seccion)
                ->first();
            if ($existente?->ruta_archivo) {
                Storage::disk(self::DISK)->delete($existente->ruta_archivo);
            }

            $doc = DocumentoExpediente::updateOrCreate(
                [
                    'expediente_id' => $expedienteId,
                    'tipo'          => $request->tipo_documento,
                    'seccion'       => $seccion,
                ],
                [
                    // Usamos el nombre descriptivo del documento, no el nombre técnico del archivo
                    'nombre'       => $request->tipo_documento,
                    'estado'       => 'pendiente',
                    'notas'        => $request->input('notas'),
                    'ruta_archivo' => $ruta,
                ]
            );
        }

        $this->notificarDocumentoSubido($doc, $exp);

        // Lee los PDFs para prellenar los datos del expediente. Con varias subidas
        // seguidas basta una corrida: el candado evita encolar una por documento.
        if (Cache::add("ocr-expediente-{$exp->id}", true, now()->addMinutes(2))) {
            ExtractPdfDataJob::dispatch($exp->id)->delay(now()->addMinutes(2));
        }

        return response()->json(['data' => $this->serialize($doc)], 201);
    }

    /**
     * Notifica a todos los super_admin que se subió un documento.
     */
    private function notificarDocumentoSubido(DocumentoExpediente $doc, Expediente $exp): void
    {
        $admins = User::role('super_admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new DocumentoSubido($doc, $exp));
        }
    }

    /**
     * GET /api/v1/expedientes/{expedienteId}/documentos/{documentoId}/ver
     *
     * Devuelve una URL firmada de corta duración (5 min) para que la app
     * pueda abrir el archivo en el browser sin exponer la ruta real.
     * Solo el asesor dueño o super_admin pueden obtenerla.
     */
    public function ver(Request $request, int $expedienteId, int $documentoId): JsonResponse
    {
        $exp = Expediente::findOrFail($expedienteId);
        $this->authorizeAccess($request, $exp);

        $doc = DocumentoExpediente::where('expediente_id', $expedienteId)
            ->findOrFail($documentoId);

        if (! $doc->ruta_archivo) {
            abort(404, 'Este documento no tiene archivo adjunto.');
        }

        // URL firmada apuntando al endpoint de descarga, válida 5 minutos
        $url = URL::temporarySignedRoute(
            'api.documentos.descargar',
            now()->addMinutes(5),
            ['expedienteId' => $expedienteId, 'documentoId' => $documentoId]
        );

        return response()->json(['url' => $url, 'expira_en' => 300]);
    }

    /**
     * GET /documentos/{expedienteId}/{documentoId}/descargar  (ruta con nombre firmada)
     *
     * Sirve el archivo binario. No está bajo /api/v1 ni requiere token Bearer
     * porque la firma de la URL es suficiente garantía.
     */
    public function descargar(Request $request, int $expedienteId, int $documentoId)
    {
        // Laravel ya valida la firma; si es inválida o expirada devuelve 403 automáticamente.

        $doc = DocumentoExpediente::where('expediente_id', $expedienteId)
            ->findOrFail($documentoId);

        if (! $doc->ruta_archivo || ! Storage::disk(self::DISK)->exists($doc->ruta_archivo)) {
            abort(404, 'Archivo no encontrado.');
        }

        $path   = Storage::disk(self::DISK)->path($doc->ruta_archivo);

        // Nombre de descarga: derivado del archivo real
        $nombreDescarga = basename($doc->ruta_archivo);

        // Detectar MIME real del contenido (no solo por extensión)
        // Los iPhones suben archivos HEIC con extensión .jpeg — hay que detectar el real
        $ext     = strtolower(pathinfo($doc->ruta_archivo, PATHINFO_EXTENSION));
        $mimeMap = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
        ];

        // Usar mime_content_type() para detectar el tipo real del archivo
        $mimeReal = mime_content_type($path) ?: null;

        // Si el archivo es HEIC/HEIF con extensión .jpeg, corregir el MIME y el nombre
        $heicMimes = ['image/heic', 'image/heif', 'image/x-heic', 'image/x-heif'];
        if ($mimeReal && in_array(strtolower($mimeReal), $heicMimes)) {
            $mimeType = 'image/heic';
            // Corregir el nombre para que el navegador lo descargue con extensión correcta
            $nombreDescarga = pathinfo($nombreDescarga, PATHINFO_FILENAME) . '.heic';
        } else {
            $mimeType = $mimeMap[$ext] ?? ($mimeReal ?: 'application/octet-stream');
        }

        return response()->file($path, [
            'Content-Type'           => $mimeType,
            'Content-Disposition'    => 'inline; filename="' . $nombreDescarga . '"',
            'Cache-Control'          => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * DELETE /api/v1/expedientes/{expedienteId}/documentos/{documentoId}
     */
    public function destroy(Request $request, int $expedienteId, int $documentoId): JsonResponse
    {
        $user = $request->user();
        $exp  = Expediente::findOrFail($expedienteId);
        $this->authorizeAccess($request, $exp);

        $doc = DocumentoExpediente::where('expediente_id', $expedienteId)
            ->findOrFail($documentoId);

        // Asesor solo puede borrar si el doc sigue pendiente; super_admin sin restricción
        if (! $user->hasRole('super_admin') && $doc->estado !== 'pendiente') {
            abort(422, 'Solo puedes eliminar documentos en estado pendiente.');
        }

        if ($doc->ruta_archivo) {
            Storage::disk(self::DISK)->delete($doc->ruta_archivo);
        }

        $doc->delete();

        return response()->json(['message' => 'Documento eliminado.']);
    }

    /**
     * POST /api/v1/expedientes/{expedienteId}/documentos/{documentoId}/reemplazar
     */
    public function reemplazar(Request $request, int $expedienteId, int $documentoId): JsonResponse
    {
        $exp = Expediente::findOrFail($expedienteId);
        $this->authorizeAccess($request, $exp);

        $doc = DocumentoExpediente::where('expediente_id', $expedienteId)
            ->findOrFail($documentoId);

        $request->validate([
            'archivo' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,heic,webp'],
        ]);

        if ($doc->ruta_archivo) {
            Storage::disk(self::DISK)->delete($doc->ruta_archivo);
        }

        $archivo = $request->file('archivo');
        $ext     = $archivo->getClientOriginalExtension();
        $nombre  = Str::slug($doc->tipo) . '_' . now()->format('YmdHis') . '.' . $ext;
        $ruta    = "expedientes/{$expedienteId}/docs/{$nombre}";

        Storage::disk(self::DISK)->putFileAs(
            "expedientes/{$expedienteId}/docs",
            $archivo,
            $nombre
        );

        $doc->update([
            // No se sobreescribe 'nombre': conserva el nombre descriptivo asignado al subir
            'ruta_archivo' => $ruta,
            'estado'       => 'recibido',
            // El archivo nuevo hay que volver a revisarlo, validarlo y capturarlo
            'capturado_at'   => null,
            'validado_at'    => null,
            'validado_por'   => null,
            'motivo_rechazo' => null,
        ]);

        return response()->json(['data' => $this->serialize($doc->fresh())]);
    }

    /**
     * POST /api/v1/expedientes/{expedienteId}/documentos/{documentoId}/validar
     *
     * Solo un administrador da por válido un documento (los asesores solo lo ven).
     */
    public function validar(Request $request, int $expedienteId, int $documentoId): JsonResponse
    {
        $this->soloAdmin($request);

        $doc = DocumentoExpediente::where('expediente_id', $expedienteId)->findOrFail($documentoId);

        if (! $doc->ruta_archivo) {
            abort(422, 'Este documento no tiene archivo — no hay nada que validar.');
        }

        $doc->update([
            'estado'         => 'recibido',
            'validado_at'    => now(),
            'validado_por'   => $request->user()->id,
            'motivo_rechazo' => null,
        ]);

        return response()->json(['data' => $this->serialize($doc->fresh())]);
    }

    /**
     * POST /api/v1/expedientes/{expedienteId}/documentos/{documentoId}/rechazar
     *
     * Solo un administrador rechaza un documento (ilegible, vencido, incorrecto…) con un
     * motivo. El archivo se conserva; el estado pasa a 'rechazado' y, si el acreditado
     * usa la app, recibe un push con el motivo.
     */
    public function rechazar(Request $request, int $expedienteId, int $documentoId): JsonResponse
    {
        $this->soloAdmin($request);

        $exp = Expediente::findOrFail($expedienteId);
        $doc = DocumentoExpediente::where('expediente_id', $expedienteId)->findOrFail($documentoId);

        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        if (! $doc->ruta_archivo) {
            abort(422, 'Este documento no tiene archivo — no hay nada que rechazar.');
        }

        $doc->update([
            'estado'         => 'rechazado',
            'motivo_rechazo' => $data['motivo'],   // `notas` queda intacta: identifica el documento
            'validado_at'    => null,
            'validado_por'   => null,
        ]);

        if ($exp->acreditadoRegistrado) {
            $exp->acreditadoRegistrado->notify(
                new \App\Notifications\DocumentoRechazado($doc, $exp, $data['motivo'])
            );
        }

        return response()->json(['data' => $this->serialize($doc->fresh())]);
    }

    private function soloAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('super_admin'), 403, 'Solo un administrador puede validar documentos.');
    }
}
