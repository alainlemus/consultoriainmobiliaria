<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Acreditado;
use App\Models\User;
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
        // Push al acreditado (si usa la app) y al asesor del expediente; el
        // acreditado además lo conserva en su bandeja de la app.
        $canales = $notifiable instanceof Acreditado ? ['database'] : [];
        return array_filter([...$canales, ($notifiable instanceof Acreditado || $notifiable instanceof User) && $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title($this->pushTitle())
            ->body($this->pushBody())
            ->danger()
            ->icon('heroicon-o-x-circle')
            ->getDatabaseMessage() + ['app' => $this->pushData()];
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
