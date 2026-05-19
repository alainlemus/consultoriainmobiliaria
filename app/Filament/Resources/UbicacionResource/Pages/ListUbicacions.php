<?php

namespace App\Filament\Resources\UbicacionResource\Pages;

use App\Filament\Resources\UbicacionResource;
use Filament\Resources\Pages\ListRecords;

class ListUbicacions extends ListRecords
{
    protected static string $resource = UbicacionResource::class;

    protected function getHeaderActions(): array
    {
        return []; // solo lectura, sin crear
    }
}
