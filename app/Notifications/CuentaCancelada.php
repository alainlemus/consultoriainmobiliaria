<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Notifica al super_admin cuando un asesor o acreditado solicita cancelar su cuenta.
 * Requerimiento de Apple App Store y Google Play Store.
 */
class CuentaCancelada extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Model $usuario) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $esAcreditado = $this->usuario instanceof \App\Models\Acreditado;
        $tipo         = $esAcreditado ? 'acreditado' : 'asesor';

        return FilamentNotification::make()
            ->title("⚠️ Solicitud de cancelación de cuenta ({$tipo})")
            ->body("**{$this->usuario->name}** ({$this->usuario->email}) ha solicitado cancelar su cuenta desde la app. La cuenta fue desactivada automáticamente.")
            ->warning()
            ->icon('heroicon-o-user-minus')
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        return '⚠️ Cancelación de cuenta solicitada';
    }

    protected function pushBody(): string
    {
        return "{$this->usuario->name} solicitó cancelar su cuenta";
    }

    protected function pushData(): array
    {
        return ['screen' => 'users', 'id' => (string) $this->usuario->id];
    }
}
