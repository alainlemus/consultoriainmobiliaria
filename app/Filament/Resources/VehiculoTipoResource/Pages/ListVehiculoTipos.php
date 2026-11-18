<?php

namespace App\Filament\Resources\VehiculoTipoResource\Pages;

use App\Filament\Resources\VehiculoTipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehiculoTipos extends ListRecords
{
    protected static string $resource = VehiculoTipoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo tipo')];
    }
}
