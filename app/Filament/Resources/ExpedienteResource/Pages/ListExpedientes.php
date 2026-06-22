<?php

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Filament\Resources\ExpedienteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpedientes extends ListRecords
{
    protected static string $resource = ExpedienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crear_desde_carpeta')
                ->label('Nuevo desde carpeta')
                ->icon('heroicon-o-folder-arrow-down')
                ->color('primary')
                ->url(ExpedienteResource::getUrl('crear-desde-carpeta')),

            Actions\CreateAction::make()
                ->label('Nuevo expediente')
                ->icon('heroicon-o-plus'),
        ];
    }
}
