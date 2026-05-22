<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Comision;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComisionPagada extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Comision $comision) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
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

    protected function pushTitle(): string
    {
        $monto = '$ ' . number_format($this->comision->monto_comision, 2);
        return "🎉 Comisión pagada: {$monto}";
    }

    protected function pushBody(): string
    {
        $folio   = $this->comision->expediente?->folio ?? "#{$this->comision->expediente_id}";
        $cliente = $this->comision->expediente?->acreditado_nombre ?? '—';
        return "Expediente {$folio} — {$cliente}";
    }

    protected function pushData(): array
    {
        return ['screen' => 'comisiones'];
    }
}
