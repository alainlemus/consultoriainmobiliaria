<?php

namespace App\Filament\Resources\VehiculoResource\Pages;

use App\Filament\Resources\VehiculoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVehiculos extends ListRecords
{
    protected static string $resource = VehiculoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nuevo vehículo')];
    }
}
