<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactoResource\Actions\EnviarEmailMasivoBulkAction;
use App\Filament\Resources\ContactoResource\Pages;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Actions\Action as NotifAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ContactoResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('super_admin') ||
            auth()->user()->hasRole('asesor')
        );
    }

    protected static ?string $model = Contacto::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Prospectos';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Prospecto';
    protected static ?string $pluralModelLabel = 'Prospectos';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'email', 'telefono', 'curp', 'mensaje'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->nombre ?: 'Prospecto sin nombre';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email'    => $record->email ?? '—',
            'Teléfono' => $record->telefono ?? '—',
            'Servicio' => ucfirst($record->servicio ?? '—'),
            'Estado'   => ucfirst(str_replace('_', ' ', $record->estado_prospecto ?? '—')),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        if ($record->estado_prospecto === 'convertido') {
            return static::getUrl('view', ['record' => $record]);
        }
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        // Excluir prospectos que ya no son prospectos activos
        $query->whereNotIn('estado_prospecto', ['convertido', 'descartado'])
              ->whereDoesntHave('expedientes');

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        // Asesor: sus prospectos pendientes de cierre o nuevos
        // Admin: todos los pendientes de cierre
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $count = static::getModel()::where('asesor_id', Auth::id())
                ->where('estado_prospecto', 'nuevo')->count();
        } else {
            $count = static::getModel()::where('estado_prospecto', 'pendiente_cierre')->count();
        }

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Auth::user()?->hasRole('asesor') ? 'warning' : 'danger';
    }

    /* ──────────────────────────────────────────────────────────── FORM ── */

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Datos del Prospecto')
                ->description('Información de contacto y gestión.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')->required()->maxLength(255)
                        ->validationMessages([
                            'required' => 'El nombre del prospecto es obligatorio.',
                            'max'      => 'El nombre no puede superar los 255 caracteres.',
                        ]),
                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')->required()->tel()
                        ->regex('/^\d{10}$/')->maxLength(10)
                        ->validationMessages([
                            'required' => 'El teléfono es obligatorio.',
                            'regex'    => 'El teléfono debe tener exactamente 10 dígitos.',
                            'max'      => 'El teléfono no puede superar los 10 dígitos.',
                        ]),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo')->email()->maxLength(150)
                        ->validationMessages([
                            'email' => 'Ingresa un correo electrónico válido (ej: usuario@dominio.com).',
                            'max'   => 'El correo no puede superar los 150 caracteres.',
                        ]),
                    Forms\Components\Select::make('servicio')
                        ->label('Servicio de interés')
                        ->options([
                            'infonavit'  => 'Crédito INFONAVIT',
                            'fovissste'  => 'Crédito FOVISSSTE',
                            'avaluo'     => 'Avalúo',
                            'escrituras' => 'Escrituración',
                            'asesoria'   => 'Asesoría personalizada',
                            'otro'       => 'Otro',
                        ]),
                    Forms\Components\Select::make('estado_prospecto')
                        ->label('Estado del prospecto')
                        ->options([
                            'nuevo'            => 'Nuevo',
                            'contactado'       => 'Contactado',
                            'precalificado'    => 'Precalificado',
                            'pendiente_cierre' => 'Pendiente de cierre (con gestor)',
                            'contrato_firmado' => 'Contrato firmado',
                            'convertido'       => 'Convertido a expediente',
                            'descartado'       => 'Descartado',
                        ])
                        ->default('nuevo')
                        ->hint('Actualiza conforme avance el proceso'),
                    Forms\Components\Select::make('estado_ubicacion')
                        ->label('Estado / Ubicación')
                        ->options([
                            'Hidalgo'          => 'Hidalgo',
                            'Veracruz'         => 'Veracruz',
                            'Tamaulipas'       => 'Tamaulipas',
                            'San Luis Potosí'  => 'San Luis Potosí',
                            'Otro'             => 'Otro',
                        ])
                        ->searchable()
                        ->nullable(),
                    Forms\Components\Select::make('estado')
                        ->label('Estado (interno)')
                        ->options([
                            'nuevo'      => 'Nuevo',
                            'en_proceso' => 'En proceso',
                            'atendido'   => 'Atendido',
                        ])
                        ->default('nuevo')
                        ->hidden(),
                    Forms\Components\Select::make('origen')
                        ->label('Origen')
                        ->options([
                            'sitio_web' => 'Sitio web',
                            'admin'     => 'Admin (super_admin)',
                            'asesor'    => 'Asesor',
                            'campo'     => 'Campo / visita directa',
                            'referido'  => 'Referido',
                            'whatsapp'  => 'WhatsApp',
                            'app_movil' => 'App móvil',
                            'otro'      => 'Otro',
                        ])
                        ->default(function () {
                            $user = Auth::user();
                            if (! $user) return 'otro';
                            return $user->hasRole('super_admin') ? 'admin' : 'asesor';
                        })
                        ->disabled(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord)
                        ->dehydrated(fn ($livewire) => ! ($livewire instanceof \Filament\Resources\Pages\EditRecord))
                        ->hint(fn ($livewire) => ($livewire instanceof \Filament\Resources\Pages\EditRecord)
                            ? 'El origen no se puede cambiar una vez creado el prospecto.'
                            : null
                        ),
                    Forms\Components\Select::make('asesor_id')
                        ->label('Asesor asignado')
                        ->options(User::where('activo', true)->role('asesor')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->default(fn () => Auth::user()?->hasRole('asesor') ? Auth::id() : null)
                        ->disabled(fn () => Auth::user()?->hasRole('asesor'))
                        ->dehydrated(fn ($livewire) => ! (
                            Auth::user()?->hasRole('asesor') &&
                            $livewire instanceof \Filament\Resources\Pages\EditRecord
                        ))
                        ->visible(fn () => Auth::user()?->hasAnyRole(['super_admin', 'asesor'])),
                    Forms\Components\DatePicker::make('fecha_primer_contacto')
                        ->label('Fecha primer contacto')
                        ->default(now())
                        ->beforeOrEqual('today')
                        ->validationMessages([
                            'before_or_equal' => 'La fecha de primer contacto no puede ser futura.',
                        ]),
                    Forms\Components\Textarea::make('mensaje')
                        ->label('Mensaje')->rows(3)->columnSpanFull()
                        ->hint('Mensaje del prospecto (sitio web, WhatsApp u otro canal)'),
                    Forms\Components\Textarea::make('notas')
                        ->label('Notas internas')->rows(3)->columnSpanFull(),
                ])->columns(2),

            // ── Sección visible solo cuando está pendiente de cierre ──────
            \Filament\Schemas\Components\Section::make('Cierre con el gestor')
                ->description('Indica cómo el gestor debe contactar o reunirse con el prospecto para cerrar.')
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('estado_prospecto'), ['pendiente_cierre', 'contrato_firmado', 'convertido']))
                ->schema([
                    Forms\Components\Select::make('modalidad_cierre')
                        ->label('Modalidad de cierre')
                        ->options([
                            'telefono'        => 'Llamada telefónica',
                            'cita_oficina'    => 'Cita en oficina',
                            'visita_domicilio'=> 'Visita a domicilio',
                            'whatsapp'        => 'WhatsApp',
                        ])
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('estado_prospecto') === 'pendiente_cierre')
                        ->validationMessages([
                            'required' => 'La modalidad de cierre es obligatoria cuando el estado es "Pendiente de cierre".',
                        ]),
                    Forms\Components\Placeholder::make('fecha_envio_dueno_display')
                        ->label('Enviado al gestor')
                        ->content(fn ($record) => $record?->fecha_envio_dueno?->format('d/m/Y H:i') ?? '—'),
                    Forms\Components\Textarea::make('notas_cierre')
                        ->label('Notas para el gestor')
                        ->rows(3)->columnSpanFull()
                        ->hint('Ej: vive en col. Magisterio, disponible de 6pm en adelante, ya revisó su crédito FOVISSSTE'),
                ])->columns(2)->collapsible(),

            \Filament\Schemas\Components\Section::make('Precalificación')
                ->description('Datos del simulador FOVISSSTE / portal INFONAVIT.')
                ->schema([
                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')
                        ->nullable()
                        ->maxLength(18)->minLength(18)
                        ->regex('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i')
                        ->validationMessages([
                            'regex' => 'La CURP no tiene el formato correcto (ej: LOHA850101HDFPLN02).',
                            'min'   => 'La CURP debe tener exactamente 18 caracteres.',
                            'max'   => 'La CURP debe tener exactamente 18 caracteres.',
                        ])
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Set $set, ?string $state) => $set('curp', strtoupper($state ?? '')))
                        ->dehydrated(true)
                        ->placeholder('LOHA850101HDFPLN02'),
                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')->before('today')
                        ->validationMessages([
                            'before' => 'La fecha de nacimiento debe ser anterior a hoy.',
                        ]),
                    Forms\Components\TextInput::make('antiguedad_laboral')
                        ->label('Antigüedad laboral (años)')
                        ->numeric()->minValue(0)->maxValue(50)
                        ->validationMessages([
                            'min' => 'La antigüedad no puede ser negativa.',
                            'max' => 'La antigüedad no puede superar los 50 años.',
                        ]),
                    Forms\Components\TextInput::make('salario_mensual')
                        ->label('Salario mensual')->numeric()->prefix('$')->minValue(0)
                        ->validationMessages([
                            'min' => 'El salario no puede ser negativo.',
                        ]),
                    Forms\Components\Select::make('tipo_credito_interes')
                        ->label('Tipo de crédito')
                        ->options([
                            'fovissste' => 'FOVISSSTE',
                            'infonavit' => 'INFONAVIT',
                            'ambos'     => 'Ambos',
                            'otro'      => 'Otro',
                        ]),
                    Forms\Components\TextInput::make('monto_credito_estimado')
                        ->label('Monto de crédito estimado')->numeric()->prefix('$')->minValue(0)
                        ->validationMessages([
                            'min' => 'El monto estimado no puede ser negativo.',
                        ]),
                    Forms\Components\TextInput::make('subcuenta_vivienda')
                        ->label('Subcuenta de vivienda')->numeric()->prefix('$')->minValue(0)
                        ->validationMessages([
                            'min' => 'La subcuenta de vivienda no puede ser negativa.',
                        ]),

                    Forms\Components\Select::make('resultado_precalificacion')
                        ->label('Resultado de precalificación')
                        ->options([
                            'pendiente'    => '⏳ Pendiente',
                            'aprobado'     => '✅ Aprobado',
                            'condicional'  => '⚠️ Condicional',
                            'no_califica'  => '❌ No califica',
                        ])
                        ->nullable()
                        ->placeholder('Sin evaluar')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('acceso_simulador')
                        ->label('Simuladores oficiales')->columnSpanFull()
                        ->content(function (\Filament\Schemas\Components\Utilities\Get $get): \Illuminate\Support\HtmlString {
                            $curp = strtoupper(trim($get('curp') ?? ''));
                            $hint = $curp
                                ? "<span class='text-xs text-gray-500 ml-2'>CURP: <strong>{$curp}</strong></span>"
                                : "<span class='text-xs text-gray-400 ml-2'>Captura la CURP arriba primero</span>";
                            return new \Illuminate\Support\HtmlString(
                                '<div class="flex flex-wrap gap-3 items-center">'
                                .'<a href="https://inscripcioncontinua.fovissste.gob.mx/simulador/" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-700 hover:bg-green-800 transition-colors shadow-sm">Simulador FOVISSSTE</a>'
                                .'<a href="https://micuenta.infonavit.org.mx/" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-700 hover:bg-red-800 transition-colors shadow-sm">Portal INFONAVIT</a>'
                                .$hint.'</div>'
                            );
                        }),

                    Forms\Components\Textarea::make('notas_precalificacion')
                        ->label('Resultado de la precalificación')
                        ->rows(4)->columnSpanFull()
                        ->helperText('Pega aquí el resultado del simulador o anota el monto aprobado.'),
                ])->columns(2)->collapsible(),
        ]);
    }

    /* ─────────────────────────────────────────────────────────── TABLE ── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')->searchable()->toggleable(),
                Tables\Columns\BadgeColumn::make('origen')
                    ->label('Origen')
                    ->colors([
                        'gray'    => 'sitio_web',
                        'primary' => 'campo',
                        'success' => 'referido',
                        'warning' => 'whatsapp',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'sitio_web' => 'Sitio web',
                        'campo'     => 'Campo',
                        'referido'  => 'Referido',
                        'whatsapp'  => 'WhatsApp',
                        'app_movil' => '📱 App móvil',
                        default     => 'Otro',
                    }),
                Tables\Columns\BadgeColumn::make('estado_prospecto')
                    ->label('Estado')
                    ->colors([
                        'gray'    => 'nuevo',
                        'primary' => 'contactado',
                        'warning' => 'precalificado',
                        'danger'  => 'pendiente_cierre',
                        'info'    => 'contrato_firmado',
                        'success' => 'convertido',
                        'gray'    => 'descartado',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'nuevo'            => 'Nuevo',
                        'contactado'       => 'Contactado',
                        'precalificado'    => 'Precalificado',
                        'pendiente_cierre' => '⏳ Pendiente cierre',
                        'contrato_firmado' => 'Contrato firmado',
                        'convertido'       => 'Convertido',
                        'descartado'       => 'Descartado',
                        default            => $state,
                    }),
                Tables\Columns\TextColumn::make('estado_ubicacion')
                    ->label('Estado')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('resultado_precalificacion')
                    ->label('Precalif.')
                    ->colors([
                        'gray'    => 'pendiente',
                        'success' => 'aprobado',
                        'warning' => 'condicional',
                        'danger'  => 'no_califica',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pendiente'   => '⏳ Pendiente',
                        'aprobado'    => '✅ Aprobado',
                        'condicional' => '⚠️ Condicional',
                        'no_califica' => '❌ No califica',
                        default       => '—',
                    })
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tipo_credito_interes')
                    ->label('Tipo crédito')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'infonavit' => 'INFONAVIT',
                        'fovissste' => 'FOVISSSTE',
                        'ambos'     => 'Ambos',
                        'otro'      => 'Otro',
                        default     => $state ?? '—',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('modalidad_cierre')
                    ->label('Modalidad')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'telefono'         => 'Teléfono',
                        'cita_oficina'     => 'Cita en oficina',
                        'visita_domicilio' => 'Visita domicilio',
                        'whatsapp'         => 'WhatsApp',
                        default            => '—',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')->toggleable(),
                Tables\Columns\TextColumn::make('monto_credito_estimado')
                    ->label('Monto estimado')->money('MXN')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_prospecto')
                    ->label('Estado')
                    ->options([
                        'nuevo'            => 'Nuevo',
                        'contactado'       => 'Contactado',
                        'precalificado'    => 'Precalificado',
                        'pendiente_cierre' => 'Pendiente de cierre',
                        'contrato_firmado' => 'Contrato firmado',
                        'convertido'       => 'Convertido',
                        'descartado'       => 'Descartado',
                    ]),
                Tables\Filters\SelectFilter::make('origen')
                    ->label('Origen')
                    ->options([
                        'sitio_web' => 'Sitio web',
                        'campo'     => 'Campo',
                        'referido'  => 'Referido',
                        'whatsapp'  => 'WhatsApp',
                        'app_movil' => 'App móvil',
                        'otro'      => 'Otro',
                    ]),
                Tables\Filters\SelectFilter::make('asesor_id')
                    ->label('Asesor')
                    ->options(User::pluck('name', 'id'))
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ])
            ->actions([
                // ── Acción principal del asesor: iniciar gestión ──────────
                \Filament\Actions\Action::make('enviar_dueno')
                    ->label('Iniciar expediente')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (Contacto $record) =>
                        Auth::user()?->hasRole('asesor') &&
                        in_array($record->estado_prospecto, ['nuevo', 'contactado', 'precalificado'])
                    )
                    ->modalHeading('Iniciar expediente del prospecto')
                    ->modalDescription('Se registrará el inicio de la gestión comercial con el prospecto.')
                    ->modalSubmitActionLabel('Sí, iniciar')
                    ->form([
                        Forms\Components\Select::make('modalidad_cierre')
                            ->label('Modalidad de contacto')
                            ->options([
                                'telefono'         => 'Llamada telefónica',
                                'cita_oficina'     => 'Cita en oficina',
                                'visita_domicilio' => 'Visita a domicilio',
                                'whatsapp'         => 'WhatsApp',
                            ])
                            ->required()
                            ->validationMessages([
                                'required' => 'Debes seleccionar una modalidad de contacto.',
                            ]),
                        Forms\Components\Textarea::make('notas_cierre')
                            ->label('Notas de gestión')
                            ->rows(3)
                            ->placeholder('Ej: vive en col. Magisterio, disponible por las tardes, ya revisó su crédito FOVISSSTE…')
                            ->helperText('Cualquier detalle relevante sobre el prospecto.'),
                    ])
                    ->action(function (Contacto $record, array $data) {
                        $record->update([
                            'estado_prospecto'  => 'pendiente_cierre',
                            'modalidad_cierre'  => $data['modalidad_cierre'],
                            'notas_cierre'      => $data['notas_cierre'] ?? null,
                            'fecha_envio_dueno' => now(),
                        ]);

                        $modalidadLabel = match($data['modalidad_cierre']) {
                            'telefono'         => 'Llamada telefónica',
                            'cita_oficina'     => 'Cita en oficina',
                            'visita_domicilio' => 'Visita a domicilio',
                            'whatsapp'         => 'WhatsApp',
                            default            => $data['modalidad_cierre'],
                        };

                        $asesorNombre = Auth::user()->name;

                        // Notificar a todos los super_admin usando clase de notificación
                        $admins = User::role('super_admin')->get();
                        foreach ($admins as $admin) {
                            $admin->notify(new \App\Notifications\ProspectoIniciandoGestion(
                                $record,
                                $modalidadLabel,
                                $data['notas_cierre'] ?? null,
                                $asesorNombre,
                            ));
                        }

                        Notification::make()
                            ->title('Gestión iniciada')
                            ->body('El prospecto ' . $record->nombre . ' ha sido marcado como pendiente de cierre.')
                            ->success()
                            ->send();
                    }),

                // Botones solo para super_admin
                \Filament\Actions\Action::make('iniciar_expediente')
                    ->label('Iniciar Expediente')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Iniciar expediente con este prospecto?')
                    ->modalDescription('Se creará un nuevo expediente vinculado a este contacto.')
                    ->action(function (Contacto $record) {
                        // Evitar duplicados
                        if (Expediente::where('contacto_id', $record->id)->exists()) {
                            Notification::make()
                                ->title('Ya existe un expediente para este prospecto.')
                                ->warning()
                                ->send();
                            return;
                        }
                        Expediente::create([
                            'contacto_id'         => $record->id,
                            'asesor_id'           => $record->asesor_id ?? Auth::id(),
                            'acreditado_nombre'   => $record->nombre,
                            'acreditado_telefono' => $record->telefono,
                            'acreditado_email'    => $record->email,
                            'acreditado_curp'     => $record->curp,
                            'tipo_tramite_id'     => 1,
                            'etapa_tramite_id'    => 1,
                            'estado'              => 'en_proceso',
                        ]);
                        $record->update(['estado_prospecto' => 'convertido']);
                        Notification::make()
                            ->title('Expediente creado correctamente.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Contacto $record) => Auth::user()?->hasRole('super_admin')
                        && ! in_array($record->estado_prospecto, ['convertido', 'descartado'])),

                \Filament\Actions\Action::make('descartar')
                    ->label('No cerró')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('¿El prospecto no cerró?')
                    ->modalDescription('El prospecto regresará a estado "Descartado".')
                    ->action(fn (Contacto $record) => $record->update(['estado_prospecto' => 'descartado']))
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),

                \Filament\Actions\EditAction::make()
                    ->visible(fn (Contacto $record) =>
                        Auth::user()?->hasRole('super_admin') ||
                        ! in_array($record->estado_prospecto, ['pendiente_cierre', 'contrato_firmado', 'convertido'])
                    ),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    EnviarEmailMasivoBulkAction::make(),
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasRole('super_admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContactos::route('/'),
            'create' => Pages\CreateContacto::route('/create'),
            'view'   => Pages\ViewContacto::route('/{record}'),
            'edit'   => Pages\EditContacto::route('/{record}/edit'),
        ];
    }
}
