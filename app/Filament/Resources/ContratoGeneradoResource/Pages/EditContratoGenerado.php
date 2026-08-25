<?php

namespace App\Filament\Resources\ContratoGeneradoResource\Pages;

use App\Filament\Resources\ContratoGeneradoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContratoGenerado extends EditRecord
{
    protected static string $resource = ContratoGeneradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
