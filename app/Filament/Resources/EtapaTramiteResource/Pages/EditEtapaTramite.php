<?php

namespace App\Filament\Resources\EtapaTramiteResource\Pages;

use App\Filament\Resources\EtapaTramiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEtapaTramite extends EditRecord
{
    protected static string $resource = EtapaTramiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
