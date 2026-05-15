<?php

namespace App\Observers;

use App\Models\Comision;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;

class ExpedienteObserver
{
    /**
     * Al crear un expediente: generar checklist de documentos.
     */
    public function created(Expediente $expediente): void
    {
        $this->sincronizarChecklist($expediente);
    }

    /**
     * Al actualizar: si cambia tipo_tramite_id o vivienda_tipo,
     * re-sincronizar el checklist. También manejar cierre para comisión.
     */
    public function updated(Expediente $expediente): void
    {
        // Re-sincronizar checklist si cambian los campos que lo determinan
        if ($expediente->wasChanged(['tipo_tramite_id', 'vivienda_tipo'])) {
            $this->sincronizarChecklist($expediente);
        }

        // Generar comisión al cerrar expediente
        if (
            $expediente->wasChanged('estado') &&
            $expediente->estado === 'cerrado' &&
            $expediente->asesor_id &&
            $expediente->honorarios_monto > 0
        ) {
            $existe = Comision::where('expediente_id', $expediente->id)->exists();

            if (! $existe) {
                $montoComision = 10000; // $10,000 MXN fijo por cierre

                Comision::create([
                    'expediente_id'       => $expediente->id,
                    'asesor_id'           => $expediente->asesor_id,
                    'monto_base'          => $expediente->honorarios_monto,
                    'porcentaje_comision' => 0,
                    'monto_comision'      => $montoComision,
                    'estado'              => 'pendiente',
                    'fecha_generacion'    => now()->toDateString(),
                ]);
            }
        }
    }

    /**
     * Sincroniza (agrega los faltantes, no borra los existentes) el checklist
     * de documentos según tipo de trámite y tipo de inmueble.
     */
    private function sincronizarChecklist(Expediente $expediente): void
    {
        if (! $expediente->tipo_tramite_id) {
            return;
        }

        $catalogo = DocumentoExpediente::catalogoPara(
            $expediente->tipo_tramite_id,
            $expediente->vivienda_tipo
        );

        $tiposExistentes = $expediente->documentos()->pluck('tipo')->toArray();

        foreach ($catalogo as $item) {
            if (! in_array($item['tipo'], $tiposExistentes)) {
                $expediente->documentos()->create([
                    'tipo'   => $item['tipo'],
                    'nombre' => $item['nombre'],
                    'estado' => 'pendiente',
                ]);
            }
        }
    }
}
