<?php

namespace App\Filament\Resources\ContratoGeneradoResource\Pages;

use App\Filament\Resources\ContratoGeneradoResource;
use App\Services\ContratoGeneradoPdfService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContratoGenerado extends EditRecord
{
    protected static string $resource = ContratoGeneradoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regenerar')
                ->label('Regenerar contrato (PDF)')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->modalHeading('Regenerar contrato')
                ->modalDescription('Guarda los cambios del formulario y vuelve a generar el PDF con el diseño y orden de la app, usando la plantilla vigente. Reemplaza el PDF actual.')
                ->schema([
                    Select::make('papel')->label('Tamaño de hoja')
                        ->options(['carta' => 'Carta', 'oficio' => 'Oficio'])
                        ->default('carta')->required(),
                ])
                ->action(function (array $data) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    app(ContratoGeneradoPdfService::class)->regenerar($this->record->refresh(), $data['papel']);
                    Notification::make()->title('Contrato regenerado')->success()->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
