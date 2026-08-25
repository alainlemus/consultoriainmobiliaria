<?php

namespace App\Filament\Resources\ContratoGeneradoResource\Pages;

use App\Filament\Resources\ContratoGeneradoResource;
use Filament\Resources\Pages\ListRecords;

class ListContratosGenerados extends ListRecords
{
    protected static string $resource = ContratoGeneradoResource::class;

    protected function getHeaderActions(): array
    {
        // Sin "crear" — los contratos solo se generan desde la app.
        return [];
    }
}
