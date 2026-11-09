<?php

namespace App\Filament\Resources\CodigoQrResource\Pages;

use App\Filament\Resources\CodigoQrResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCodigoQr extends CreateRecord
{
    protected static string $resource = CodigoQrResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
