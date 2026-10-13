<?php

namespace App\Filament\Resources\ContratoGeneradoResource\Pages;

use App\Filament\Resources\ContratoGeneradoResource;
use App\Services\ContratoGeneradoPdfService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContratoGenerado extends EditRecord
{
    protected static string $resource = ContratoGeneradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->accionRegenerar('carta', 'Regenerar en Carta', 'primary'),
            $this->accionRegenerar('oficio', 'Regenerar en Oficio', 'gray'),
            Actions\DeleteAction::make(),
        ];
    }

    private function accionRegenerar(string $papel, string $label, string $color): Actions\Action
    {
        return Actions\Action::make("regenerar_{$papel}")
            ->label($label)
            ->icon('heroicon-o-arrow-path')
            ->color($color)
            ->requiresConfirmation()
            ->modalHeading($label)
            ->modalDescription('Guarda los cambios del formulario y vuelve a generar el PDF con el diseño de la app. Reemplaza el PDF actual.')
            ->action(function () use ($papel) {
                $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                app(ContratoGeneradoPdfService::class)->regenerar($this->record->refresh(), $papel);
                Notification::make()->title('Contrato regenerado en ' . ($papel === 'oficio' ? 'Oficio' : 'Carta'))->success()->send();
            });
    }
}
