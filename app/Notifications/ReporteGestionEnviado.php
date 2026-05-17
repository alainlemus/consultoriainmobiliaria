<?php

namespace App\Notifications;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación en el panel Filament para avisar que el reporte fue enviado por email.
 */
class ReporteGestionEnviado extends Notification
{
    use Queueable;

    public function __construct(
        public string $tipo,
        public string $periodo,
        public int    $destinatarios,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $tipoLabel = match($this->tipo) {
            'diario'   => 'Diario',
            'semanal'  => 'Semanal',
            'mensual'  => 'Mensual',
            default    => ucfirst($this->tipo),
        };

        return FilamentNotification::make()
            ->title("📊 Reporte {$tipoLabel} enviado")
            ->body("El reporte de gestión **{$tipoLabel}** ({$this->periodo}) fue enviado a {$this->destinatarios} " . ($this->destinatarios === 1 ? 'correo' : 'correos') . " correctamente.")
            ->success()
            ->icon('heroicon-o-envelope-open')
            ->getDatabaseMessage();
    }
}
