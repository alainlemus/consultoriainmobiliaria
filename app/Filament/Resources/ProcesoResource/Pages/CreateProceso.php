<?php

namespace App\Filament\Resources\ProcesoResource\Pages;

use App\Filament\Resources\ProcesoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProceso extends CreateRecord
{
    protected static string $resource = ProcesoResource::class;
    public function getTitle(): string { return 'Nuevo paso del proceso'; }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
