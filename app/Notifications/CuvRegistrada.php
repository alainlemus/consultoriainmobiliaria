<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Al asesor y a los super_admin: ya se registró la CUV del expediente. */
class CuvRegistrada extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Expediente $expediente, public string $por) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $folio = $this->expediente->folio ?? "#{$this->expediente->id}";

        return FilamentNotification::make()
            ->title('🏠 CUV registrada')
            ->body("Expediente **{$folio}** ({$this->expediente->acreditado_nombre}): CUV **{$this->expediente->cuv}**, registrada por {$this->por}. El recurso está en gestión.")
            ->success()
            ->icon('heroicon-o-home-modern')
            ->actions([
                Action::make('ver')->label('Ver expediente')->url(url("/admin/expedientes/{$this->expediente->id}/edit"))->markAsRead(),
            ])
            ->getDatabaseMessage() + ['app' => $this->pushData()];
    }

    protected function pushTitle(): string
    {
        return '🏠 CUV registrada';
    }

    protected function pushBody(): string
    {
        $folio = $this->expediente->folio ?? "#{$this->expediente->id}";
        return "{$folio} · {$this->expediente->acreditado_nombre} · CUV {$this->expediente->cuv}";
    }

    protected function pushData(): array
    {
        return ['screen' => 'expedientes', 'id' => (string) $this->expediente->id];
    }
}
