<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\VehiculoReserva;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Avisos del préstamo de vehículos al super admin y al asesor: bandeja (database) + push. */
class VehiculoNotificacion extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public VehiculoReserva $reserva, public string $tipo, public string $titulo, public string $cuerpo) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $color = match ($this->tipo) {
            'autorizada', 'entregada', 'subio' => 'success',
            'rechazada', 'cancelada' => 'danger',
            'solicitada' => 'warning',
            default => 'info',
        };

        return FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->cuerpo)
            ->color($color)
            ->icon('heroicon-o-key')
            ->actions([Action::make('ver')->label('Ver reserva')->url(url("/admin/vehiculo-reservas/{$this->reserva->id}"))->markAsRead()])
            ->getDatabaseMessage() + ['app' => $this->pushData()];
    }

    protected function pushTitle(): string
    {
        return $this->titulo;
    }

    protected function pushBody(): string
    {
        return $this->cuerpo;
    }

    protected function pushData(): array
    {
        return ['screen' => 'vehiculo_reserva', 'id' => (string) $this->reserva->id];
    }
}
