<?php

namespace App\Services;

use App\Models\ContratoGenerado;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Oficio de regresión de fases: el acreditado y el obligado solidario piden a FOVISSSTE
 * (AT'N Hipotecaria Hogar) que se decline el crédito. Se llena con sus nombres, la ciudad
 * y la fecha del contrato; el número de crédito queda en blanco para capturarse al firmar.
 */
class RegresionFasesService
{
    /**
     * @return array{lugar: string, fecha: string, acreditado: string, solidario: string}
     */
    public function datos(ContratoGenerado $c): array
    {
        $ciudad = trim((string) $c->ciudad) ?: 'Huejutla de Reyes';
        $fecha  = ($c->created_at ?? now())->copy()->locale('es');

        return [
            // "Huejutla de Reyes, Estado de Hidalgo"; si la ciudad ya trae el estado, no se repite.
            'lugar'      => str_contains(mb_strtolower($ciudad), 'hidalgo') ? $ciudad : "{$ciudad}, Estado de Hidalgo",
            'fecha'      => $fecha->translatedFormat('j \d\e F \d\e Y'),
            'acreditado' => mb_strtoupper(trim((string) $c->acreditado_nombre)),
            'solidario'  => mb_strtoupper(trim((string) $c->solidario_nombre)),
        ];
    }

    /** PDF del oficio (hoja carta) con la vista contratos.regresion_fases. */
    public function pdf(ContratoGenerado $c): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('contratos.regresion_fases', ['d' => $this->datos($c)])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');
    }
}
