<?php

namespace App\Filament\Resources\DocumentoRequeridoResource\Pages;

use App\Filament\Resources\DocumentoRequeridoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentoRequerido extends EditRecord
{
    protected static string $resource = DocumentoRequeridoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
