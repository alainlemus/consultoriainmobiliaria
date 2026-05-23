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
        return FilamentNotification::make()
            ->title('Nuevo prospecto del sitio web')
            ->body("{$this->contacto->nombre} · {$this->contacto->telefono}")
            ->icon('heroicon-o-envelope')
            ->iconColor('warning')
            ->actions([
                Action::make('ver')
                    ->label('Ver prospecto')
                    ->url(url('/admin/contactos'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
