<?php

namespace App\Filament\Resources\FotoClienteResource\Pages;

use App\Filament\Resources\FotoClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFotoClientes extends ListRecords
{
    protected static string $resource = FotoClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Subir foto'),
        ];
    }
}
