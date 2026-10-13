<?php

namespace App\Services;

use App\Models\Expediente;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Arma el ZIP con la carpeta del expediente:
 *   NOMBRE_ACREDITADO/<CARPETA o SECCIÓN>/<documento>.<ext>
 */
class CarpetaExpedienteService
{
    /**
     * @return array{ruta: string, nombre: string, archivos: int}|null  null si no hay archivos físicos
     */
    public function crearZip(Expediente $expediente): ?array
    {
        $documentos = $expediente->documentos()->whereNotNull('ruta_archivo')->get();

        if ($documentos->isEmpty()) {
            return null;
        }

        $nombreAcreditado = $expediente->acreditado_nombre
            ? Str::upper($expediente->acreditado_nombre)
            : $expediente->folio;

        $raiz = preg_replace('/[^A-Z0-9\s\-_]/i', '', $nombreAcreditado);
        $raiz = trim(preg_replace('/\s+/', ' ', $raiz)) ?: $expediente->folio;

        $tmpZip = sys_get_temp_dir() . '/expediente_' . $expediente->id . '_' . uniqid() . '.zip';
        $zip    = new ZipArchive();

        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        $agregados = 0;
        $usados    = [];

        foreach ($documentos as $doc) {
            $absoluta = Storage::disk('local')->path($doc->ruta_archivo);
            if (! file_exists($absoluta)) {
                continue;
            }

            // Carpeta: la categoría de carga masiva o, si no hay, la sección (ACREDITADO, VENDEDOR…)
            $carpeta = $doc->categoria
                ? implode('/', array_map(fn ($p) => strtoupper(trim($p)), explode('/', $doc->categoria)))
                : strtoupper($doc->seccion ?: 'GENERAL');

            $base = $doc->nombre ? Str::upper($doc->nombre) : strtoupper(pathinfo($doc->ruta_archivo, PATHINFO_FILENAME));
            $base = trim(preg_replace('/[^\w\s\-\.\(\)·]/u', '', $base)) ?: 'DOCUMENTO';
            // La nota identifica el documento ("INE del dueño"): se incluye en el nombre
            if ($doc->notas) {
                $nota = trim(preg_replace('/[^\w\s\-]/u', '', Str::upper($doc->notas)));
                $base .= $nota ? ' - ' . Str::limit($nota, 40, '') : '';
            }

            $ext     = pathinfo($doc->ruta_archivo, PATHINFO_EXTENSION);
            $enZip   = "{$raiz}/{$carpeta}/{$base}.{$ext}";
            // Evita pisar archivos con el mismo nombre
            for ($n = 2; isset($usados[$enZip]); $n++) {
                $enZip = "{$raiz}/{$carpeta}/{$base} ({$n}).{$ext}";
            }
            $usados[$enZip] = true;

            $zip->addFile($absoluta, $enZip);
            $agregados++;
        }

        $zip->close();

        if ($agregados === 0) {
            @unlink($tmpZip);
            return null;
        }

        return [
            'ruta'     => $tmpZip,
            'nombre'   => Str::slug($raiz) . '.zip',
            'archivos' => $agregados,
        ];
    }
}
