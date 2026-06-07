<?php

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Filament\Resources\ExpedienteResource;
use App\Mail\SolicitudTestimonio;
use App\Models\EtapaTramite;
use App\Models\SeguimientoExpediente;
use App\Models\TestimonioToken;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EditExpediente extends EditRecord
{
    protected static string $resource = ExpedienteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $pct   = floatval($data['honorarios_porcentaje'] ?? 0);
        $total = floatval($data['monto_total_estimado']  ?? 0);

        if ($pct > 0 && $total > 0) {
            $data['honorarios_monto'] = round($total * $pct / 100, 2);
        }

        return $data;
    }

    protected static array $estadoConfig = [
        'en_proceso' => ['label' => 'En proceso',  'bg' => '#1d4ed8', 'text' => '#eff6ff'],
        'aprobado'   => ['label' => 'Aprobado',    'bg' => '#15803d', 'text' => '#f0fdf4'],
        'firmado'    => ['label' => 'Firmado',      'bg' => '#7e22ce', 'text' => '#faf5ff'],
        'pausado'    => ['label' => 'Pausado',      'bg' => '#b45309', 'text' => '#fffbeb'],
        'cerrado'    => ['label' => 'Cerrado',      'bg' => '#374151', 'text' => '#f9fafb'],
    ];

    public function getHeading(): string | HtmlString
    {
        $folio  = $this->record->folio ?? 'Expediente';
        $estado = $this->record->estado ?? '';
        $cfg    = static::$estadoConfig[$estado] ?? ['label' => ucfirst($estado), 'bg' => '#6b7280', 'text' => '#ffffff'];

        $badgeEstado = sprintf(
            '<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;background:%s;color:%s;vertical-align:middle;margin-left:10px;">%s</span>',
            $cfg['bg'],
            $cfg['text'],
            e($cfg['label'])
        );

        $tipoTramite = $this->record->tipoTramite?->nombre;
        $badgeTipo = $tipoTramite
            ? sprintf(
                '<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;background:#0e7490;color:#ecfeff;vertical-align:middle;margin-left:6px;">%s</span>',
                e($tipoTramite)
            )
            : '';

        $nombre = $this->record->acreditado_nombre;
        $nombreHtml = $nombre
            ? sprintf(
                '<div style="font-size:1.35rem;font-weight:600;color:#ffffff;margin-top:2px;line-height:1.3;">%s</div>',
                e($nombre)
            )
            : '';

        return new HtmlString('<div>' . $folio . $badgeEstado . $badgeTipo . $nombreHtml . '</div>');
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Avanzar etapa (solo super_admin, oculto en última etapa) ─────
            Action::make('avanzar_etapa')
                ->label(function () {
                    $siguiente = $this->siguienteEtapa();
                    return $siguiente
                        ? 'Avanzar → ' . preg_replace('/^\d+\.\s*/', '', $siguiente->nombre)
                        : 'Avanzar etapa';
                })
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(function () {
                    if (! auth()->user()?->hasRole('super_admin')) return false;
                    return $this->siguienteEtapa() !== null;
                })
                ->requiresConfirmation()
                ->modalHeading(fn () => 'Avanzar a: ' . ($this->siguienteEtapa()?->nombre ?? ''))
                ->modalDescription('Se registrará automáticamente en la bitácora de seguimiento.')
                ->modalSubmitActionLabel('Sí, avanzar')
                ->action(function () {
                    $etapaAnterior  = $this->record->etapa;
                    $siguienteEtapa = $this->siguienteEtapa();

                    if (! $siguienteEtapa) {
                        Notification::make()->title('Ya está en la última etapa')->warning()->send();
                        return;
                    }

                    $this->record->update(['etapa_tramite_id' => $siguienteEtapa->id]);

                    SeguimientoExpediente::create([
                        'expediente_id'     => $this->record->id,
                        'usuario_id'        => auth()->id(),
                        'tipo'              => 'cambio_etapa',
                        'descripcion'       => 'Avance de etapa: "' . ($etapaAnterior?->nombre ?? '—') . '" → "' . $siguienteEtapa->nombre . '"',
                        'etapa_anterior_id' => $etapaAnterior?->id,
                        'etapa_nueva_id'    => $siguienteEtapa->id,
                    ]);

                    Notification::make()
                        ->title('Etapa avanzada')
                        ->body('Ahora en: ' . preg_replace('/^\d+\.\s*/', '', $siguienteEtapa->nombre))
                        ->success()
                        ->send();

                    $this->redirect(ExpedienteResource::getUrl('edit', ['record' => $this->record]));
                }),

            // ── Solicitar Testimonio ─────────────────────────────────────────
            Action::make('solicitar_testimonio')
                ->label('Solicitar Testimonio')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn () => $this->record->estado === 'cerrado'
                               && $this->record->honorarios_pagados)
                ->requiresConfirmation()
                ->modalHeading('Enviar solicitud de testimonio')
                ->modalDescription(fn () => 'Se enviará un correo a '
                    . ($this->record->acreditado_email ?: '(sin email registrado)')
                    . ' con un enlace personal de un solo uso, válido por 7 días.')
                ->modalSubmitActionLabel('Sí, enviar correo')
                ->action(function () {
                    $expediente = $this->record;

                    // Validar que el expediente tiene email
                    if (! $expediente->acreditado_email) {
                        Notification::make()
                            ->title('Sin correo registrado')
                            ->body('El acreditado no tiene email. Agrégalo antes de enviar.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Verificar si ya existe un token válido (no usado y no expirado)
                    $tokenExistente = TestimonioToken::where('expediente_id', $expediente->id)
                        ->valido()
                        ->first();

                    if ($tokenExistente) {
                        Notification::make()
                            ->title('Ya hay un enlace activo')
                            ->body('Ya se envió un enlace que aún no ha sido usado y no ha expirado. '
                                . 'Expira el ' . $tokenExistente->expires_at->format('d/m/Y H:i') . '.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Generar token y enviar correo
                    $token = TestimonioToken::generar($expediente, auth()->id());

                    Mail::to($expediente->acreditado_email)
                        ->send(new SolicitudTestimonio($token));

                    Notification::make()
                        ->title('Solicitud enviada')
                        ->body('Se envió el enlace a ' . $expediente->acreditado_email
                            . '. Válido por 7 días.')
                        ->success()
                        ->send();
                }),

            // ── Contratos PDF ────────────────────────────────────────────────
            Action::make('contrato_servicios')
                ->label('Contrato de Servicios')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->modalHeading('Vista previa — Contrato de Servicios')
                ->modalWidth('5xl')
                ->modalSubmitActionLabel('Descargar PDF')
                ->modalContent(fn () => new HtmlString(
                    '<iframe src="' . route('contratos.prestacion_servicios', $this->record->id) . '?preview=1"
                        style="width:100%;height:75vh;border:none;border-radius:4px;"
                        title="Contrato de Servicios">
                    </iframe>'
                ))
                ->action(fn () => redirect()->away(
                    route('contratos.prestacion_servicios', $this->record->id)
                )),

            Action::make('convenio_honorarios')
                ->label('Convenio de Honorarios')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->modalHeading('Vista previa — Convenio de Honorarios')
                ->modalWidth('5xl')
                ->modalSubmitActionLabel('Descargar PDF')
                ->modalContent(fn () => new HtmlString(
                    '<iframe src="' . route('contratos.convenio_honorarios', $this->record->id) . '?preview=1"
                        style="width:100%;height:75vh;border:none;border-radius:4px;"
                        title="Convenio de Honorarios">
                    </iframe>'
                ))
                ->action(fn () => redirect()->away(
                    route('contratos.convenio_honorarios', $this->record->id)
                )),

            Action::make('carta_mandato')
                ->label('Carta Mandato')
                ->icon('heroicon-o-identification')
                ->color('info')
                ->modalHeading('Vista previa — Carta Mandato')
                ->modalWidth('5xl')
                ->modalSubmitActionLabel('Descargar PDF')
                ->modalContent(fn () => new HtmlString(
                    '<iframe src="' . route('contratos.carta_mandato', $this->record->id) . '?preview=1"
                        style="width:100%;height:75vh;border:none;border-radius:4px;"
                        title="Carta Mandato">
                    </iframe>'
                ))
                ->action(fn () => redirect()->away(
                    route('contratos.carta_mandato', $this->record->id)
                )),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                ->requiresConfirmation()
                ->modalHeading('Eliminar expediente')
                ->modalDescription(fn () => new HtmlString(
                    'Para confirmar, escribe el folio <strong>' . e($this->record->folio) . '</strong> en el campo de abajo.'
                ))
                ->modalSubmitActionLabel('Sí, eliminar definitivamente')
                ->form([
                    \Filament\Forms\Components\TextInput::make('folio_confirmacion')
                        ->label('Folio del expediente')
                        ->placeholder($this->record->folio ?? '')
                        ->required(),
                ])
                ->action(function (array $data) {
                    if (($data['folio_confirmacion'] ?? '') !== ($this->record->folio ?? '')) {
                        \Filament\Notifications\Notification::make()
                            ->title('Folio incorrecto')
                            ->body('El folio ingresado no coincide. No se eliminó el expediente.')
                            ->danger()
                            ->send();
                        return;
                    }
                    $this->record->delete();
                    \Filament\Notifications\Notification::make()
                        ->title('Expediente eliminado')
                        ->success()
                        ->send();
                    $this->redirect(ExpedienteResource::getUrl('index'));
                }),
        ];
    }

    // ── Helper: obtiene la siguiente etapa del trámite ───────────────────────
    private function siguienteEtapa(): ?EtapaTramite
    {
        $etapaActual = $this->record->etapa;

        return EtapaTramite::where('tipo_tramite_id', $this->record->tipo_tramite_id)
            ->where('orden', '>', $etapaActual?->orden ?? 0)
            ->orderBy('orden')
            ->first();
    }
}
