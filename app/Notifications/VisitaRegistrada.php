<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Ubicacion;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifica al super_admin cuando un asesor registra una visita desde la app.
 */
class VisitaRegistrada extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Ubicacion $ubicacion) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $asesor    = $this->ubicacion->user?->name ?? 'Un asesor';
        $tipo      = $this->ubicacion->tipo === 'propiedad' ? 'Propiedad' : 'Visita a cliente';
        $lugar     = collect([$this->ubicacion->municipio, $this->ubicacion->estado])
            ->filter()->implode(', ');
        $contacto  = $this->ubicacion->contacto?->nombre;

        $body = "**{$asesor}** registró: {$tipo}";
        if ($lugar) $body .= " en {$lugar}";
        if ($contacto) $body .= " — cliente: {$contacto}";

        return FilamentNotification::make()
            ->title('📍 Visita registrada')
            ->body($body)
            ->info()
            ->icon('heroicon-o-map-pin')
            ->actions([
                Action::make('ver')
                    ->label('Ver visitas')
                    ->url(url('/admin/ubicacions'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function pushTitle(): string
    {
        $asesor = $this->ubicacion->user?->name ?? 'Asesor';
        return "📍 {$asesor} registró una visita";
    }

    protected function pushBody(): string
    {
        $tipo  = $this->ubicacion->tipo === 'propiedad' ? 'Propiedad' : 'Visita a cliente';
        $lugar = collect([$this->ubicacion->municipio, $this->ubicacion->estado])
            ->filter()->implode(', ');
        return $lugar ? "{$tipo} — {$lugar}" : $tipo;
    }

    protected function pushData(): array
    {
        return ['screen' => 'visitas', 'id' => (string) $this->ubicacion->id];
    }
}
