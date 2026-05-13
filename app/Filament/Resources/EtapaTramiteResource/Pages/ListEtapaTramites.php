<?php

namespace App\Filament\Resources\EtapaTramiteResource\Pages;

use App\Filament\Resources\EtapaTramiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEtapaTramites extends ListRecords
{
    protected static string $resource = EtapaTramiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
