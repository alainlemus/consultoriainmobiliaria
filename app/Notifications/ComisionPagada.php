<?php

namespace App\Notifications;

use App\Models\Comision;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al asesor cuando su comisión es marcada como pagada.
 */
class ComisionPagada extends Notification
{
    use Queueable;

    public function __construct(public Comision $comision) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $monto   = '$ ' . number_format($this->comision->monto_comision, 2);
        $folio   = $this->comision->expediente?->folio ?? "#{$this->comision->expediente_id}";
        $cliente = $this->comision->expediente?->acreditado_nombre ?? '—';

        return FilamentNotification::make()
            ->title('🎉 Comisión pagada')
            ->body("Tu comisión de **{$monto}** por el expediente {$folio} ({$cliente}) ha sido marcada como pagada.")
            ->success()
            ->icon('heroicon-o-currency-dollar')
            ->actions([
                Action::make('ver')
                    ->label('Ver comisiones')
                    ->url(url('/admin/comisions'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
