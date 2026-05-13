<?php

namespace App\Filament\Resources\CoberturaResource\Pages;

use App\Filament\Resources\CoberturaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoberturas extends ListRecords
{
    protected static string $resource = CoberturaResource::class;
    public function getTitle(): string { return 'Cobertura'; }
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nueva zona')];
    }
}
