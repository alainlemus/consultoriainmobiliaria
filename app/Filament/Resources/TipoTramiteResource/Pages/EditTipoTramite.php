<?php

namespace App\Filament\Resources\TipoTramiteResource\Pages;

use App\Filament\Resources\TipoTramiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoTramite extends EditRecord
{
    protected static string $resource = TipoTramiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
