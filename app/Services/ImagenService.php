<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
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
     * Intenta Imagick primero, luego GD; solo si ambos fallan guarda el
     * original sin comprimir (evita fotos de varios MB servidas tal cual).
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
                return $this->comprimirConImagick($archivo, $directorio, $disk, $maxLado, $calidad);
            } catch (\Throwable $e) {
                Log::warning('ImagenService: fallo compresión con Imagick, probando GD', ['error' => $e->getMessage()]);
            }
        }

        if (function_exists('imagecreatetruecolor')) {
            try {
                return $this->comprimirConGd($archivo, $directorio, $disk, $maxLado, $calidad);
            } catch (\Throwable $e) {
                Log::warning('ImagenService: fallo compresión con GD, guardando original', ['error' => $e->getMessage()]);
            }
        }

        // Último recurso: guardar sin comprimir
        return $archivo->store($directorio, $disk);
    }

    /**
     * Genera una miniatura pequeña (para tablas/mapas) a partir de una imagen
     * ya guardada en el disco. Devuelve la ruta relativa del thumb o null si falla.
     */
    public function generarThumbnail(
        string $rutaOriginal,
        string $disk = 'local',
        int $maxLado = 240,
        int $calidad = 60
    ): ?string {
        try {
            $absoluta = Storage::disk($disk)->path($rutaOriginal);
            $directorio = pathinfo($rutaOriginal, PATHINFO_DIRNAME);
            $nombreThumb = pathinfo($rutaOriginal, PATHINFO_FILENAME) . '_thumb.jpg';
            $rutaThumb = $directorio . '/' . $nombreThumb;

            if (class_exists(\Imagick::class)) {
                $im = new \Imagick($absoluta);
                $im->autoOrient();
                $im->resizeImage($maxLado, $maxLado, \Imagick::FILTER_LANCZOS, 1, true);
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality($calidad);
                $im->stripImage();
                $contenido = $im->getImageBlob();
                $im->clear();
                $im->destroy();
            } elseif (function_exists('imagecreatetruecolor')) {
                $contenido = $this->redimensionarConGd($absoluta, $maxLado, $calidad);
            } else {
                return null;
            }

            Storage::disk($disk)->put($rutaThumb, $contenido);

            return $rutaThumb;
        } catch (\Throwable $e) {
            Log::warning('ImagenService: fallo generando thumbnail', ['error' => $e->getMessage()]);

            return null;
        }
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

        return $this->guardarContenido($contenido, $directorio, $disk);
    }

    private function comprimirConGd(
        UploadedFile $archivo,
        string $directorio,
        string $disk,
        int $maxLado,
        int $calidad
    ): string {
        $contenido = $this->redimensionarConGd($archivo->getRealPath(), $maxLado, $calidad);

        return $this->guardarContenido($contenido, $directorio, $disk);
    }

    private function redimensionarConGd(string $rutaAbsoluta, int $maxLado, int $calidad): string
    {
        $info = getimagesize($rutaAbsoluta);
        if (! $info) {
            throw new \RuntimeException('GD: no se pudo leer la imagen.');
        }

        $origen = match ($info['mime']) {
            'image/jpeg' => imagecreatefromjpeg($rutaAbsoluta),
            'image/png'  => imagecreatefrompng($rutaAbsoluta),
            'image/webp' => imagecreatefromwebp($rutaAbsoluta),
            default      => throw new \RuntimeException('GD: formato no soportado.'),
        };

        [$w, $h] = [imagesx($origen), imagesy($origen)];
        $escala  = min(1, $maxLado / max($w, $h));
        $nuevoW  = max(1, (int) round($w * $escala));
        $nuevoH  = max(1, (int) round($h * $escala));

        $destino = imagecreatetruecolor($nuevoW, $nuevoH);
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoW, $nuevoH, $w, $h);

        ob_start();
        imagejpeg($destino, null, $calidad);
        $contenido = ob_get_clean();

        imagedestroy($origen);
        imagedestroy($destino);

        return $contenido;
    }

    private function guardarContenido(string $contenido, string $directorio, string $disk): string
    {
        $nombre  = Str::random(40) . '.jpg';
        $rutaRel = $directorio . '/' . $nombre;

        Storage::disk($disk)->put($rutaRel, $contenido);

        return $rutaRel;
    }
}
