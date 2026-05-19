<?php

namespace App\Notifications;

use App\Models\Expediente;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al asesor cuando su expediente es marcado como cerrado.
 */
class ExpedienteCerrado extends Notification
{
    use Queueable;

    public function __construct(public Expediente $expediente) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre;
        $monto   = $this->expediente->honorarios_monto
            ? '$ ' . number_format($this->expediente->honorarios_monto, 2)
            : null;

        $body = "Expediente **{$folio}** — {$cliente} ha sido cerrado exitosamente.";
        if ($monto) {
            $body .= " Honorarios: {$monto}.";
        }

        return FilamentNotification::make()
            ->title('✅ Expediente cerrado')
            ->body($body)
            ->success()
            ->icon('heroicon-o-check-badge')
            ->actions([
                Action::make('ver')
                    ->label('Ver expediente')
                    ->url(url("/admin/expedientes/{$this->expediente->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
