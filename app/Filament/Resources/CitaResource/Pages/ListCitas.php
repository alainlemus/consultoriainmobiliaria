<?php

namespace App\Filament\Resources\CitaResource\Pages;

use App\Filament\Resources\CitaResource;
use App\Filament\Widgets\CalendarioCitasWidget;
use Filament\Resources\Pages\ListRecords;

class ListCitas extends ListRecords
{
    protected static string $resource = CitaResource::class;

    protected function getHeaderWidgets(): array
    {
        return [CalendarioCitasWidget::class];
    }
}
