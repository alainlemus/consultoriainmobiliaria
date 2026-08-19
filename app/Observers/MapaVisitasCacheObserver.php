<?php

namespace App\Observers;

use App\Filament\Pages\MapaVisitas;

/**
 * Invalida el caché de /admin/mapa-visitas cuando cambia cualquier dato
 * que la página muestra (ubicaciones, anuncios o sus fotos).
 * Se registra para varios modelos en AppServiceProvider.
 */
class MapaVisitasCacheObserver
{
    public function created(): void
    {
        MapaVisitas::bumpCache();
    }

    public function updated(): void
    {
        MapaVisitas::bumpCache();
    }

    public function deleted(): void
    {
        MapaVisitas::bumpCache();
    }
}
