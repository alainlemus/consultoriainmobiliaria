<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Contacto;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProspectoAsignado extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Contacto $contacto) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $nombre   = $this->contacto->nombre;
        $telefono = $this->contacto->telefono;
        $servicio = ucfirst($this->contacto->servicio ?? 'No especificado');

        return FilamentNotification::make()
            ->title('👤 Nuevo prospecto asignado')
            ->body("Se te ha asignado el prospecto **{$nombre}** ({$telefono}) — Interés: {$servicio}.")
            ->info()
            ->icon('heroicon-o-user-plus')
            ->actions([
                Action::make('ver')
                    ->label('Ver prospecto')
                    ->url(url("/admin/contactos/{$this->contacto->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        return '👤 Nuevo prospecto asignado';
    }

    protected function pushBody(): string
    {
        $nombre   = $this->contacto->nombre;
        $servicio = ucfirst($this->contacto->servicio ?? 'No especificado');
        return "{$nombre} — {$servicio}";
    }

    protected function pushData(): array
    {
        return ['screen' => 'prospectos', 'id' => (string) $this->contacto->id];
    }
}
