<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\EncargoGestor;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisos del ida y vuelta de un encargo de gestor: campana del panel + push en la app.
 * Una sola clase con el texto como parámetro (el flujo tiene muchos avisos parecidos).
 *
 * `tipo` viaja en el push para que la app abra la pantalla correcta:
 * el gestor abre el encargo; el super_admin, el expediente.
 */
class EncargoGestorNotificacion extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        public EncargoGestor $encargo,
        public string $tipo,
        public string $titulo,
        public string $mensaje,
        public bool $paraGestor,
        public string $color = 'info',
    ) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $url = $this->paraGestor
            ? url('/admin/gestor')
            : url("/admin/expedientes/{$this->encargo->expediente_id}/edit");

        return FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->mensaje)
            ->color($this->color)
            ->icon('heroicon-o-building-library')
            ->actions([
                Action::make('ver')
                    ->label($this->paraGestor ? 'Ver mis expedientes' : 'Ver expediente')
                    ->url($url)
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        return $this->titulo;
    }

    protected function pushBody(): string
    {
        return $this->mensaje;
    }

    protected function pushData(): array
    {
        return [
            'screen'        => $this->paraGestor ? 'gestor_encargo' : 'expedientes',
            'tipo'          => $this->tipo,
            'encargo_id'    => (string) $this->encargo->id,
            'expediente_id' => (string) $this->encargo->expediente_id,
        ];
    }
}
