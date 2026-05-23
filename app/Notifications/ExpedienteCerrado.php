<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpedienteCerrado extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Expediente $expediente) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
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

    protected function pushTitle(): string
    {
        $folio = $this->expediente->folio ?? "#{$this->expediente->id}";
        return "✅ Expediente {$folio} cerrado";
    }

    protected function pushBody(): string
    {
        $cliente = $this->expediente->acreditado_nombre;
        $monto   = $this->expediente->honorarios_monto
            ? ' — Honorarios: $ ' . number_format($this->expediente->honorarios_monto, 2)
            : '';
        return "{$cliente}{$monto}";
    }

    protected function pushData(): array
    {
        return ['screen' => 'expedientes', 'id' => (string) $this->expediente->id];
    }
}
