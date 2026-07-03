<?php

namespace App\Notifications;

use App\Models\Contacto;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NuevoMensajeContacto extends Notification
{
    use Queueable;

    public function __construct(public Contacto $contacto) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $origen = match ($this->contacto->origen) {
            'app_movil'      => '📱 App móvil',
            'app_acreditado' => '👤 App acreditado',
            'sitio_web'      => '🌐 Sitio web',
            default          => '💻 Panel admin',
        };

        return FilamentNotification::make()
            ->title("Nueva solicitud de asesoría — {$origen}")
            ->body("{$this->contacto->nombre} · {$this->contacto->telefono} · {$this->contacto->servicio}")
            ->icon('heroicon-o-envelope')
            ->iconColor('warning')
            ->actions([
                Action::make('ver')
                    ->label('Ver prospecto')
                    ->url(url("/admin/contactos/{$this->contacto->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
