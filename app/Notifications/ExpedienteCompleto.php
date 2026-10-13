<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Le avisa al asesor que el administrador validó TODOS los documentos de su expediente.
 */
class ExpedienteCompleto extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Expediente $expediente) {}

    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Expediente completo')
            ->body($this->pushBody())
            ->success()
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        return 'Expediente completo';
    }

    protected function pushBody(): string
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre;

        return "El expediente {$folio}" . ($cliente ? " ({$cliente})" : '') . ' está completo: todos sus documentos fueron validados.';
    }

    protected function pushData(): array
    {
        return [
            'screen'        => 'expedientes',
            'expediente_id' => (string) $this->expediente->id,
        ];
    }
}
