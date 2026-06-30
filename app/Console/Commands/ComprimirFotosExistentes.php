<?php

namespace App\Console\Commands;

use App\Models\AnuncioFoto;
use App\Models\UbicacionFoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ComprimirFotosExistentes extends Command
{
    protected $signature = 'fotos:comprimir
                            {--tipo=todas : Qué fotos comprimir: ubicaciones, anuncios, o todas}
                            {--calidad=72 : Calidad JPEG (1-100)}
                            {--max-lado=1280 : Píxeles máximos en el lado mayor}
                            {--dry-run : Solo muestra qué se haría, sin modificar nada}';

    protected $description = 'Comprime las fotos ya almacenadas en disco (ubicaciones y anuncios)';

    private const DISK = 'local';

    public function handle(): int
    {
        if (! class_exists(\Imagick::class)) {
            $this->error('Imagick no está disponible. Instala la extensión PHP Imagick para continuar.');
            return self::FAILURE;
        }

        $tipo     = $this->option('tipo');
        $calidad  = (int) $this->option('calidad');
        $maxLado  = (int) $this->option('max-lado');
        $dryRun   = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('--- MODO DRY-RUN: no se modificará nada ---');
        }

        $this->info("Calidad JPEG: {$calidad}%  |  Lado máximo: {$maxLado}px");
        $this->newLine();

        $totalProcesadas = 0;
        $totalErrores    = 0;
        $ahorroBytes     = 0;

        if (in_array($tipo, ['ubicaciones', 'todas'])) {
            [$p, $e, $a] = $this->procesarModelo(
                UbicacionFoto::class, 'Fotos de ubicaciones', $calidad, $maxLado, $dryRun
            );
            $totalProcesadas += $p;
            $totalErrores    += $e;
            $ahorroBytes     += $a;
        }

        if (in_array($tipo, ['anuncios', 'todas'])) {
            [$p, $e, $a] = $this->procesarModelo(
                AnuncioFoto::class, 'Fotos de anuncios', $calidad, $maxLado, $dryRun
            );
            $totalProcesadas += $p;
            $totalErrores    += $e;
            $ahorroBytes     += $a;
        }

        $this->newLine();
        $this->info('=== Resumen ===');
        $this->table(
            ['', 'Valor'],
            [
                ['Fotos procesadas', $totalProcesadas],
                ['Errores',          $totalErrores],
                ['Espacio liberado', $this->formatBytes($ahorroBytes)],
            ]
        );

        if ($dryRun) {
            $this->warn('Modo dry-run: ningún archivo fue modificado.');
        }

        return self::SUCCESS;
    }

    private function procesarModelo(
        string $modelClass,
        string $etiqueta,
        int $calidad,
        int $maxLado,
        bool $dryRun
    ): array {
        $this->info("Procesando: {$etiqueta}");

        $procesadas = 0;
        $errores    = 0;
        $ahorro     = 0;

        // Iterar en chunks para no agotar memoria
        $modelClass::orderBy('id')->chunk(50, function ($fotos) use (
            $calidad, $maxLado, $dryRun,
            &$procesadas, &$errores, &$ahorro
        ) {
            foreach ($fotos as $foto) {
                $ruta = $foto->ruta;

                if (! Storage::disk(self::DISK)->exists($ruta)) {
                    $this->warn("  [#{$foto->id}] Archivo no encontrado: {$ruta}");
                    $errores++;
                    continue;
                }

                $rutaAbsoluta = Storage::disk(self::DISK)->path($ruta);
                $pesoOriginal = filesize($rutaAbsoluta);

                // Detectar si ya es un JPEG pequeño (< 200 KB): saltar
                if ($pesoOriginal < 200 * 1024 && str_ends_with(strtolower($ruta), '.jpg')) {
                    $this->line("  [#{$foto->id}] Ya comprimida ({$this->formatBytes($pesoOriginal)}), omitida.");
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [#{$foto->id}] Se comprimiría: {$this->formatBytes($pesoOriginal)} → estimado ~{$this->formatBytes((int)($pesoOriginal * 0.10))}  ({$ruta})");
                    $procesadas++;
                    continue;
                }

                try {
                    $nuevoContenido = $this->comprimir($rutaAbsoluta, $calidad, $maxLado);
                    $pesoNuevo      = strlen($nuevoContenido);

                    // Solo reemplazar si realmente redujo el tamaño
                    if ($pesoNuevo >= $pesoOriginal) {
                        $this->line("  [#{$foto->id}] Sin mejora ({$this->formatBytes($pesoOriginal)}), omitida.");
                        continue;
                    }

                    // Guardar con nombre .jpg, borrar el original si cambia la extensión
                    $nuevaRuta = preg_replace('/\.[^.]+$/', '.jpg', $ruta);
                    Storage::disk(self::DISK)->put($nuevaRuta, $nuevoContenido);

                    if ($nuevaRuta !== $ruta) {
                        Storage::disk(self::DISK)->delete($ruta);
                    }

                    // Actualizar BD
                    $foto->update(['ruta' => $nuevaRuta, 'mime' => 'image/jpeg']);

                    $reduccion = $pesoOriginal - $pesoNuevo;
                    $ahorro   += $reduccion;
                    $procesadas++;

                    $this->line(sprintf(
                        '  [#%d] %s → %s  (-%s, -%d%%)',
                        $foto->id,
                        $this->formatBytes($pesoOriginal),
                        $this->formatBytes($pesoNuevo),
                        $this->formatBytes($reduccion),
                        round($reduccion / $pesoOriginal * 100)
                    ));
                } catch (\Throwable $e) {
                    $this->error("  [#{$foto->id}] Error: {$e->getMessage()}");
                    $errores++;
                }
            }
        });

        $this->info("  Subtotal: {$procesadas} comprimidas, {$errores} errores, {$this->formatBytes($ahorro)} liberados.");
        $this->newLine();

        return [$procesadas, $errores, $ahorro];
    }

    private function comprimir(string $rutaAbsoluta, int $calidad, int $maxLado): string
    {
        $im = new \Imagick($rutaAbsoluta);
        $im->autoOrient();

        $w = $im->getImageWidth();
        $h = $im->getImageHeight();
        if ($w > $maxLado || $h > $maxLado) {
            $im->resizeImage($maxLado, $maxLado, \Imagick::FILTER_LANCZOS, 1, true);
        }

        $im->setImageFormat('jpeg');
        $im->setImageCompressionQuality($calidad);
        $im->stripImage();

        $blob = $im->getImageBlob();
        $im->clear();
        $im->destroy();

        return $blob;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
