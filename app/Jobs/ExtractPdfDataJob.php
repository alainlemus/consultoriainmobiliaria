<?php

namespace App\Jobs;

use App\Models\Expediente;
use App\Services\PdfExtractorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Procesa el OCR de los PDFs de un expediente en background.
 * - Marca ocr_procesando = true al iniciar
 * - Extrae datos de todos los PDFs con prioridad por tipo de documento
 * - Rellena solo campos vacíos del expediente
 * - Marca ocr_procesando = false al terminar (incluso si falla)
 * - Notifica al asesor con los resultados
 */
class ExtractPdfDataJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries   = 1;

    public function __construct(
        private int $expedienteId
    ) {}

    public function handle(PdfExtractorService $extractor): void
    {
        $expediente = Expediente::find($this->expedienteId);
        if (! $expediente) return;

        $expediente->updateQuietly(['ocr_procesando' => true]);

        try {
            $this->procesarOcr($expediente, $extractor);
        } finally {
            $expediente->updateQuietly(['ocr_procesando' => false]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OCR y extracción de datos
    // ─────────────────────────────────────────────────────────────────────────

    private function procesarOcr(Expediente $expediente, PdfExtractorService $extractor): void
    {
        $precedencia = [
            'avaluo'          => 10,
            'sar'             => 9,
            'afore'           => 8,
            'curp'            => 7,
            'sat'             => 7,
            'ine'             => 6,
            'acta_nacimiento' => 5,
            'talon_fovissste' => 5,
            'talon'           => 4,
            'escritura'       => 4,
            'predial'         => 3,
            'cuenta_bancaria' => 3,
            'desconocido'     => 1,
        ];

        $camposMejores = [];

        foreach ($expediente->documentos as $doc) {
            if (! $doc->ruta_archivo) continue;
            if (strtolower(pathinfo($doc->ruta_archivo, PATHINFO_EXTENSION)) !== 'pdf') continue;

            $rutaAbsoluta = Storage::disk('local')->path($doc->ruta_archivo);
            if (! file_exists($rutaAbsoluta)) continue;

            $resultado = $extractor->extraer($rutaAbsoluta, basename($doc->ruta_archivo));
            if (empty($resultado['datos'])) continue;

            $prioridad = $precedencia[$resultado['tipo'] ?? 'desconocido'] ?? 1;

            foreach ($resultado['datos'] as $campo => $valor) {
                if (empty($valor)) continue;
                if (! isset($camposMejores[$campo]) || $prioridad >= $camposMejores[$campo]['prioridad']) {
                    $camposMejores[$campo] = ['valor' => $valor, 'prioridad' => $prioridad];
                }
            }
        }

        if (empty($camposMejores)) return;

        $mapa = [
            'acreditado_nombre'           => $camposMejores['acreditado_nombre']['valor'] ?? $camposMejores['nombre']['valor'] ?? null,
            'acreditado_curp'             => $camposMejores['curp']['valor']              ?? null,
            'acreditado_rfc'              => $camposMejores['acreditado_rfc']['valor']    ?? $camposMejores['rfc']['valor'] ?? null,
            'acreditado_fecha_nacimiento' => $camposMejores['fecha_nacimiento']['valor']  ?? null,
            'acreditado_domicilio'        => $camposMejores['acreditado_domicilio']['valor'] ?? null,
            'acreditado_colonia'          => $camposMejores['acreditado_colonia']['valor']   ?? null,
            'acreditado_municipio'        => $camposMejores['acreditado_municipio']['valor'] ?? null,
            'acreditado_estado'           => $camposMejores['acreditado_estado']['valor']    ?? null,
            'acreditado_cp'               => $camposMejores['acreditado_cp']['valor']        ?? null,
            'acreditado_numero_credito'   => $camposMejores['acreditado_numero_credito']['valor'] ?? null,
            'subcuenta_vivienda'          => $camposMejores['subcuenta_vivienda']['valor']   ?? null,
            'monto_total_estimado'        => $camposMejores['monto_total_estimado']['valor'] ?? null,
            'vivienda_calle'              => $camposMejores['vivienda_calle']['valor']       ?? null,
            'vivienda_numero'             => $camposMejores['vivienda_numero']['valor']      ?? null,
            'vivienda_colonia'            => $camposMejores['vivienda_colonia']['valor']     ?? null,
            'vivienda_cp'                 => $camposMejores['vivienda_cp']['valor']          ?? null,
            'vivienda_municipio'          => $camposMejores['vivienda_municipio']['valor']   ?? null,
            'vivienda_estado'             => $camposMejores['vivienda_estado']['valor']      ?? null,
            'vivienda_superficie'         => $camposMejores['vivienda_superficie']['valor']  ?? null,
            'cuv'                         => $camposMejores['cuv']['valor']                  ?? null,
            'vendedor_nombre'             => $camposMejores['vendedor_nombre']['valor']      ?? null,
            'vendedor_rfc'                => $camposMejores['vendedor_rfc']['valor']         ?? null,
            'vendedor_clabe'              => $camposMejores['vendedor_clabe']['valor']       ?? null,
            'vendedor_banco'              => $camposMejores['vendedor_banco']['valor']       ?? null,
        ];

        $actualizacion = [];
        foreach (array_filter($mapa) as $campo => $valor) {
            if (empty($expediente->$campo)) {
                $actualizacion[$campo] = $valor;
            }
        }

        if (empty($actualizacion)) return;

        $expediente->updateQuietly($actualizacion);
        Log::info("OCR completado para expediente {$expediente->folio}: " . count($actualizacion) . " campos rellenados.");

        $asesor = $expediente->asesor;
        if ($asesor) {
            $asesor->notify(new \App\Notifications\OcrCompletado($expediente, count($actualizacion)));
        }
    }
}
