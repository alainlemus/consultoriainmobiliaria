<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación enviada al asesor cuando el pago del crédito
 * ha sido recibido (paso 20 del proceso FOVISSSTE).
 *
 * Se dispara desde el ExpedienteObserver al marcar pago_recibido = true.
 */
class PagoExpedienteRecibido extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Expediente $expediente) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre ?? '—';
        $fecha   = $this->expediente->fecha_pago_recibido
            ? \Carbon\Carbon::parse($this->expediente->fecha_pago_recibido)->format('d/m/Y')
            : now()->format('d/m/Y');

        return FilamentNotification::make()
            ->title('💰 Pago recibido')
            ->body("El pago del expediente **{$folio}** ({$cliente}) fue recibido el {$fecha}. El trámite ha concluido exitosamente.")
            ->success()
            ->icon('heroicon-o-banknotes')
            ->actions([
                Action::make('ver')
                    ->label('Ver expediente')
                    ->url(url("/admin/expedientes/{$this->expediente->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        $folio = $this->expediente->folio ?? "#{$this->expediente->id}";
        return "💰 Pago recibido — {$folio}";
    }

    protected function pushBody(): string
    {
        $cliente = $this->expediente->acreditado_nombre ?? '—';
        $fecha   = $this->expediente->fecha_pago_recibido
            ? \Carbon\Carbon::parse($this->expediente->fecha_pago_recibido)->format('d/m/Y')
            : now()->format('d/m/Y');
        return "Expediente de {$cliente} cerrado el {$fecha}";
    }

    protected function pushData(): array
    {
        return [
            'screen'        => 'expediente',
            'expediente_id' => $this->expediente->id,
        ];
    }
}
