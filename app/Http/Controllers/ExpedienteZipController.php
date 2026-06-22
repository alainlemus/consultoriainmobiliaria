<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExpedienteZipController extends Controller
{
    /**
     * Genera y descarga un ZIP con la estructura de carpetas del expediente.
     *
     * Estructura del ZIP:
     *   NOMBRE_ACREDITADO/
     *     ACREDITADA/
     *       curp.pdf
     *       ine.pdf
     *       ...
     *     VENDEDOR/
     *       ine.pdf
     *       ...
     *     VIVIENDA/
     *       escritura.pdf
     *       ...
     *     SOFOM/
     *       ACREDITADA/
     *         curp.pdf
     *       VENDEDOR/
     *         cuenta.pdf
     *       VIVIENDA/
     *         avaluo.pdf
     *     CATASTRO/
     *       ...
     *     NOTARIA/
     *       ...
     */
    public function descargar(Request $request, int $expedienteId)
    {
        $expediente = Expediente::findOrFail($expedienteId);

        // Autorización: solo el asesor dueño o super_admin
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super_admin') ||
            $user?->hasRole('admin') ||
            $expediente->asesor_id === $user?->id ||
            $user?->can('ViewAny:Expediente'),
            403
        );

        $documentos = $expediente->documentos()
            ->whereNotNull('ruta_archivo')
            ->get();

        if ($documentos->isEmpty()) {
            abort(404, 'Este expediente no tiene documentos para descargar.');
        }

        // Nombre de la carpeta raíz: nombre del acreditado o folio
        $nombreAcreditado = $expediente->acreditado_nombre
            ? Str::upper($expediente->acreditado_nombre)
            : $expediente->folio;

        // Sanitizar para nombres de archivo seguros
        $nombreCarpetaRaiz = preg_replace('/[^A-Z0-9\s\-_]/i', '', $nombreAcreditado);
        $nombreCarpetaRaiz = trim(preg_replace('/\s+/', ' ', $nombreCarpetaRaiz));

        // Crear ZIP en directorio temporal
        $tmpZip  = sys_get_temp_dir() . '/expediente_' . $expedienteId . '_' . time() . '.zip';
        $zip     = new ZipArchive();

        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        $archivosAgregados = 0;

        foreach ($documentos as $doc) {
            if (! $doc->ruta_archivo) continue;

            $rutaAbsoluta = Storage::disk('local')->path($doc->ruta_archivo);
            if (! file_exists($rutaAbsoluta)) continue;

            // Construir la ruta dentro del ZIP basada en la categoría
            // categoria: "acreditada" → carpeta: "ACREDITADA"
            // categoria: "avaluo/vivienda" → carpeta: "AVALUO/VIVIENDA"
            // categoria: "sofom/acreditada" → carpeta: "SOFOM/ACREDITADA"
            $carpetaCategoria = $this->categoriaACarpeta($doc->categoria ?? 'GENERAL');

            // Nombre del archivo dentro del ZIP: nombre legible + extensión original
            $nombreOriginal = $doc->nombre
                ? Str::upper($doc->nombre)
                : strtoupper(pathinfo($doc->ruta_archivo, PATHINFO_FILENAME));

            $extension = pathinfo($doc->ruta_archivo, PATHINFO_EXTENSION);
            $nombreArchivo = $nombreOriginal . '.' . $extension;

            // Sanitizar nombre del archivo
            $nombreArchivo = preg_replace('/[^\w\s\-\.]/u', '', $nombreArchivo);
            $nombreArchivo = trim($nombreArchivo);

            // Ruta completa dentro del ZIP
            $rutaEnZip = $nombreCarpetaRaiz . '/' . $carpetaCategoria . '/' . $nombreArchivo;

            $zip->addFile($rutaAbsoluta, $rutaEnZip);
            $archivosAgregados++;
        }

        $zip->close();

        if ($archivosAgregados === 0) {
            unlink($tmpZip);
            abort(404, 'No se encontraron archivos físicos para descargar.');
        }

        $nombreDescarga = Str::slug($nombreCarpetaRaiz ?: $expediente->folio) . '.zip';

        return response()->download($tmpZip, $nombreDescarga, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $nombreDescarga . '"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Convierte la categoría interna en una ruta de carpeta legible en MAYÚSCULAS.
     * "acreditada"       → "ACREDITADA"
     * "avaluo/vivienda"  → "AVALUO/VIVIENDA"
     * "sofom/acreditada" → "SOFOM/ACREDITADA"
     */
    private function categoriaACarpeta(string $categoria): string
    {
        return implode('/', array_map(
            fn ($parte) => strtoupper(trim($parte)),
            explode('/', $categoria)
        ));
    }
}
