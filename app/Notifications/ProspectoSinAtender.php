<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Contacto;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Un prospecto asignado lleva más de un día en «nuevo» sin que nadie lo contacte. */
class ProspectoSinAtender extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Contacto $contacto, public bool $paraAsesor = true) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->paraAsesor && $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $horas  = (int) $this->contacto->created_at->diffInHours(now());
        $asesor = $this->contacto->asesor?->name ?? 'sin asesor';
        $quien  = $this->paraAsesor ? 'Tienes' : "{$asesor} tiene";

        return FilamentNotification::make()
            ->title('⏰ Prospecto sin atender')
            ->body("{$quien} a **{$this->contacto->nombre}** desde hace {$horas} h sin contactar.")
            ->warning()
            ->icon('heroicon-o-clock')
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
        return '⏰ Prospecto sin atender';
    }

    protected function pushBody(): string
    {
        $horas = (int) $this->contacto->created_at->diffInHours(now());
        return "{$this->contacto->nombre} lleva {$horas} h esperando que lo contactes";
    }

    protected function pushData(): array
    {
        return ['screen' => 'prospectos', 'id' => (string) $this->contacto->id];
    }
}
