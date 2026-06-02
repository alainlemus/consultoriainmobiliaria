<?php

namespace App\Observers;

use App\Models\Contacto;
use App\Models\User;
use App\Notifications\NuevoProspectoCreado;
use App\Notifications\ProspectoAsignado;
use App\Services\WhatsAppService;

class ContactoObserver
{
    /**
     * Al crear: notificar al asesor asignado (si hay) y a todos los super_admin.
     */
    public function created(Contacto $contacto): void
    {
        // 1. Notificar al asesor asignado
        if ($contacto->asesor_id) {
            $contacto->asesor?->notify(new ProspectoAsignado($contacto));

            // WhatsApp al asesor con datos del nuevo prospecto
            $this->notificarAsesorNuevoProspecto($contacto);
        }

        // 2. Notificar a todos los super_admin
        User::role('super_admin')
            ->get()
            ->each(fn (User $admin) => $admin->notify(new NuevoProspectoCreado($contacto)));
    }

    /**
     * Al actualizar: sincronizar estado interno con estado_prospecto
     * y notificar al asesor si cambia la asignación.
     */
    public function updated(Contacto $contacto): void
    {
        // Sincronizar estado interno con estado_prospecto
        if ($contacto->wasChanged('estado_prospecto')) {
            $map = [
                'nuevo'             => 'nuevo',
                'contactado'        => 'en_proceso',
                'precalificado'     => 'en_proceso',
                'pendiente_cierre'  => 'en_proceso',
                'contrato_firmado'  => 'en_proceso',
                'convertido'        => 'atendido',
                'descartado'        => 'atendido',
            ];
            $nuevoEstado = $map[$contacto->estado_prospecto] ?? 'en_proceso';
            // Actualizar sin disparar eventos para evitar loop
            Contacto::withoutEvents(fn () => $contacto->updateQuietly(['estado' => $nuevoEstado]));
        }

        // Notificar al asesor si cambia la asignación
        if ($contacto->wasChanged('asesor_id') && $contacto->asesor_id !== null) {
            $contacto->asesor?->notify(new ProspectoAsignado($contacto));

            // WhatsApp al nuevo asesor asignado
            $this->notificarAsesorNuevoProspecto($contacto);
        }
    }

    private function notificarAsesorNuevoProspecto(Contacto $contacto): void
    {
        $telefono = $contacto->asesor?->telefono;
        if (! $telefono) return;

        $nombre  = trim("{$contacto->nombre} {$contacto->apellidos}");
        $celular = $contacto->celular ?? $contacto->telefono ?? '—';
        $origen  = $contacto->origen ?? '—';

        WhatsAppService::sendText(
            $telefono,
            "🏠 *Nuevo prospecto asignado*\n\n" .
            "Nombre: *{$nombre}*\n" .
            "Teléfono: {$celular}\n" .
            "Origen: {$origen}\n\n" .
            "Entra al CRM para ver todos los detalles."
        );
    }
}
