<?php

namespace App\Observers;

use App\Models\Comision;
use App\Models\Expediente;

class ExpedienteObserver
{
    /**
     * Al actualizar un expediente, si el estado cambia a "cerrado"
     * y tiene honorarios configurados, se genera la comisión del asesor
     * si aún no existe una para ese expediente.
     */
    public function updated(Expediente $expediente): void
    {
        // Solo actuar cuando el estado acaba de cambiar a "cerrado"
        if (
            $expediente->wasChanged('estado') &&
            $expediente->estado === 'cerrado' &&
            $expediente->asesor_id &&
            $expediente->honorarios_monto > 0
        ) {
            // Evitar duplicados
            $existe = Comision::where('expediente_id', $expediente->id)->exists();

            if (! $existe) {
                $porcentaje = $expediente->honorarios_porcentaje ?? 0;
                $montoBase  = $expediente->honorarios_monto;

                // Si hay porcentaje definido, calcular sobre monto_total_estimado
                if ($porcentaje > 0 && $expediente->monto_total_estimado > 0) {
                    $montoBase = $expediente->monto_total_estimado;
                    $montoComision = round($montoBase * ($porcentaje / 100), 2);
                } else {
                    // Usar directamente el monto de honorarios como comisión al 100%
                    $porcentaje    = 100;
                    $montoComision = $montoBase;
                }

                Comision::create([
                    'expediente_id'       => $expediente->id,
                    'asesor_id'           => $expediente->asesor_id,
                    'monto_base'          => $montoBase,
                    'porcentaje_comision' => $porcentaje,
                    'monto_comision'      => $montoComision,
                    'estado'              => 'pendiente',
                    'fecha_generacion'    => now()->toDateString(),
                ]);
            }
        }
    }
}
