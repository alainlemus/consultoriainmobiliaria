<?php

namespace App\Notifications;

use App\Channels\PushChannel;
use App\Models\Cita;
use App\Notifications\Concerns\SendsPushNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Avisos de citas a super admin, asesor y acreditado: bandeja (database) + push. */
class CitaNotificacion extends Notification
{
    use Queueable;
    use SendsPushNotification;

    public function __construct(public Cita $cita, public string $tipo, public string $titulo, public string $cuerpo) {}

    public function via(object $notifiable): array
    {
        return array_filter(['database', $this->viaPush() ? PushChannel::class : null]);
    }

    public function toDatabase(object $notifiable): array
    {
        $color = match ($this->tipo) { 'confirmada', 'reagendada' => 'success', 'cancelada' => 'danger', default => 'info' };

        return FilamentNotification::make()
            ->title($this->titulo)
            ->body($this->cuerpo)
            ->color($color)
            ->icon('heroicon-o-calendar-days')
            ->actions([Action::make('ver')->label('Ver cita')->url(url("/admin/citas/{$this->cita->id}/edit"))->markAsRead()])
            ->getDatabaseMessage() + ['app' => $this->pushData()];
    }

    protected function pushTitle(): string { return $this->titulo; }
    protected function pushBody(): string  { return $this->cuerpo; }

    protected function pushData(): array
    {
        return ['screen' => 'cita', 'id' => (string) $this->cita->id];
    }
}
