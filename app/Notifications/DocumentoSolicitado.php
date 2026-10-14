<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Acreditado;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Un administrador pidió al acreditado un documento faltante. Push al acreditado
 * (abre Mi Trámite, donde está el aviso) y al asesor del expediente (abre el expediente).
 */
class DocumentoSolicitado extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        public DocumentoExpediente $documento,
        public Expediente $expediente,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->viaPush() ? [PushChannel::class] : [];
    }

    public function toPush(object $notifiable): void
    {
        $esAcreditado = $notifiable instanceof Acreditado;

        PushService::sendToUser(
            $notifiable,
            $this->pushTitle(),
            $this->pushBody(),
            [
                'screen'        => $esAcreditado ? 'tramite' : 'expedientes',
                'expediente_id' => (string) $this->expediente->id,
            ],
        );
    }

    protected function pushTitle(): string
    {
        return '📄 Falta un documento';
    }

    protected function pushBody(): string
    {
        return (string) $this->documento->solicitud_nota;
    }
}
