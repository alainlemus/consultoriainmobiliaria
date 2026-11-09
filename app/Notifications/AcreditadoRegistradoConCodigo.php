<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Acreditado;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Al asesor: alguien creó su cuenta en la app con tu código de QR. Todavía
 * no pide asesoría; conviene contactarlo antes de que se enfríe.
 */
class AcreditadoRegistradoConCodigo extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Acreditado $acreditado) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $tel    = $this->acreditado->telefono ?: 'sin teléfono';
        $codigo = $this->acreditado->codigoQr?->codigo ?? '—';

        return FilamentNotification::make()
            ->title('📱 Se registró con tu código QR')
            ->body("**{$this->acreditado->name}** ({$tel}) creó su cuenta en la app con el código {$codigo}. Aún no pide asesoría: contáctalo.")
            ->success()
            ->icon('heroicon-o-qr-code')
            ->actions([
                Action::make('ver')
                    ->label('Ver acreditados')
                    ->url(url('/admin/acreditados'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        return '📱 Se registró con tu código QR';
    }

    protected function pushBody(): string
    {
        $tel = $this->acreditado->telefono ? " · {$this->acreditado->telefono}" : '';
        return "{$this->acreditado->name}{$tel} — contáctalo antes de que se enfríe";
    }

    protected function pushData(): array
    {
        return ['screen' => 'registros_qr', 'id' => (string) $this->acreditado->id];
    }

    /** Para pruebas: a qué pantalla lleva el push. */
    public function pushDataPublic(): array
    {
        return $this->pushData();
    }
}
