<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactoResource\Pages;
use App\Models\Contacto;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotifAction;
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
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Prospectos';
    protected static ?string $navigationGroup = 'CRM';
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
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de contacto')
                ->description('Información básica del prospecto.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre')->required()->maxLength(255),
                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')->required()->tel()
                        ->regex('/^\d{10}$/')->maxLength(10)
                        ->validationMessages(['regex' => 'El teléfono debe tener exactamente 10 dígitos.']),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo')->email()->maxLength(150),
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
                    Forms\Components\Textarea::make('mensaje')
                        ->label('Mensaje')->rows(3)->columnSpanFull()
                        ->hint('Mensaje original del sitio web o nota inicial'),
                ])->columns(2),

            Forms\Components\Section::make('Gestión del Prospecto')
                ->description('Controla el avance del prospecto.')
                ->schema([
                    Forms\Components\Select::make('estado_prospecto')
                        ->label('Estado del prospecto')
                        ->options([
                            'nuevo'            => 'Nuevo',
                            'contactado'       => 'Contactado',
                            'precalificado'    => 'Precalificado',
                            'pendiente_cierre' => 'Pendiente de cierre (con dueño)',
                            'contrato_firmado' => 'Contrato firmado',
                            'convertido'       => 'Convertido a expediente',
                            'descartado'       => 'Descartado',
                        ])
                        ->default('nuevo')
                        ->hint('Actualiza conforme avance el proceso'),
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
                            'campo'     => 'Campo / visita directa',
                            'referido'  => 'Referido',
                            'whatsapp'  => 'WhatsApp',
                            'otro'      => 'Otro',
                        ])
                        ->default('sitio_web'),
                    Forms\Components\Select::make('asesor_id')
                        ->label('Asesor asignado')
                        ->options(User::where('activo', true)->pluck('name', 'id'))
                        ->searchable()->nullable()
                        ->visible(fn () => Auth::user()?->hasRole('super_admin')),
                    Forms\Components\DatePicker::make('fecha_primer_contacto')
                        ->label('Fecha primer contacto')
                        ->beforeOrEqual('today'),
                    Forms\Components\Textarea::make('notas')
                        ->label('Notas internas')->rows(3)->columnSpanFull(),
                ])->columns(2),

            // ── Sección visible solo cuando está pendiente de cierre ──────
            Forms\Components\Section::make('Cierre con el dueño')
                ->description('Indica cómo el dueño debe contactar o reunirse con el prospecto para cerrar.')
                ->visible(fn (Forms\Get $get) => in_array($get('estado_prospecto'), ['pendiente_cierre', 'contrato_firmado', 'convertido']))
                ->schema([
                    Forms\Components\Select::make('modalidad_cierre')
                        ->label('Modalidad de cierre')
                        ->options([
                            'telefono'        => 'Llamada telefónica',
                            'cita_oficina'    => 'Cita en oficina',
                            'visita_domicilio'=> 'Visita a domicilio',
                            'whatsapp'        => 'WhatsApp',
                        ])
                        ->required(fn (Forms\Get $get) => $get('estado_prospecto') === 'pendiente_cierre'),
                    Forms\Components\Placeholder::make('fecha_envio_dueno_display')
                        ->label('Enviado al dueño')
                        ->content(fn ($record) => $record?->fecha_envio_dueno?->format('d/m/Y H:i') ?? '—'),
                    Forms\Components\Textarea::make('notas_cierre')
                        ->label('Notas para el dueño')
                        ->rows(3)->columnSpanFull()
                        ->hint('Ej: vive en col. Magisterio, disponible de 6pm en adelante, ya revisó su crédito FOVISSSTE'),
                ])->columns(2)->collapsible(),

            Forms\Components\Section::make('Precalificación')
                ->description('Datos del simulador FOVISSSTE / portal INFONAVIT.')
                ->schema([
                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')->maxLength(18)->minLength(18)
                        ->regex('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i')
                        ->validationMessages(['regex' => 'La CURP no tiene el formato correcto.'])
                        ->live(onBlur: true)
                        ->extraAttributes(['style' => 'text-transform:uppercase']),
                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')->before('today'),
                    Forms\Components\TextInput::make('antiguedad_laboral')
                        ->label('Antigüedad laboral (años)')
                        ->numeric()->minValue(0)->maxValue(50),
                    Forms\Components\TextInput::make('salario_mensual')
                        ->label('Salario mensual')->numeric()->prefix('$')->minValue(0),
                    Forms\Components\Select::make('tipo_credito_interes')
                        ->label('Tipo de crédito')
                        ->options([
                            'fovissste' => 'FOVISSSTE',
                            'infonavit' => 'INFONAVIT',
                            'ambos'     => 'Ambos',
                            'otro'      => 'Otro',
                        ]),
                    Forms\Components\TextInput::make('monto_credito_estimado')
                        ->label('Monto de crédito estimado')->numeric()->prefix('$')->minValue(0),
                    Forms\Components\TextInput::make('subcuenta_vivienda')
                        ->label('Subcuenta de vivienda')->numeric()->prefix('$')->minValue(0),

                    Forms\Components\Placeholder::make('acceso_simulador')
                        ->label('Simuladores oficiales')->columnSpanFull()
                        ->content(function (Forms\Get $get): \Illuminate\Support\HtmlString {
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
                    ]),
                Tables\Filters\SelectFilter::make('asesor_id')
                    ->label('Asesor')
                    ->options(User::pluck('name', 'id'))
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ])
            ->actions([
                // ── Acción principal del asesor: enviar al dueño ──────────
                Tables\Actions\Action::make('enviar_dueno')
                    ->label('Enviar al dueño')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (Contacto $record) =>
                        Auth::user()?->hasRole('asesor') &&
                        in_array($record->estado_prospecto, ['nuevo', 'contactado', 'precalificado'])
                    )
                    ->modalHeading('Enviar prospecto al dueño')
                    ->modalDescription('El dueño recibirá una notificación con los datos del prospecto y la modalidad de cierre elegida.')
                    ->modalSubmitActionLabel('Sí, enviar ahora')
                    ->form([
                        Forms\Components\Select::make('modalidad_cierre')
                            ->label('¿Cómo debe contactarlo el dueño?')
                            ->options([
                                'telefono'         => 'Llamada telefónica',
                                'cita_oficina'     => 'Cita en oficina',
                                'visita_domicilio' => 'Visita a domicilio',
                                'whatsapp'         => 'WhatsApp',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notas_cierre')
                            ->label('Notas para el dueño')
                            ->rows(3)
                            ->placeholder('Ej: vive en col. Magisterio, disponible por las tardes, ya revisó su crédito FOVISSSTE…')
                            ->helperText('Cualquier detalle que ayude al dueño a preparar la visita o llamada.'),
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
                        $body = "**{$record->nombre}** — {$record->telefono}"
                            . "\nModalidad: {$modalidadLabel}"
                            . ($data['notas_cierre'] ? "\nNotas: {$data['notas_cierre']}" : '')
                            . "\nEnviado por: {$asesorNombre}";

                        // Notificar a todos los super_admin
                        $admins = User::role('super_admin')->get();
                        foreach ($admins as $admin) {
                            Notification::make()
                                ->title('Prospecto listo para cierre')
                                ->body($body)
                                ->icon('heroicon-o-user-plus')
                                ->warning()
                                ->actions([
                                    NotifAction::make('ver')
                                        ->label('Ver prospecto')
                                        ->url(static::getUrl('edit', ['record' => $record]))
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($admin);
                        }

                        Notification::make()
                            ->title('Prospecto enviado al dueño')
                            ->body('Se notificó al dueño. En breve se pondrá en contacto con ' . $record->nombre . '.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'edit'   => Pages\EditContacto::route('/{record}/edit'),
        ];
    }
}
