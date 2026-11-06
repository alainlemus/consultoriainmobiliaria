<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;
use App\Models\Expediente;
use App\Models\User;
use App\Services\EncargoGestorService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/**
 * Pestaña "Gestores" del expediente (solo super_admin): enviar el expediente a
 * uno o varios gestores, atender sus observaciones y reenviar, y ver el historial.
 */
class EncargosGestorRelationManager extends RelationManager
{
    protected static string $relationship = 'encargosGestor';
    protected static ?string $title       = 'Gestores';
    protected static ?string $modelLabel  = 'encargo';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function instrucciones(): HtmlString
    {
        return new HtmlString(
            '<b>Cómo trabajar con gestores.</b> '
            . '1) Toca <b>Enviar a gestor</b>: elige al gestor, escribe qué tramitar y dónde, la fecha de entrega y marca solo los documentos que necesita. '
            . '2) El gestor revisa cada documento; si marca alguno como incorrecto te llega una notificación con su nota. '
            . '3) Usa <b>Corregir y reenviar</b> para subir el archivo bueno y regresárselo. '
            . '4) Cuando sube lo que tramitó te avisamos: valídalo o recházalo en la pestaña Documentos. '
            . '5) Cuando el gestor toca <b>Ya terminé</b> te avisamos; el encargo se cierra al validar todo lo que subió (si rechazas algo, se le regresa). Si tardas, te lo recordamos cada 2 días. Un expediente puede tener varios gestores a la vez.'
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(self::instrucciones())
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('gestor.name')->label('Gestor')->weight('bold'),
                Tables\Columns\TextColumn::make('instrucciones')->label('Qué tramitar')->limit(60)->wrap()
                    ->tooltip(fn (EncargoGestor $r) => $r->instrucciones),
                Tables\Columns\TextColumn::make('fecha_entrega')->label('Entrega')->date('d M Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => EncargoGestor::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        EncargoGestor::POR_REVISAR       => 'warning',
                        EncargoGestor::CON_OBSERVACIONES => 'danger',
                        EncargoGestor::EN_TRAMITE        => 'info',
                        EncargoGestor::POR_VALIDAR       => 'warning',
                        EncargoGestor::TERMINADO         => 'success',
                        default                          => 'gray',
                    }),
                Tables\Columns\TextColumn::make('documentos_count')->label('Compartidos')->counts('documentos'),
                Tables\Columns\TextColumn::make('documentos_subidos_count')->label('Subidos')->counts('documentosSubidos'),
                Tables\Columns\TextColumn::make('vuelta')->label('Vuelta'),
            ])
            ->headerActions([
                self::accionEnviar(fn () => $this->getOwnerRecord()),
            ])
            ->actions([
                \Filament\Actions\Action::make('ver')
                    ->label('Revisión e historial')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (EncargoGestor $r) => "Encargo de {$r->gestor?->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (EncargoGestor $r) => view('filament.encargo-gestor-detalle', ['encargo' => $r->load(['documentos', 'documentosSubidos', 'eventos.user'])])),

                \Filament\Actions\Action::make('reenviar')
                    ->label('Corregir y reenviar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->visible(fn (EncargoGestor $r) => $r->estado === EncargoGestor::CON_OBSERVACIONES)
                    ->modalHeading('Corregir y reenviar al gestor')
                    ->modalDescription('Sube el archivo correcto de cada documento que el gestor marcó como incorrecto. Al reenviar, el gestor solo revisa estos documentos.')
                    ->modalSubmitActionLabel('Reenviar al gestor')
                    ->form(fn (EncargoGestor $r) => $r->documentos()->wherePivot('revision', 'incorrecto')->get()
                        ->map(fn (DocumentoExpediente $d) => Forms\Components\FileUpload::make("archivo_{$d->id}")
                            ->label("Nuevo archivo: {$d->nombre}")
                            ->helperText("El gestor dijo: “{$d->pivot->nota}”")
                            ->disk('local')
                            ->directory("expedientes/{$d->expediente_id}/docs")
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->required())
                        ->all())
                    ->action(function (EncargoGestor $r, array $data) {
                        foreach ($r->documentos()->wherePivot('revision', 'incorrecto')->get() as $d) {
                            $nuevo = $data["archivo_{$d->id}"] ?? null;
                            if ($nuevo && $nuevo !== $d->ruta_archivo) {
                                $d->update(['ruta_archivo' => $nuevo, 'estado' => 'recibido', 'validado_at' => null, 'validado_por' => null, 'motivo_rechazo' => null]);
                            }
                        }
                        try {
                            app(EncargoGestorService::class)->reenviar($r->fresh(), auth()->user());
                            Notification::make()->title('Reenviado al gestor')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()->title('No se pudo reenviar')->body(collect($e->errors())->flatten()->join(' '))->danger()->send();
                        }
                    }),

                \Filament\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (EncargoGestor $r) => ! in_array($r->estado, [EncargoGestor::TERMINADO, EncargoGestor::CANCELADO], true))
                    ->requiresConfirmation()
                    ->modalDescription('El gestor recibirá un aviso de que ya no tiene que tramitarlo.')
                    ->action(fn (EncargoGestor $r) => app(EncargoGestorService::class)->cancelar($r, auth()->user())),
            ]);
    }

    /**
     * Acción "Enviar a gestor" (pestaña Gestores y encabezado del expediente).
     * @param \Closure(): Expediente $expediente
     */
    public static function accionEnviar(\Closure $expediente, string $nombre = 'enviar_a_gestor'): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make($nombre)
            ->label('Enviar a gestor')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->visible(fn () => auth()->user()?->hasRole('super_admin'))
            ->modalHeading('Enviar a gestor')
            ->modalDescription('Elige los documentos que necesita y los datos del expediente que verá. El gestor primero revisa que los documentos estén bien; si alguno está mal te avisará con una nota, y cuando todos estén correctos empieza el trámite.')
            ->modalSubmitActionLabel('Enviar')
            ->modalWidth('2xl')
            ->form(fn () => [
                Forms\Components\Select::make('gestor_id')
                    ->label('Gestor')
                    ->options(fn () => User::role('gestor')->where('activo', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->native(false)
                    ->helperText('¿No aparece? Créalo en Usuarios con el rol "gestor".'),
                Forms\Components\Textarea::make('instrucciones')
                    ->label('¿Qué tiene que tramitar?')
                    ->placeholder('Ej: Tramitar el certificado de libertad de gravamen en el Registro Público de Huejutla. Llevar copia de la escritura y la INE del vendedor.')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
                Forms\Components\DatePicker::make('fecha_entrega')
                    ->label('Fecha de entrega')
                    ->native(false)
                    ->minDate(today()),
                Forms\Components\CheckboxList::make('documentos')
                    ->label('Documentos que verá el gestor (marca solo los que necesita)')
                    ->options(function () use ($expediente) {
                        return DocumentoExpediente::where('expediente_id', $expediente()->id)
                            ->whereNotNull('ruta_archivo')
                            ->whereNull('encargo_gestor_id')
                            ->orderBy('nombre')
                            ->get()
                            ->mapWithKeys(fn (DocumentoExpediente $d) => [$d->id => $d->nombre . ($d->validacion === 'validado' ? ' ✓ validado' : '')]);
                    })
                    ->columns(2)
                    ->bulkToggleable()
                    ->required()
                    ->validationMessages(['required' => 'Marca al menos un documento.']),
                Forms\Components\CheckboxList::make('datos')
                    ->label('Datos del expediente que verá el gestor')
                    ->options(EncargoGestor::SECCIONES_DATOS)
                    ->default(array_keys(EncargoGestor::SECCIONES_DATOS))
                    ->columns(2),
            ])
            ->action(function (array $data) use ($expediente) {
                try {
                    app(EncargoGestorService::class)->enviar(
                        $expediente(),
                        User::findOrFail($data['gestor_id']),
                        array_map('intval', $data['documentos']),
                        trim($data['instrucciones']),
                        $data['fecha_entrega'] ?? null,
                        auth()->user(),
                        $data['datos'] ?? [],
                    );
                    Notification::make()->title('Expediente enviado al gestor')->body('Le llegó una notificación en la web y en la app.')->success()->send();
                } catch (ValidationException $e) {
                    Notification::make()->title('No se pudo enviar')->body(collect($e->errors())->flatten()->join(' '))->danger()->send();
                }
            });
    }
}
