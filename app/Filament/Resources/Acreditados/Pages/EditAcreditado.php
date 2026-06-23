<?php

namespace App\Filament\Resources\Acreditados\Pages;

use App\Filament\Resources\Acreditados\AcreditadoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAcreditado extends EditRecord
{
    protected static string $resource = AcreditadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
