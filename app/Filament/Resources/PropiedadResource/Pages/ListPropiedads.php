<?php

namespace App\Filament\Resources\PropiedadResource\Pages;

use App\Filament\Resources\PropiedadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPropiedads extends ListRecords
{
    protected static string $resource = PropiedadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
