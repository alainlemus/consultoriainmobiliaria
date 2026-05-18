<?php

namespace App\Observers;

use App\Models\Contacto;
use App\Models\User;
use App\Notifications\NuevoProspectoCreado;
use App\Notifications\ProspectoAsignado;

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
        }

        // 2. Notificar a todos los super_admin
        User::role('super_admin')
            ->get()
            ->each(fn (User $admin) => $admin->notify(new NuevoProspectoCreado($contacto)));
    }

    /**
     * Al actualizar: si cambia el asesor_id, notificar al nuevo asesor.
     */
    public function updated(Contacto $contacto): void
    {
        if (
            $contacto->wasChanged('asesor_id') &&
            $contacto->asesor_id !== null
        ) {
            $contacto->asesor?->notify(new ProspectoAsignado($contacto));
        }
    }
}
