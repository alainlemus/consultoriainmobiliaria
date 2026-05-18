<?php

namespace App\Observers;

use App\Models\Comision;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use App\Notifications\ExpedienteCerrado;
use App\Notifications\EtapaExpedienteCambiada;
use App\Notifications\NuevoExpedienteCreado;

class ExpedienteObserver
{
    /**
     * Al crear un expediente: generar checklist de documentos
     * y notificar a todos los super_admin.
     */
    public function created(Expediente $expediente): void
    {
        $this->sincronizarChecklist($expediente);

        User::role('super_admin')
            ->get()
            ->each(fn (User $admin) => $admin->notify(new NuevoExpedienteCreado($expediente)));
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

        // Notificar al asesor si la etapa cambia
        if ($expediente->wasChanged('etapa_tramite_id') && $expediente->asesor) {
            $etapaAnterior = $expediente->getOriginal('etapa_tramite_id');
            $nombreAnterior = \App\Models\EtapaTramite::find($etapaAnterior)?->nombre ?? 'Anterior';
            $nombreNueva    = $expediente->etapa?->nombre ?? 'Nueva etapa';

            $expediente->asesor->notify(new EtapaExpedienteCambiada(
                expediente:     $expediente,
                etapaAnterior:  $nombreAnterior,
                etapaNueva:     $nombreNueva,
            ));
        }

        // Generar comisión al cerrar expediente y notificar al asesor
        if (
            $expediente->wasChanged('estado') &&
            $expediente->estado === 'cerrado' &&
            $expediente->asesor_id &&
            $expediente->honorarios_monto > 0
        ) {
            $existe = Comision::where('expediente_id', $expediente->id)->exists();

            if (! $existe) {
                Comision::create([
                    'expediente_id'       => $expediente->id,
                    'asesor_id'           => $expediente->asesor_id,
                    'monto_base'          => $expediente->honorarios_monto,
                    'porcentaje_comision' => $expediente->honorarios_porcentaje ?? 0,
                    'monto_comision'      => $expediente->honorarios_monto,
                    'estado'              => 'pendiente',
                    'fecha_generacion'    => now()->toDateString(),
                ]);
            }

            // Notificar al asesor que su expediente fue cerrado
            $expediente->asesor?->notify(new ExpedienteCerrado($expediente));
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
