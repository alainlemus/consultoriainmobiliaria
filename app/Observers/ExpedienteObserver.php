<?php

namespace App\Observers;

use App\Models\Comision;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use App\Notifications\ExpedienteCerrado;
use App\Notifications\EtapaExpedienteCambiada;
use App\Notifications\NuevoExpedienteCreado;
use App\Services\WhatsAppService;

class ExpedienteObserver
{
    public function created(Expediente $expediente): void
    {
        $this->sincronizarChecklist($expediente);

        if ($expediente->contacto_id) {
            \App\Models\Contacto::where('id', $expediente->contacto_id)
                ->whereNotIn('estado_prospecto', ['convertido', 'descartado'])
                ->update(['estado_prospecto' => 'convertido']);
        }

        // WhatsApp al asesor: nuevo expediente asignado
        if ($expediente->asesor?->telefono) {
            $folio   = $expediente->folio ?? "#{$expediente->id}";
            $cliente = $expediente->acreditado_nombre;
            WhatsAppService::sendText(
                $expediente->asesor->telefono,
                "📂 *Nuevo expediente asignado*\n\n" .
                "Folio: *{$folio}*\n" .
                "Cliente: {$cliente}\n\n" .
                "Entra al CRM para ver los detalles y comenzar el trámite."
            );
        }

        // WhatsApp al acreditado: confirmación de inicio de trámite
        if ($expediente->acreditado_telefono) {
            $folio   = $expediente->folio ?? "#{$expediente->id}";
            $cliente = $expediente->acreditado_nombre;
            WhatsAppService::sendText(
                $expediente->acreditado_telefono,
                "¡Hola *{$cliente}*! 🏠\n\n" .
                "Tu trámite ha sido iniciado con el folio *{$folio}*.\n\n" .
                "Tu asesor estará en contacto contigo para guiarte en cada etapa del proceso. ¡Bienvenido!"
            );
        }

        User::role('super_admin')
            ->get()
            ->each(fn (User $admin) => $admin->notify(new NuevoExpedienteCreado($expediente)));
    }

    public function updated(Expediente $expediente): void
    {
        if ($expediente->wasChanged(['tipo_tramite_id', 'vivienda_tipo'])) {
            $this->sincronizarChecklist($expediente);
        }

        // Notificar al asesor si la etapa cambia
        if ($expediente->wasChanged('etapa_tramite_id') && $expediente->asesor) {
            $etapaAnterior  = $expediente->getOriginal('etapa_tramite_id');
            $nombreAnterior = \App\Models\EtapaTramite::find($etapaAnterior)?->nombre ?? 'Anterior';
            $nombreNueva    = \App\Models\EtapaTramite::find($expediente->etapa_tramite_id)?->nombre ?? 'Nueva etapa';

            // Push notification al asesor
            $expediente->asesor->notify(new EtapaExpedienteCambiada(
                expediente:    $expediente,
                etapaAnterior: $nombreAnterior,
                etapaNueva:    $nombreNueva,
            ));

            // WhatsApp al asesor si tiene teléfono
            $this->notificarAsesorWhatsApp($expediente, $nombreAnterior, $nombreNueva);

            // WhatsApp al acreditado para informarle del avance
            $this->notificarAcreditadoWhatsApp($expediente, $nombreNueva);
        }

        // Generar comisión al cerrar expediente y notificar al asesor
        if (
            $expediente->wasChanged('estado') &&
            $expediente->estado === 'cerrado' &&
            $expediente->asesor_id &&
            $expediente->honorarios_monto > 0 &&
            $expediente->honorarios_pagados === true
        ) {
            $existe = Comision::where('expediente_id', $expediente->id)->exists();

            if (! $existe) {
                $porcentaje    = (float) ($expediente->honorarios_porcentaje ?? 0);
                $montoBase     = (float) $expediente->honorarios_monto;
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

            $expediente->asesor?->notify(new ExpedienteCerrado($expediente));

            // WhatsApp de cierre al asesor
            if ($expediente->asesor?->telefono) {
                $folio   = $expediente->folio ?? "#{$expediente->id}";
                $cliente = $expediente->acreditado_nombre;
                WhatsAppService::sendText(
                    $expediente->asesor->telefono,
                    "🎉 *¡Expediente cerrado!*\n\n" .
                    "Folio: *{$folio}*\n" .
                    "Cliente: {$cliente}\n\n" .
                    "La comisión ha sido generada. ¡Felicidades!"
                );
            }
        }
    }

    private function notificarAsesorWhatsApp(Expediente $expediente, string $etapaAnterior, string $etapaNueva): void
    {
        $telefono = $expediente->asesor?->telefono;
        if (! $telefono) return;

        $folio   = $expediente->folio ?? "#{$expediente->id}";
        $cliente = $expediente->acreditado_nombre;

        WhatsAppService::sendText(
            $telefono,
            "📋 *Expediente actualizado*\n\n" .
            "Folio: *{$folio}*\n" .
            "Cliente: {$cliente}\n\n" .
            "Etapa anterior: {$etapaAnterior}\n" .
            "Nueva etapa: *{$etapaNueva}*"
        );
    }

    private function notificarAcreditadoWhatsApp(Expediente $expediente, string $etapaNueva): void
    {
        $telefono = $expediente->acreditado_telefono;
        if (! $telefono) return;

        $folio   = $expediente->folio ?? "#{$expediente->id}";
        $cliente = $expediente->acreditado_nombre;

        WhatsAppService::sendText(
            $telefono,
            "Hola *{$cliente}* 👋\n\n" .
            "Tu trámite *{$folio}* ha avanzado a la etapa:\n" .
            "➡️ *{$etapaNueva}*\n\n" .
            "Si tienes dudas, comunícate con tu asesor."
        );
    }

    private function sincronizarChecklist(Expediente $expediente): void
    {
        if (! $expediente->tipo_tramite_id) return;

        // Fuente primaria: tabla documento_requeridos (catálogo en BD)
        $requeridos = \App\Models\DocumentoRequerido::where('tipo_tramite_id', $expediente->tipo_tramite_id)
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get();

        if ($requeridos->isEmpty()) {
            // Fallback: catálogo hardcodeado en el modelo (legacy)
            $this->sincronizarChecklistLegacy($expediente);
            return;
        }

        $tiposExistentes = $expediente->documentos()->pluck('tipo')->toArray();

        foreach ($requeridos as $req) {
            if (! in_array($req->nombre, $tiposExistentes)) {
                $expediente->documentos()->create([
                    'tipo'    => $req->nombre,
                    'nombre'  => $req->nombre,
                    'seccion' => $req->seccion,
                    'estado'  => 'pendiente',
                ]);
            }
        }
    }

    private function sincronizarChecklistLegacy(Expediente $expediente): void
    {
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
