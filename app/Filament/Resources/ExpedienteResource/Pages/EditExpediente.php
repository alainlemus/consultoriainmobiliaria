<?php

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Filament\Resources\ExpedienteResource;
use App\Mail\SolicitudTestimonio;
use App\Models\TestimonioToken;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EditExpediente extends EditRecord
{
    protected static string $resource = ExpedienteResource::class;

    protected static array $estadoConfig = [
        'en_proceso' => ['label' => 'En proceso',  'bg' => '#1d4ed8', 'text' => '#eff6ff'],
        'aprobado'   => ['label' => 'Aprobado',    'bg' => '#15803d', 'text' => '#f0fdf4'],
        'firmado'    => ['label' => 'Firmado',      'bg' => '#7e22ce', 'text' => '#faf5ff'],
        'pausado'    => ['label' => 'Pausado',      'bg' => '#b45309', 'text' => '#fffbeb'],
        'cerrado'    => ['label' => 'Cerrado',      'bg' => '#374151', 'text' => '#f9fafb'],
    ];

    public function getHeading(): string | HtmlString
    {
        $folio  = $this->record->folio ?? 'Expediente';
        $estado = $this->record->estado ?? '';
        $cfg    = static::$estadoConfig[$estado] ?? ['label' => ucfirst($estado), 'bg' => '#6b7280', 'text' => '#ffffff'];

        $badge = sprintf(
            '<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;background:%s;color:%s;vertical-align:middle;margin-left:10px;">%s</span>',
            $cfg['bg'],
            $cfg['text'],
            e($cfg['label'])
        );

        return new HtmlString($folio . $badge);
    }

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
