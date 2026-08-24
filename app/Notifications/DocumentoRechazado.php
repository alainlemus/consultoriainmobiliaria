<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Acreditado;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al acreditado que un documento fue rechazado y por qué —
 * solo push: el acreditado no tiene bandeja de notificaciones en la app,
 * a diferencia del admin/asesor (canal 'database', leído en Filament).
 */
class DocumentoRechazado extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        public DocumentoExpediente $documento,
        public Expediente $expediente,
        public string $motivo,
    ) {}

    public function via(object $notifiable): array
    {
        // Si algún día esto se dispara también hacia un User (asesor/admin),
        // no tiene sentido de push-only — pero hoy solo se dispara al Acreditado.
        return $notifiable instanceof Acreditado && $this->viaPush() ? [PushChannel::class] : [];
    }

    protected function pushTitle(): string
    {
        return '⚠️ Documento rechazado';
    }

    protected function pushBody(): string
    {
        return "{$this->documento->nombre}: {$this->motivo}";
    }

    protected function pushData(): array
    {
        return [
            'screen'        => 'documentos',
            'expediente_id' => (string) $this->expediente->id,
        ];
    }
}
