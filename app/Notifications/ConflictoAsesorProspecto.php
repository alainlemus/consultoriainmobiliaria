<?php

namespace App\Notifications;

use App\Models\Contacto;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** A los super_admin: un prospecto con asesor llegó por el QR de otro asesor. */
class ConflictoAsesorProspecto extends Notification
{
    use Queueable;

    public function __construct(public Contacto $contacto) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $actual = $this->contacto->asesor?->name ?? 'sin asesor';
        $qr     = $this->contacto->asesorConflicto?->name ?? 'otro asesor';

        return FilamentNotification::make()
            ->title('⚠️ Conflicto de asesor en un prospecto')
            ->body("**{$this->contacto->nombre}** está asignado a {$actual} pero se registró en la app con el QR de {$qr}. Decide a quién corresponde.")
            ->warning()
            ->icon('heroicon-o-exclamation-triangle')
            ->actions([
                Action::make('resolver')
                    ->label('Resolver')
                    ->url(url('/admin/contactos?tableFilters[conflicto][value]=1'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
