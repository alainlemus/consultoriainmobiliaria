<?php

namespace App\Filament\Resources\FotoClienteResource\Pages;

use App\Filament\Resources\FotoClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFotoCliente extends EditRecord
{
    protected static string $resource = FotoClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
