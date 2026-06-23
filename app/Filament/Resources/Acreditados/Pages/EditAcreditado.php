<?php

namespace App\Filament\Resources\Acreditados\Pages;

use App\Filament\Resources\Acreditados\AcreditadoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcreditado extends EditRecord
{
    protected static string $resource = AcreditadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar cuenta')
                ->requiresConfirmation()
                ->modalDescription('Se eliminará permanentemente la cuenta del acreditado. Los expedientes vinculados no se borran.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
