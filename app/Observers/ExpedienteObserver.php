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
     * Al crear un expediente: generar checklist de documentos,
     * notificar a todos los super_admin y marcar el contacto como en_tramite.
     */
    public function created(Expediente $expediente): void
    {
        $this->sincronizarChecklist($expediente);

        // El contacto ya no es un prospecto — tiene expediente activo
        if ($expediente->contacto_id) {
            \App\Models\Contacto::where('id', $expediente->contacto_id)
                ->whereNotIn('estado_prospecto', ['convertido', 'descartado'])
                ->update(['estado_prospecto' => 'convertido']);
        }

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
            $expediente->honorarios_monto > 0 &&
            $expediente->honorarios_pagados === true          // BUG-05: solo si ya se cobró
        ) {
            $existe = Comision::where('expediente_id', $expediente->id)->exists();

            if (! $existe) {
                $porcentaje    = (float) ($expediente->honorarios_porcentaje ?? 0);
                $montoBase     = (float) $expediente->honorarios_monto;
                // BUG-04: aplicar porcentaje real, no el 100%
                $montoComision = $porcentaje > 0
                    ? round($montoBase * $porcentaje / 100, 2)
                    : $montoBase;

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
