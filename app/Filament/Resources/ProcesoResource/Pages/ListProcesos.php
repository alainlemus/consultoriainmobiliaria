<?php

namespace App\Filament\Resources\ProcesoResource\Pages;

use App\Filament\Resources\ProcesoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcesos extends ListRecords
{
    protected static string $resource = ProcesoResource::class;
    public function getTitle(): string { return 'Proceso paso a paso'; }
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nuevo paso')];
    }
}
