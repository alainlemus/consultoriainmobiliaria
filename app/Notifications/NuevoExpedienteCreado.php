<?php

namespace App\Notifications;

use App\Models\Expediente;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NuevoExpedienteCreado extends Notification
{
    use Queueable;

    public function __construct(public Expediente $expediente) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $asesor  = $this->expediente->asesor?->name ?? 'Sin asesor';
        $folio   = $this->expediente->folio;
        $tipo    = $this->expediente->tipoTramite?->nombre ?? '—';
        $cliente = $this->expediente->acreditado_nombre ?? '—';

        return FilamentNotification::make()
            ->title("Nuevo expediente creado: {$folio}")
            ->body("{$cliente} · {$tipo} · Asesor: {$asesor}")
            ->icon('heroicon-o-folder-plus')
            ->iconColor('success')
            ->actions([
                Action::make('ver')
                    ->label('Ver expediente')
                    ->url(url("/admin/expedientes/{$this->expediente->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
