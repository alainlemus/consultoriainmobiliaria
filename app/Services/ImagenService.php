<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagenService
{
    /**
     * Comprime una imagen subida y la guarda en el disco indicado.
     * Devuelve la ruta relativa almacenada.
     *
     * Estrategia:
     *  - Redimensiona a un máximo de 1280px en el lado mayor (mantiene aspecto)
     *  - Re-codifica como JPEG al 72% de calidad
     *  - Elimina metadatos EXIF/IPTC para reducir peso adicional
     *
     * En la práctica una foto de móvil de 4–8 MB queda en ~100–300 KB,
     * suficiente para thumbnails en el mapa sin pérdida visual notable.
     */
    public function comprimirYGuardar(
        UploadedFile $archivo,
        string $directorio,
        string $disk = 'local',
        int $maxLado = 1280,
        int $calidad = 72
    ): string {
        if (class_exists(\Imagick::class)) {
            try {
                return $this->comprimirConImagick(
                    $archivo, $directorio, $disk, $maxLado, $calidad
                );
            } catch (\Throwable) {
                // Si falla Imagick por algún motivo, guarda el original
            }
        }

        // Fallback: guardar sin compresión
        return $archivo->store($directorio, $disk);
    }

    private function comprimirConImagick(
        UploadedFile $archivo,
        string $directorio,
        string $disk,
        int $maxLado,
        int $calidad
    ): string {
        $im = new \Imagick($archivo->getRealPath());

        // Corregir orientación EXIF antes de todo
        $im->autoOrient();

        // Redimensionar si excede el lado máximo
        $w = $im->getImageWidth();
        $h = $im->getImageHeight();
        if ($w > $maxLado || $h > $maxLado) {
            $im->resizeImage($maxLado, $maxLado, \Imagick::FILTER_LANCZOS, 1, true);
        }

        // Convertir a JPEG y comprimir
        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality($calidad);
        $im->stripImage(); // elimina metadatos EXIF/IPTC/XMP

        $contenido = $im->getImageBlob();
        $im->clear();
        $im->destroy();

        // Construir ruta con extensión .jpg
        $nombre   = Str::random(40) . '.jpg';
        $rutaRel  = $directorio . '/' . $nombre;

        Storage::disk($disk)->put($rutaRel, $contenido);

        return $rutaRel;
    }
}
