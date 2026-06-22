<?php

namespace App\Filament\Resources\AnuncioResource\Pages;

use App\Filament\Resources\AnuncioResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAnuncios extends ListRecords
{
    protected static string $resource = AnuncioResource::class;

    protected function getHeaderActions(): array
    {
        return [];  // Los anuncios se crean solo desde la app móvil
    }
}
