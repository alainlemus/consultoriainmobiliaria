<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\User;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al super_admin cuando un asesor solicita cancelar su cuenta desde la app.
 * Requerimiento de Apple App Store (cuenta debe poder eliminarse/cancelarse).
 */
class CuentaCancelada extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public User $usuario) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('⚠️ Solicitud de cancelación de cuenta')
            ->body("**{$this->usuario->name}** ({$this->usuario->email}) ha solicitado cancelar su cuenta desde la app. La cuenta ha sido desactivada automáticamente. Puedes reactivarla desde el panel de Usuarios si fue un error.")
            ->warning()
            ->icon('heroicon-o-user-minus')
            ->actions([
                \Filament\Actions\Action::make('ver')
                    ->label('Ver usuario')
                    ->url(url("/admin/users/{$this->usuario->id}/edit"))
                    ->markAsRead(),
            ])
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
