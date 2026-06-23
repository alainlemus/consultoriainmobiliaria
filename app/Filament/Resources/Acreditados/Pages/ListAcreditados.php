<?php

namespace App\Filament\Resources\Acreditados\Pages;

use App\Filament\Resources\Acreditados\AcreditadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAcreditados extends ListRecords
{
    protected static string $resource = AcreditadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
