<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al super_admin cuando un asesor sube un documento desde la app.
 */
class DocumentoSubido extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        public DocumentoExpediente $documento,
        public Expediente $expediente,
    ) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre;
        $tipo    = $this->documento->tipo;

        return FilamentNotification::make()
            ->title('📄 Documento recibido')
            ->body("El asesor subió **{$tipo}** para el expediente {$folio} ({$cliente}).")
            ->info()
            ->icon('heroicon-o-paper-clip')
            ->actions([
                Action::make('ver')
                    ->label('Ver expediente')
                    ->url(url("/admin/expedientes/{$this->expediente->id}/edit"))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        $folio = $this->expediente->folio ?? "#{$this->expediente->id}";
        return "📄 Documento recibido — {$folio}";
    }

    protected function pushBody(): string
    {
        $cliente = $this->expediente->acreditado_nombre;
        $tipo    = $this->documento->tipo;
        return "{$cliente}: {$tipo}";
    }

    protected function pushData(): array
    {
        return [
            'screen'        => 'expedientes',
            'expediente_id' => (string) $this->expediente->id,
        ];
    }
}
