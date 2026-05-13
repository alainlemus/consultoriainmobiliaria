<?php

namespace App\Filament\Resources\ProcesoResource\Pages;

use App\Filament\Resources\ProcesoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProceso extends EditRecord
{
    protected static string $resource = ProcesoResource::class;
    public function getTitle(): string { return 'Editar paso: ' . $this->record->titulo; }
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Eliminar paso')];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
