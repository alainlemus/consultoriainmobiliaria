<?php

namespace App\Filament\Resources\ServicioResource\Pages;

use App\Filament\Resources\ServicioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServicio extends CreateRecord
{
    protected static string $resource = ServicioResource::class;

    public function getTitle(): string { return 'Nuevo servicio'; }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
