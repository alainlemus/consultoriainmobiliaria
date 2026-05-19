<?php

namespace App\Observers;

use App\Models\Comision;
use App\Notifications\ComisionGenerada;
use App\Notifications\ComisionPagada;

class ComisionObserver
{
    /**
     * Al crear una comisión: notificar al asesor.
     */
    public function created(Comision $comision): void
    {
        $comision->load('expediente');
        $comision->asesor?->notify(new ComisionGenerada($comision));
    }

    /**
     * Al actualizar: si el estado cambia a 'pagada', notificar al asesor.
     */
    public function updated(Comision $comision): void
    {
        if ($comision->wasChanged('estado') && $comision->estado === 'pagada') {
            $comision->load('expediente');
            $comision->asesor?->notify(new ComisionPagada($comision));
        }
    }
}
