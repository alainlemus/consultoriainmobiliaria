<?php

namespace App\Filament\Resources\ExpedienteResource\RelationManagers;

use App\Models\DocumentoExpediente;
use App\Services\CargaMasivaService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\BulkAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';
    protected static ?string $title       = 'Documentos';
    protected static ?string $modelLabel  = 'documento';

    // Asesor puede ver y editar estado/notas/archivo, pero no crear ni eliminar
    public function canCreate(): bool { return false; }
    public function canDelete(Model $record): bool   { return Auth::user()?->hasRole('super_admin') ?? false; }
    public function canDeleteAny(): bool             { return Auth::user()?->hasRole('super_admin') ?? false; }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Documento')
                ->required()
                ->maxLength(150)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('ruta_archivo')
                ->label('Archivo del documento')
                ->disk('local')
                ->directory(fn ($record) => 'expedientes/' . ($record?->expediente_id ?? 'tmp') . '/docs')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(10240)
                ->columnSpanFull()
                ->helperText('PDF o imagen — máx. 10 MB. Al subir un archivo el estado cambia automáticamente a Recibido; al quitarlo vuelve a Pendiente.'),

            Forms\Components\TextInput::make('notas')
                ->label('Notas u observaciones')
                ->maxLength(255)
                ->placeholder('Opcional — ej: "pendiente firma", "copia simple aceptada"...')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Documentos del expediente')
            ->description('Documentos subidos desde la app y el panel. Márcalos como capturados al terminar.')
            ->columns([
                Tables\Columns\TextColumn::make('seccion')
                    ->label('Sección')
                    ->badge()
                    ->color(fn (?string $state) => match($state) {
                        'acreditado' => 'primary',
                        'vendedor'   => 'warning',
                        'vivienda'   => 'success',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match($state) {
                        'acreditado' => 'Acreditado',
                        'vendedor'   => 'Vendedor',
                        'vivienda'   => 'Vivienda',
                        default      => 'Otros',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Carpeta')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color(fn (?string $state) => match (true) {
                        str_starts_with($state ?? '', 'sofom/') => 'warning',
                        $state === 'sofom'                      => 'warning',
                        $state === 'notaria'                    => 'danger',
                        $state === 'acreditada'                 => 'primary',
                        $state === 'vendedor'                   => 'info',
                        $state === 'vivienda'                   => 'success',
                        default                                 => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => $state ? strtoupper($state) : '—')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Documento')
                    ->searchable()
                    ->weight('medium'),

                // La nota es lo que identifica rápido cada documento (ej. "INE del dueño de la casa").
                Tables\Columns\TextColumn::make('notas')
                    ->label('Nota')
                    ->placeholder('—')
                    ->searchable()
                    ->wrap()
                    ->limit(80)
                    ->color('warning')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('validacion')
                    ->label('Validación')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->ruta_archivo ? $record->validacion : null)
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'validado'    => 'Validado',
                        'rechazado'   => 'Rechazado',
                        'en_revision' => 'Por validar',
                        default       => '—',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'validado'    => 'success',
                        'rechazado'   => 'danger',
                        'en_revision' => 'warning',
                        default       => 'gray',
                    })
                    ->tooltip(fn ($record) => $record->validacion === 'rechazado' ? $record->motivo_rechazo : ($record->validado_at ? 'Validado el ' . $record->validado_at->format('d/m/Y H:i') . ($record->validadoPor ? ' por ' . $record->validadoPor->name : '') : null)),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->colors([
                        'danger'  => 'pendiente',
                        'success' => 'recibido',
                        'gray'    => 'no_aplica',
                    ])
                    ->formatStateUsing(fn (string $state) => match($state) {
                        'recibido'  => 'Recibido ✓',
                        'no_aplica' => 'No aplica',
                        default     => 'Pendiente',
                    }),

                Tables\Columns\IconColumn::make('capturado_at')
                    ->label('Capturado')
                    ->getStateUsing(fn ($record) => $record->ruta_archivo ? $record->capturado_at !== null : null)
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->ruta_archivo
                        ? ($record->capturado_at ? 'Capturado el ' . $record->capturado_at->format('d/m/Y H:i') : 'Por capturar')
                        : null),

                Tables\Columns\IconColumn::make('ruta_archivo')
                    ->label('Archivo')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->ruta_archivo ? 'Archivo adjunto' : 'Sin archivo'),
            ])
            ->defaultSort('categoria')
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')
                    ->label('Carpeta')
                    ->options(function () {
                        return \App\Models\DocumentoExpediente::where('expediente_id', $this->getOwnerRecord()?->id)
                            ->whereNotNull('categoria')
                            ->pluck('categoria')
                            ->map(fn ($c) => strtoupper($c))
                            ->unique()
                            ->sort()
                            ->mapWithKeys(fn ($c) => [strtolower($c) => $c])
                            ->toArray();
                    })
                    ->query(fn ($query, $data) => $data['value']
                        ? $query->where('categoria', $data['value'])
                        : $query
                    ),

                Tables\Filters\SelectFilter::make('validacion')
                    ->label('Validación')
                    ->options(['en_revision' => 'Por validar', 'validado' => 'Validados', 'rechazado' => 'Rechazados'])
                    ->query(fn ($query, $data) => match ($data['value'] ?? null) {
                        'en_revision' => $query->whereNotNull('ruta_archivo')->whereNull('validado_at')->where('estado', '!=', 'rechazado'),
                        'validado'    => $query->whereNotNull('validado_at'),
                        'rechazado'   => $query->where('estado', 'rechazado'),
                        default       => $query,
                    }),

                Tables\Filters\Filter::make('por_capturar')
                    ->label('Por capturar')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNotNull('ruta_archivo')->whereNull('capturado_at')),

                Tables\Filters\SelectFilter::make('seccion')
                    ->label('Sección')
                    ->options([
                        'acreditado' => 'Acreditado',
                        'vendedor'   => 'Vendedor',
                        'vivienda'   => 'Vivienda',
                    ]),

                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'recibido'  => 'Recibido',
                        'no_aplica' => 'No aplica',
                    ]),
            ])
            ->headerActions([
                // ── Pedir al acreditado un documento que faltaba (solo administradores) ──
                \Filament\Actions\Action::make('solicitar_documento')
                    ->label('Solicitar documento faltante')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                    ->modalHeading('Solicitar un documento faltante')
                    ->modalDescription('El acreditado recibe una notificación y verá tu nota en Mi Trámite de la app. Mientras no lo suba, el trámite sigue en revisión.')
                    ->modalSubmitActionLabel('Solicitar')
                    ->form([
                        Forms\Components\Textarea::make('nota')
                            ->label('¿Qué documento falta?')
                            ->placeholder('Ej: Falta el acta de matrimonio, ya que en la escritura aparece como casado.')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        app(\App\Services\SolicitudDocumentoService::class)->solicitar($this->getOwnerRecord(), auth()->user(), trim($data['nota']));
                        \Filament\Notifications\Notification::make()->title('Documento solicitado')->success()->send();
                    }),

                // ── Subir carpeta completa ────────────────────────────────
                \Filament\Actions\Action::make('subir_carpeta')
                    ->label('Subir carpeta')
                    ->icon('heroicon-o-folder-arrow-down')
                    ->color('primary')
                    ->modalHeading('Subir carpeta de documentos')
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Subir y procesar')
                    ->form([
                        Forms\Components\Placeholder::make('_instrucciones')
                            ->label('')
                            ->columnSpanFull()
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:13px;color:#1e40af;">'
                                . '<strong>¿Cómo subir una carpeta completa?</strong><br>'
                                . 'Selecciona todos los archivos dentro de la carpeta del acreditado.<br>'
                                . 'Organiza los archivos en subcarpetas: <strong>ACREDITADA</strong>, <strong>VENDEDOR</strong>, <strong>VIVIENDA</strong>, <strong>SOFOM</strong>, <strong>NOTARIA</strong>.<br>'
                                . 'El sistema detectará la sección automáticamente por el nombre de la carpeta.<br>'
                                . '<em>Formatos aceptados: PDF, JPG, PNG — máx. 20 MB por archivo.</em>'
                                . '</div>'
                            )),

                        Forms\Components\FileUpload::make('archivos')
                            ->label('Archivos')
                            ->multiple()
                            ->disk('local')
                            ->directory(fn () => 'tmp/carga_masiva/' . uniqid())
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(20480)
                            ->maxFiles(200)
                            ->columnSpanFull()
                            ->helperText('Selecciona múltiples archivos de una vez. En Mac: Cmd+A para seleccionar todos.')
                            ->required(),

                        Forms\Components\Textarea::make('rutas_relativas')
                            ->label('Rutas relativas (una por línea)')
                            ->columnSpanFull()
                            ->rows(4)
                            ->placeholder("ACREDITADA/CURP.pdf\nACREDITADA/INE.pdf\nVENDEDOR/CURP.pdf\nVIVIENDA/ESCRITURA.pdf")
                            ->helperText('Indica la carpeta de cada archivo. Formato: CARPETA/nombre_archivo.pdf — una por línea, en el mismo orden que los archivos seleccionados.')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $expediente = $this->getOwnerRecord();
                        $archivosSubidos = $data['archivos'] ?? [];
                        $rutasTexto      = $data['rutas_relativas'] ?? '';

                        if (empty($archivosSubidos)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se seleccionaron archivos')
                                ->warning()->send();
                            return;
                        }

                        // Parsear rutas relativas ingresadas
                        $rutas = array_values(array_filter(
                            array_map('trim', explode("\n", $rutasTexto))
                        ));

                        // Construir array de items para el servicio
                        $items = [];
                        foreach ($archivosSubidos as $idx => $rutaTmp) {
                            $rutaAbsoluta = Storage::disk('local')->path($rutaTmp);
                            if (! file_exists($rutaAbsoluta)) continue;

                            $items[] = [
                                'file'           => new \Illuminate\Http\UploadedFile(
                                    $rutaAbsoluta,
                                    basename($rutaTmp),
                                    null,
                                    null,
                                    true // test mode = no validar existencia
                                ),
                                'ruta_relativa' => $rutas[$idx] ?? basename($rutaTmp),
                            ];
                        }

                        if (empty($items)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No se pudieron leer los archivos subidos')
                                ->danger()->send();
                            return;
                        }

                        /** @var CargaMasivaService $servicio */
                        $servicio  = app(CargaMasivaService::class);
                        $resultado = $servicio->procesar($expediente, $items);

                        // ── Pre-rellenar campos del expediente con datos extraídos ──
                        $datosExtraidos = $resultado['datos_extraidos'];
                        unset($datosExtraidos['_fuentes']);

                        $camposExpediente = [
                            'acreditado_nombre'        => $datosExtraidos['acreditado_nombre'] ?? $datosExtraidos['nombre'] ?? null,
                            'acreditado_curp'          => $datosExtraidos['curp'] ?? null,
                            'acreditado_rfc'           => $datosExtraidos['rfc'] ?? null,
                            'acreditado_fecha_nacimiento' => $datosExtraidos['fecha_nacimiento'] ?? null,
                            'vivienda_calle'           => $datosExtraidos['vivienda_calle'] ?? null,
                            'vivienda_numero'          => $datosExtraidos['vivienda_numero'] ?? null,
                            'vivienda_colonia'         => $datosExtraidos['vivienda_colonia'] ?? null,
                            'vivienda_cp'              => $datosExtraidos['vivienda_cp'] ?? null,
                            'vivienda_municipio'       => $datosExtraidos['vivienda_municipio'] ?? null,
                            'vivienda_estado'          => $datosExtraidos['vivienda_estado'] ?? null,
                            'vendedor_nombre'          => $datosExtraidos['vendedor_nombre'] ?? null,
                            'cuv'                      => $datosExtraidos['cuv'] ?? null,
                        ];

                        $camposAActualizar = array_filter($camposExpediente, fn ($v) => ! is_null($v));

                        // Solo actualizar campos que estén vacíos en el expediente
                        $actualizacion = [];
                        foreach ($camposAActualizar as $campo => $valor) {
                            if (empty($expediente->$campo)) {
                                $actualizacion[$campo] = $valor;
                            }
                        }

                        if (! empty($actualizacion)) {
                            $expediente->update($actualizacion);
                        }

                        // Limpiar archivos temporales
                        foreach ($archivosSubidos as $rutaTmp) {
                            Storage::disk('local')->delete($rutaTmp);
                        }

                        $camposRellenos = count($actualizacion);
                        $msg = "Se subieron {$resultado['documentos_creados']} documentos";
                        if ($resultado['documentos_actualizados'] > 0) {
                            $msg .= ", {$resultado['documentos_actualizados']} actualizados";
                        }
                        if ($camposRellenos > 0) {
                            $msg .= ". Se pre-rellenaron {$camposRellenos} campos del expediente con datos extraídos de los PDFs.";
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Carga completada')
                            ->body($msg)
                            ->success()->send();
                    }),

                // Re-sincronizar checklist (solo super_admin) — útil para expedientes creados antes de la migración
                \Filament\Actions\Action::make('resincronizar')
                    ->label('Re-sincronizar checklist')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function () {
                        if (! Auth::user()?->hasRole('super_admin')) return false;
                        $expediente = $this->getOwnerRecord();
                        if (! $expediente?->tipo_tramite_id) return false;
                        // Ocultar si el expediente tiene documentos de carga masiva (con categoria)
                        return ! $expediente->documentos()->whereNotNull('categoria')->exists();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Re-sincronizar checklist de documentos')
                    ->modalDescription(
                        'Esto eliminará los documentos pendientes (sin archivo subido) que no pertenezcan al catálogo actual, ' .
                        'y creará los documentos faltantes según el tipo de trámite. ' .
                        'Los documentos con archivo subido NO se eliminan.'
                    )
                    ->action(function () {
                        $expediente = $this->getOwnerRecord();

                        if (! $expediente->tipo_tramite_id) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sin tipo de trámite asignado')
                                ->warning()->send();
                            return;
                        }

                        $requeridos = \App\Models\DocumentoRequerido::where('tipo_tramite_id', $expediente->tipo_tramite_id)
                            ->orderBy('seccion')->orderBy('orden')->get();

                        if ($requeridos->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sin documentos requeridos para este tipo de trámite')
                                ->warning()->send();
                            return;
                        }

                        $nombresRequeridos = $requeridos->pluck('nombre')->toArray();

                        // Eliminar filas legacy sin archivo que no pertenecen al catálogo actual
                        $eliminados = $expediente->documentos()
                            ->whereNull('ruta_archivo')
                            ->whereNotIn('tipo', $nombresRequeridos)
                            ->delete();

                        // Crear filas faltantes del catálogo actual
                        $tiposExistentes = $expediente->documentos()->pluck('tipo')->toArray();
                        $creados = 0;

                        foreach ($requeridos as $req) {
                            if (! in_array($req->nombre, $tiposExistentes)) {
                                $expediente->documentos()->create([
                                    'tipo'    => $req->nombre,
                                    'nombre'  => $req->nombre,
                                    'seccion' => $req->seccion,
                                    'estado'  => 'pendiente',
                                ]);
                                $creados++;
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title("Checklist actualizado — {$creados} creados, {$eliminados} legacy eliminados")
                            ->success()->send();
                    }),
            ])
            ->actions([
                // Solo un administrador valida o rechaza documentos
                \Filament\Actions\Action::make('validar')
                    ->label('Validar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()?->hasRole('super_admin') && $record->ruta_archivo && $record->validacion !== 'validado')
                    ->action(fn ($record) => app(\App\Services\ValidacionDocumentoService::class)->validar($record, auth()->user()))
                    ->requiresConfirmation(false),

                \Filament\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()?->hasRole('super_admin') && $record->ruta_archivo && $record->validacion !== 'rechazado')
                    ->modalHeading('Rechazar documento')
                    ->modalDescription('Escribe qué debe corregirse. Si el acreditado usa la app, recibirá el motivo.')
                    ->form([
                        Forms\Components\Textarea::make('motivo')->label('Motivo')->required()->maxLength(500)->rows(3)
                            ->placeholder('Ej: El estado de cuenta tiene más de 3 meses'),
                    ])
                    ->action(fn ($record, array $data) => app(\App\Services\ValidacionDocumentoService::class)->rechazar($record, $data['motivo'])),

                \Filament\Actions\Action::make('marcar_capturado')
                    ->label('Capturado')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->ruta_archivo && ! $record->capturado_at)
                    ->action(fn ($record) => $record->update(['capturado_at' => now()]))
                    ->requiresConfirmation(false),

                \Filament\Actions\Action::make('desmarcar_capturado')
                    ->label('Por capturar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn ($record) => $record->ruta_archivo && $record->capturado_at)
                    ->action(fn ($record) => $record->update(['capturado_at' => null]))
                    ->requiresConfirmation(false),

                \Filament\Actions\Action::make('ver_archivo')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn ($record) => (bool) $record->ruta_archivo)
                    ->modalHeading(fn ($record) => 'Documento — ' . $record->nombre)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $url    = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                            'api.documentos.descargar',
                            now()->addMinutes(30),
                            ['expedienteId' => $record->expediente_id, 'documentoId' => $record->id]
                        );
                        $nombre = strtolower($record->ruta_archivo ?? '');
                        $esPdf  = str_ends_with($nombre, '.pdf');

                        if ($esPdf) {
                            $html = '
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <div style="text-align:center;">
                                        <a href="' . e($url) . '" target="_blank" rel="noopener"
                                           style="display:inline-flex;align-items:center;gap:6px;
                                                  background:#2563eb;color:#fff;padding:9px 20px;
                                                  border-radius:8px;text-decoration:none;
                                                  font-size:14px;font-weight:600;letter-spacing:.01em;">
                                            ↗&nbsp; Abrir PDF en nueva pestaña
                                        </a>
                                    </div>
                                    <object data="' . e($url) . '" type="application/pdf"
                                            style="width:100%;height:85vh;border:none;border-radius:6px;background:#f3f4f6;">
                                        <p style="text-align:center;color:#6b7280;padding:32px;font-size:14px;">
                                            Tu navegador no puede mostrar el PDF en línea.<br>
                                            <a href="' . e($url) . '" target="_blank"
                                               style="color:#2563eb;">Haz clic aquí para abrirlo</a>.
                                        </p>
                                    </object>
                                </div>';
                        } else {
                            $html = '<img src="' . e($url) . '" alt="Documento"
                                         style="max-width:100%;max-height:85vh;display:block;
                                                margin:0 auto;border-radius:6px;object-fit:contain;">';
                        }

                        return new \Illuminate\Support\HtmlString($html);
                    }),

                \Filament\Actions\ActionGroup::make([
                // Acciones rápidas de estado (un clic, sin modal)
                \Filament\Actions\Action::make('marcar_recibido')
                    ->label('Recibido')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->estado !== 'recibido')
                    ->action(fn ($record) => $record->update(['estado' => 'recibido']))
                    ->requiresConfirmation(false),

                \Filament\Actions\Action::make('marcar_no_aplica')
                    ->label('No aplica')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn ($record) => $record->estado !== 'no_aplica')
                    ->action(fn ($record) => $record->update(['estado' => 'no_aplica']))
                    ->requiresConfirmation(false),

                \Filament\Actions\Action::make('marcar_pendiente')
                    ->label('Pendiente')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn ($record) => $record->estado !== 'pendiente')
                    ->action(fn ($record) => $record->update(['estado' => 'pendiente']))
                    ->requiresConfirmation(false),

                // Editar notas y subir/reemplazar archivo
                \Filament\Actions\EditAction::make()
                    ->label('Editar / Subir')
                    ->icon('heroicon-o-paper-clip')
                    ->mutateRecordDataUsing(fn (array $data) => $data)
                    ->using(function (Model $record, array $data): Model {
                        $archivoNuevo   = $data['ruta_archivo'] ?? null;
                        $archivoActual  = $record->ruta_archivo;

                        if ($archivoNuevo && $archivoNuevo !== $archivoActual) {
                            // Se subió un archivo nuevo → recibido
                            $data['estado'] = 'recibido';
                        } elseif (! $archivoNuevo && $archivoActual) {
                            // Se eliminó el archivo → pendiente
                            $data['estado'] = 'pendiente';
                        }

                        $record->update($data);
                        return $record;
                    }),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Más acciones'),
            ])
            ->bulkActions([
                BulkAction::make('validar_documentos')
                    ->label('Validar seleccionados')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->hasRole('super_admin'))
                    ->action(fn ($records) => $records->filter(fn ($r) => $r->ruta_archivo)
                        ->each(fn ($r) => app(\App\Services\ValidacionDocumentoService::class)->validar($r, auth()->user()))),
                BulkAction::make('marcar_capturados')
                    ->label('Marcar como capturados')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->action(fn ($records) => $records->each->update(['capturado_at' => now()])),
                BulkAction::make('marcar_recibidos')
                    ->label('Marcar seleccionados como recibidos')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($records) => $records->each->update(['estado' => 'recibido'])),

                BulkAction::make('marcar_no_aplica_bulk')
                    ->label('Marcar como no aplica')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->action(fn ($records) => $records->each->update(['estado' => 'no_aplica'])),
            ]);
    }
}
