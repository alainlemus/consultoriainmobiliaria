<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Al asesor: el expediente de su cliente pasó a otro paso del seguimiento. */
class SeguimientoActualizado extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Expediente $expediente, public string $anterior, public string $nuevo) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre;

        return FilamentNotification::make()
            ->title('📋 Seguimiento actualizado')
            ->body("Expediente **{$folio}** ({$cliente}): {$this->anterior} → **{$this->nuevo}**")
            ->info()
            ->icon('heroicon-o-arrow-right-circle')
            ->actions([
                Action::make('ver')->label('Ver expediente')->url(url("/admin/expedientes/{$this->expediente->id}/edit"))->markAsRead(),
            ])
            ->getDatabaseMessage() + ['app' => $this->pushData()];
    }

    protected function pushTitle(): string
    {
        $folio = $this->expediente->folio ?? "#{$this->expediente->id}";
        return "📋 {$folio}: {$this->nuevo}";
    }

    protected function pushBody(): string
    {
        return "{$this->expediente->acreditado_nombre} pasó de «{$this->anterior}» a «{$this->nuevo}»";
    }

    protected function pushData(): array
    {
        return ['screen' => 'expedientes', 'id' => (string) $this->expediente->id];
    }
}
