<?php

namespace App\Filament\Resources\CoberturaResource\Pages;

use App\Filament\Resources\CoberturaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCobertura extends EditRecord
{
    protected static string $resource = CoberturaResource::class;
    public function getTitle(): string { return 'Editar zona: ' . $this->record->nombre; }
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Eliminar zona')];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
