<?php

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Filament\Resources\ExpedienteResource;
use App\Mail\SolicitudTestimonio;
use App\Models\TestimonioToken;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditExpediente extends EditRecord
{
    protected static string $resource = ExpedienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Solicitar Testimonio ─────────────────────────────────────────
            Action::make('solicitar_testimonio')
                ->label('Solicitar Testimonio')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn () => $this->record->estado === 'cerrado'
                               && $this->record->honorarios_pagados)
                ->requiresConfirmation()
                ->modalHeading('Enviar solicitud de testimonio')
                ->modalDescription(fn () => 'Se enviará un correo a '
                    . ($this->record->acreditado_email ?: '(sin email registrado)')
                    . ' con un enlace personal de un solo uso, válido por 7 días.')
                ->modalSubmitActionLabel('Sí, enviar correo')
                ->action(function () {
                    $expediente = $this->record;

                    // Validar que el expediente tiene email
                    if (! $expediente->acreditado_email) {
                        Notification::make()
                            ->title('Sin correo registrado')
                            ->body('El acreditado no tiene email. Agrégalo antes de enviar.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Verificar si ya existe un token válido (no usado y no expirado)
                    $tokenExistente = TestimonioToken::where('expediente_id', $expediente->id)
                        ->valido()
                        ->first();

                    if ($tokenExistente) {
                        Notification::make()
                            ->title('Ya hay un enlace activo')
                            ->body('Ya se envió un enlace que aún no ha sido usado y no ha expirado. '
                                . 'Expira el ' . $tokenExistente->expires_at->format('d/m/Y H:i') . '.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Generar token y enviar correo
                    $token = TestimonioToken::generar($expediente, auth()->id());

                    Mail::to($expediente->acreditado_email)
                        ->send(new SolicitudTestimonio($token));

                    Notification::make()
                        ->title('Solicitud enviada')
                        ->body('Se envió el enlace a ' . $expediente->acreditado_email
                            . '. Válido por 7 días.')
                        ->success()
                        ->send();
                }),

            // ── Contratos PDF ────────────────────────────────────────────────
            Action::make('contrato_servicios')
                ->label('Contrato de Servicios')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('contratos.prestacion_servicios', $this->record->id))
                ->openUrlInNewTab(),

            Action::make('convenio_honorarios')
                ->label('Convenio de Honorarios')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->url(fn () => route('contratos.convenio_honorarios', $this->record->id))
                ->openUrlInNewTab(),

            Actions\DeleteAction::make(),
        ];
    }
}
