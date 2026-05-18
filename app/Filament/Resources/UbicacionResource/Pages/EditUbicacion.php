<?php

namespace App\Filament\Resources\UbicacionResource\Pages;

use App\Filament\Resources\UbicacionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUbicacion extends EditRecord
{
    protected static string $resource = UbicacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
