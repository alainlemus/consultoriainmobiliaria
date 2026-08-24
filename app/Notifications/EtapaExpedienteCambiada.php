<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Acreditado;
use App\Models\Expediente;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EtapaExpedienteCambiada extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(
        public Expediente $expediente,
        public string $etapaAnterior,
        public string $etapaNueva,
    ) {}

    public function via(object $notifiable): array
    {
        // El acreditado no tiene bandeja de notificaciones en la app — solo push.
        // 'database' es para el admin/asesor, se lee en Filament.
        $canales = $notifiable instanceof Acreditado ? [] : ['database'];
        return array_filter([...$canales, $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $folio   = $this->expediente->folio ?? "#{$this->expediente->id}";
        $cliente = $this->expediente->acreditado_nombre;

        return FilamentNotification::make()
            ->title('📋 Etapa actualizada')
            ->body("Expediente **{$folio}** ({$cliente}): **{$this->etapaAnterior}** → **{$this->etapaNueva}**")
            ->info()
            ->icon('heroicon-o-arrow-right-circle')
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
        return "📋 Expediente {$folio} actualizado";
    }

    protected function pushBody(): string
    {
        $cliente = $this->expediente->acreditado_nombre;
        return "{$cliente}: {$this->etapaAnterior} → {$this->etapaNueva}";
    }

    protected function pushData(): array
    {
        return ['screen' => 'expedientes', 'id' => (string) $this->expediente->id];
    }
}
