<?php

namespace App\Notifications;

use App\Models\Expediente;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al asesor cuando la etapa de su expediente cambia.
 */
class EtapaExpedienteCambiada extends Notification
{
    use Queueable;

    public function __construct(
        public Expediente $expediente,
        public string $etapaAnterior,
        public string $etapaNueva,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre;

        return FilamentNotification::make()
            ->title('📋 Etapa actualizada')
            ->body("Expediente **{$folio}** ({$cliente}): **{$this->etapaAnterior}** → **{$this->etapaNueva}**")
            ->info()
            ->icon('heroicon-o-arrow-right-circle')
            ->actions([
                Action::make('ver')
                    ->label('Ver expediente')
                    ->url(url("/admin/expedientes/{$this->expediente->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
