<?php

namespace App\Notifications;

use App\Models\Contacto;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProspectoIniciandoGestion extends Notification
{
    use Queueable;

    public function __construct(
        public Contacto $contacto,
        public string $modalidadLabel,
        public ?string $notas,
        public string $asesorNombre,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Gestión iniciada con prospecto')
            ->body("**{$this->contacto->nombre}** — {$this->contacto->telefono}\nModalidad: {$this->modalidadLabel}" . ($this->notas ? "\nNotas: {$this->notas}" : '') . "\nEnviado por: {$this->asesorNombre}")
            ->icon('heroicon-o-user-plus')
            ->iconColor('warning')
            ->actions([
                Action::make('ver')
                    ->label('Ver prospecto')
                    ->url(url('/admin/contactos/' . $this->contacto->id . '/edit'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'contacto_id' => $this->contacto->id,
            'contacto_nombre' => $this->contacto->nombre,
            'telefono' => $this->contacto->telefono,
            'modalidad' => $this->modalidadLabel,
            'notas' => $this->notas,
            'asesor' => $this->asesorNombre,
        ];
    }
}