<?php

namespace App\Notifications;

use App\Models\Expediente;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OcrCompletado extends Notification
{
    use Queueable;

    public function __construct(
        private Expediente $expediente,
        private int $camposRellenos
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $folio  = $this->expediente->folio ?? "#{$this->expediente->id}";
        $nombre = $this->expediente->acreditado_nombre ?? '—';

        return FilamentNotification::make()
            ->title('🔍 Análisis IA completado — ' . $folio)
            ->body("Se rellenaron **{$this->camposRellenos} campos** del expediente de {$nombre} con datos extraídos de los PDFs.")
            ->success()
            ->icon('heroicon-o-sparkles')
            ->actions([
                Action::make('ver')
                    ->label('Ver expediente')
                    ->url(url("/admin/expedientes/{$this->expediente->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
