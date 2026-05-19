<?php

namespace App\Notifications;

use App\Models\Contacto;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al asesor cuando se le asigna un nuevo prospecto.
 */
class ProspectoAsignado extends Notification
{
    use Queueable;

    public function __construct(public Contacto $contacto) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
