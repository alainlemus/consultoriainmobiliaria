<?php

namespace App\Filament\Resources\ContactoResource\Actions;

use App\Mail\EmailMasivoProspecto;
use App\Models\Contacto;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EnviarEmailMasivoBulkAction extends BulkAction
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'enviar_email_masivo')
            ->label('Enviar email')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->visible(fn () => Auth::user()?->hasRole('super_admin'))
            ->modalHeading('Enviar email a prospectos seleccionados')
            ->modalDescription(fn (Collection $records) => self::buildModalDescription($records))
            ->modalIcon('heroicon-o-envelope')
            ->form([
                Placeholder::make('variables_hint')
                    ->label('Variables disponibles')
                    ->content(new \Illuminate\Support\HtmlString(
                        '<span class="text-sm text-gray-500">'
                        . 'Puedes usar: <code class="bg-gray-100 px-1 rounded">{nombre}</code>, '
                        . '<code class="bg-gray-100 px-1 rounded">{telefono}</code>, '
                        . '<code class="bg-gray-100 px-1 rounded">{servicio}</code>'
                        . '</span>'
                    )),
                TextInput::make('asunto')
                    ->label('Asunto del email')
                    ->required()
                    ->maxLength(150)
                    ->placeholder('Ej: Tenemos una oferta especial para ti, {nombre}')
                    ->validationMessages([
                        'required' => 'El asunto es obligatorio.',
                        'max'      => 'El asunto no puede superar los 150 caracteres.',
                    ]),
                Textarea::make('cuerpo')
                    ->label('Mensaje')
                    ->required()
                    ->rows(8)
                    ->placeholder(
                        "Hola {nombre},\n\n"
                        . "Queremos informarte sobre nuestros servicios de asesoría FOVISSSTE.\n\n"
                        . "Si tienes dudas, puedes contactarnos al {telefono}.\n\n"
                        . "Saludos,\nEl equipo de ConsultoríaInmobiliaria"
                    )
                    ->helperText('El mensaje se enviará personalizado a cada prospecto con sus datos.')
                    ->validationMessages([
                        'required' => 'El mensaje es obligatorio.',
                    ]),
            ])
            ->action(function (Collection $records, array $data): void {
                $sinEmail = $records->whereNull('email')->count();
                $conEmail = $records->whereNotNull('email');

                foreach ($conEmail as $contacto) {
                    Mail::to($contacto->email)
                        ->queue(new EmailMasivoProspecto(
                            contacto: $contacto,
                            asunto: $data['asunto'],
                            cuerpo: $data['cuerpo'],
                        ));
                }

                $enviados = $conEmail->count();
                $body = "Se encolaron {$enviados} emails correctamente.";
                if ($sinEmail > 0) {
                    $body .= " ({$sinEmail} " . ($sinEmail === 1 ? 'prospecto no tenía' : 'prospectos no tenían') . " correo registrado y " . ($sinEmail === 1 ? 'fue omitido' : 'fueron omitidos') . ")";
                }

                Notification::make()
                    ->title('Emails encolados')
                    ->body($body)
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function buildModalDescription(Collection $records): string
    {
        $total    = $records->count();
        $conEmail = $records->whereNotNull('email')->count();
        $sinEmail = $total - $conEmail;

        $msg = "Has seleccionado {$total} " . ($total === 1 ? 'prospecto' : 'prospectos') . ". ";
        $msg .= "{$conEmail} " . ($conEmail === 1 ? 'tiene' : 'tienen') . " correo registrado";
        if ($sinEmail > 0) {
            $msg .= " y {$sinEmail} " . ($sinEmail === 1 ? 'será omitido' : 'serán omitidos') . " por no tener email";
        }
        $msg .= ".";

        return $msg;
    }
}
