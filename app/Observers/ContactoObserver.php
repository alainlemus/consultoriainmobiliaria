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
     * Antes de crear: auto-completar fecha_primer_contacto si no se envió.
     */
    public function creating(Contacto $contacto): void
    {
        if (empty($contacto->fecha_primer_contacto)) {
            $contacto->fecha_primer_contacto = now()->toDateString();
        }
    }

    /**
     * Al crear: notificar al asesor asignado (si hay) y a todos los super_admin.
     */
    public function created(Contacto $contacto): void
    {
        // 1. WhatsApp de bienvenida al prospecto
        $this->notificarBienvenidaProspecto($contacto);

        // 2. Notificar al asesor asignado (si hay)
        if ($contacto->asesor_id) {
            $contacto->asesor?->notify(new ProspectoAsignado($contacto));
            $this->notificarAsesorNuevoProspecto($contacto);
        }

        // 3. WhatsApp a super_admins cuando viene del sitio web
        if ($contacto->origen === 'sitio_web') {
            $this->notificarAdminsNuevoProspectoWeb($contacto);
        }

        // 4. Notificación in-app a todos los super_admin
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

    private function notificarBienvenidaProspecto(Contacto $contacto): void
    {
        $telefono = $contacto->celular ?? $contacto->telefono ?? null;
        if (! $telefono) return;

        $nombre = trim("{$contacto->nombre} {$contacto->apellidos}");

        WhatsAppService::sendText(
            $telefono,
            "¡Hola *{$nombre}*! 👋\n\n" .
            "Gracias por contactarnos. Hemos recibido tu información y un asesor se pondrá en contacto contigo a la brevedad.\n\n" .
            "Si tienes alguna duda, no dudes en escribirnos. 🏠"
        );
    }

    private function notificarAdminsNuevoProspectoWeb(Contacto $contacto): void
    {
        $nombre   = trim("{$contacto->nombre} {$contacto->apellidos}");
        $celular  = $contacto->celular ?? $contacto->telefono ?? '—';
        $servicio = $contacto->servicio ?? '—';

        User::role('super_admin')->get()->each(function (User $admin) use ($nombre, $celular, $servicio) {
            if (! $admin->telefono) return;
            WhatsAppService::sendText(
                $admin->telefono,
                "🌐 *Nuevo prospecto del sitio web*\n\n" .
                "Nombre: *{$nombre}*\n" .
                "Teléfono: {$celular}\n" .
                "Servicio: {$servicio}\n\n" .
                "Entra al CRM para asignarlo a un asesor."
            );
        });
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
