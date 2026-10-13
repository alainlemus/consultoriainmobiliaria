<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Le avisa al asesor (en su app) que un administrador validó un documento de su expediente.
 */
class DocumentoValidado extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        public DocumentoExpediente $documento,
        public Expediente $expediente,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Documento validado')
            ->body($this->pushBody())
            ->success()
            ->getDatabaseMessage();
    }

    private function etiqueta(): string
    {
        $nombre = $this->documento->nombre ?: $this->documento->tipo;

        return $this->documento->notas ? "{$nombre} ({$this->documento->notas})" : $nombre;
    }

    protected function pushTitle(): string
    {
        return 'Documento validado';
    }

    protected function pushBody(): string
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre;

        return "{$this->etiqueta()} del expediente {$folio}" . ($cliente ? " ({$cliente})" : '') . ' ya fue validado.';
    }

    protected function pushData(): array
    {
        return [
            'screen'        => 'expedientes',
            'expediente_id' => (string) $this->expediente->id,
        ];
    }
}
