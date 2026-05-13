<?php

namespace App\Filament\Resources\TestimonioResource\Pages;

use App\Filament\Resources\TestimonioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestimonios extends ListRecords
{
    protected static string $resource = TestimonioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
