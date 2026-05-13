<?php

namespace App\Filament\Resources\ServicioResource\Pages;

use App\Filament\Resources\ServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServicio extends EditRecord
{
    protected static string $resource = ServicioResource::class;

    public function getTitle(): string { return 'Editar servicio: ' . $this->record->titulo; }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Eliminar servicio')];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
