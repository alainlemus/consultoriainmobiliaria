<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Aviso al acreditado sobre su trámite: push y bandeja de la app (database).
 * El título y el texto los decide quien lo manda (seguimiento, CUV…).
 */
class AvisoTramiteAcreditado extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        public Expediente $expediente,
        public string $titulo,
        public string $cuerpo,
        public string $pantalla = 'tramite',
    ) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->cuerpo)
            ->info()
            ->icon('heroicon-o-clipboard-document-check')
            ->getDatabaseMessage() + ['app' => $this->pushData()];
    }

    protected function pushTitle(): string
    {
        return $this->titulo;
    }

    protected function pushBody(): string
    {
        return $this->cuerpo;
    }

    protected function pushData(): array
    {
        return ['screen' => $this->pantalla, 'expediente_id' => (string) $this->expediente->id];
    }
}
