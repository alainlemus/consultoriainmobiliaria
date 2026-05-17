<?php

namespace App\Observers;

use App\Models\Contacto;
use App\Notifications\ProspectoAsignado;

class ContactoObserver
{
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

    /**
     * Al crear: si ya viene con asesor asignado, notificarlo.
     */
    public function created(Contacto $contacto): void
    {
        if ($contacto->asesor_id) {
            $contacto->asesor?->notify(new ProspectoAsignado($contacto));
        }
    }
}
