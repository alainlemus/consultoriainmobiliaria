<?php

namespace App\Filament\Resources\TipoTramiteResource\Pages;

use App\Filament\Resources\TipoTramiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoTramites extends ListRecords
{
    protected static string $resource = TipoTramiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
