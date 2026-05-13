<?php

namespace App\Filament\Resources\ComisionResource\Pages;

use App\Filament\Resources\ComisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComisiones extends ListRecords
{
    protected static string $resource = ComisionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
