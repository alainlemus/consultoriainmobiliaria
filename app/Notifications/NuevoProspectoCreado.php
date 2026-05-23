<?php

namespace App\Notifications;

use App\Models\Contacto;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al super_admin cuando se crea un nuevo prospecto,
 * ya sea desde la app móvil (API) o desde el panel Filament de un asesor.
 */
class NuevoProspectoCreado extends Notification
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
        $servicio = $this->contacto->servicio ?? 'No especificado';
        $asesor   = $this->contacto->asesor?->name ?? 'Sin asesor';
        $origen   = $this->contacto->origen === 'app_movil' ? '📱 App móvil' : '💻 Panel admin';

        return FilamentNotification::make()
            ->title('👤 Nuevo prospecto registrado')
            ->body("**{$nombre}** — {$servicio} | Asesor: {$asesor} | Origen: {$origen}")
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
