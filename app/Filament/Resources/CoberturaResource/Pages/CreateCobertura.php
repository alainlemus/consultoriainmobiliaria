<?php

namespace App\Filament\Resources\CoberturaResource\Pages;

use App\Filament\Resources\CoberturaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCobertura extends CreateRecord
{
    protected static string $resource = CoberturaResource::class;
    public function getTitle(): string { return 'Nueva zona de cobertura'; }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
