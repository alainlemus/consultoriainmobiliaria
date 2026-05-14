<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactoResource\Pages;
use App\Models\Contacto;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
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
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }


    protected static ?string $model = Contacto::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Prospectos del Sitio Web';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Prospecto';
    protected static ?string $pluralModelLabel = 'Prospectos del Sitio Web';

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

        // Asesores solo ven sus prospectos asignados
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::where('estado_prospecto', 'nuevo');

        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de contacto')
                ->description('Información básica del prospecto. Los prospectos del sitio web llegan aquí automáticamente. También puedes agregar prospectos de forma manual.')
                ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->hint('Nombre completo tal como lo proporcionó'),
                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono')
                    ->required()
                    ->tel()
                    ->regex('/^\d{10}$/')
                    ->maxLength(10)
                    ->validationMessages(['regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos.'])
                    ->hint('10 dígitos — principal medio de contacto'),
                Forms\Components\TextInput::make('email')
                    ->label('Correo')
                    ->email()
                    ->maxLength(150)
                    ->validationMessages(['email' => 'Ingresa un correo electrónico válido.']),
                Forms\Components\Select::make('servicio')
                    ->label('Servicio de interés')
                    ->options([
                        'infonavit'  => 'Crédito INFONAVIT',
                        'fovissste'  => 'Crédito FOVISSSTE',
                        'avaluo'     => 'Avalúo',
                        'escrituras' => 'Escrituración',
                        'asesoria'   => 'Asesoría personalizada',
                        'otro'       => 'Otro',
                    ])
                    ->hint('Servicio que solicitó o le interesa'),
                Forms\Components\Textarea::make('mensaje')
                    ->label('Mensaje')->rows(3)->columnSpanFull()
                    ->hint('Mensaje original que envió desde el sitio web'),
            ])->columns(2),

            Forms\Components\Section::make('Gestión del Prospecto')
                ->description('Controla el avance del prospecto en el funnel de ventas. Cuando el prospecto esté listo, crea un expediente desde la sección de Expedientes y vincúlalo aquí.')
                ->schema([
                Forms\Components\Select::make('estado_prospecto')
                    ->label('Estado del prospecto')
                    ->options([
                        'nuevo'             => 'Nuevo',
                        'contactado'        => 'Contactado',
                        'precalificado'     => 'Precalificado',
                        'propuesta_enviada' => 'Propuesta enviada',
                        'convertido'        => 'Convertido a expediente',
                        'descartado'        => 'Descartado',
                    ])
                    ->default('nuevo')
                    ->hint('Actualiza conforme avance el proceso de atención'),
                // Mantener campo 'estado' para compatibilidad con notificaciones previas
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
                    ->default('sitio_web')
                    ->hint('¿Cómo llegó este prospecto?'),
                Forms\Components\Select::make('asesor_id')
                    ->label('Asesor asignado')
                    ->options(User::where('activo', true)->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->hint('Asesor que dará seguimiento a este prospecto'),
                Forms\Components\DatePicker::make('fecha_primer_contacto')
                    ->label('Fecha primer contacto')
                    ->beforeOrEqual('today')
                    ->validationMessages(['before_or_equal' => 'La fecha de primer contacto no puede ser futura.'])
                    ->hint('Cuándo se estableció contacto por primera vez'),
                Forms\Components\Textarea::make('notas')
                    ->label('Notas internas')->rows(3)->columnSpanFull()
                    ->hint('Observaciones internas — no visibles para el prospecto'),
            ])->columns(2),

            Forms\Components\Section::make('Precalificación')
                ->description('Datos obtenidos del simulador FOVISSSTE / portal INFONAVIT. Completa esta sección antes de presentar una propuesta al prospecto.')
                ->schema([
                    Forms\Components\TextInput::make('curp')
                        ->label('CURP')
                        ->maxLength(18)
                        ->minLength(18)
                        ->regex('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i')
                        ->validationMessages(['regex' => 'La CURP no tiene el formato correcto (18 caracteres, ej: GOML800101HDFRZS09).'])
                        ->live(onBlur: true)
                        ->extraAttributes(['style' => 'text-transform:uppercase'])
                        ->hint('Necesaria para consultar crédito en simulador'),
                    Forms\Components\DatePicker::make('fecha_nacimiento')
                        ->label('Fecha de nacimiento')
                        ->before('today')
                        ->validationMessages(['before' => 'La fecha de nacimiento debe ser anterior a hoy.']),
                    Forms\Components\TextInput::make('antiguedad_laboral')
                        ->label('Antigüedad laboral (años)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->validationMessages([
                            'min' => 'La antigüedad no puede ser negativa.',
                            'max' => 'La antigüedad no puede superar 50 años.',
                        ])
                        ->hint('Años completos cotizados al IMSS o ISSSTE'),
                    Forms\Components\TextInput::make('salario_mensual')
                        ->label('Salario mensual')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->validationMessages(['min' => 'El salario no puede ser negativo.'])
                        ->hint('Salario integrado mensual bruto'),
                    Forms\Components\Select::make('tipo_credito_interes')
                        ->label('Tipo de crédito')
                        ->options([
                            'fovissste' => 'FOVISSSTE',
                            'infonavit' => 'INFONAVIT',
                            'ambos'     => 'Ambos',
                            'otro'      => 'Otro',
                        ]),
                    Forms\Components\TextInput::make('monto_credito_estimado')
                        ->label('Monto de crédito estimado')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->validationMessages(['min' => 'El monto estimado no puede ser negativo.'])
                        ->hint('Resultado del simulador oficial'),
                    Forms\Components\TextInput::make('subcuenta_vivienda')
                        ->label('Subcuenta de vivienda')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->validationMessages(['min' => 'La subcuenta no puede ser negativa.'])
                        ->hint('Saldo acumulado — obtenido del portal INFONAVIT/FOVISSSTE'),

                    // ── Botón acceso rápido al simulador ──────────────────
                    Forms\Components\Placeholder::make('acceso_simulador')
                        ->label('Simuladores oficiales')
                        ->columnSpanFull()
                        ->content(function (Forms\Get $get): \Illuminate\Support\HtmlString {
                            $curp = strtoupper(trim($get('curp') ?? ''));
                            $curpHint = $curp
                                ? "<span class='text-xs text-gray-500 ml-2'>CURP capturada: <strong>{$curp}</strong> — ingrésala en el simulador</span>"
                                : "<span class='text-xs text-gray-400 ml-2'>Captura la CURP arriba antes de abrir el simulador</span>";

                            return new \Illuminate\Support\HtmlString(
                                '<div class="flex flex-wrap gap-3 items-center">'
                                . '<a href="https://inscripcioncontinua.fovissste.gob.mx/simulador/" target="_blank" rel="noopener" '
                                . 'class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white '
                                . 'bg-green-700 hover:bg-green-800 transition-colors shadow-sm">'
                                . '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>'
                                . 'Simulador FOVISSSTE'
                                . '</a>'
                                . '<a href="https://micuenta.infonavit.org.mx/" target="_blank" rel="noopener" '
                                . 'class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white '
                                . 'bg-red-700 hover:bg-red-800 transition-colors shadow-sm">'
                                . '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>'
                                . 'Portal INFONAVIT'
                                . '</a>'
                                . $curpHint
                                . '</div>'
                            );
                        }),

                    Forms\Components\Textarea::make('notas_precalificacion')
                        ->label('Resultado de la precalificación')
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Pega aquí el resultado del simulador o anota el monto aprobado y observaciones.'),
                ])->columns(2)->collapsible(),
        ]);
    }

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
                        'info'    => 'propuesta_enviada',
                        'success' => 'convertido',
                        'danger'  => 'descartado',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'nuevo'             => 'Nuevo',
                        'contactado'        => 'Contactado',
                        'precalificado'     => 'Precalificado',
                        'propuesta_enviada' => 'Propuesta enviada',
                        'convertido'        => 'Convertido',
                        'descartado'        => 'Descartado',
                        default             => $state,
                    }),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')->toggleable(),
                Tables\Columns\TextColumn::make('monto_credito_estimado')
                    ->label('Monto estimado')
                    ->money('MXN')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_prospecto')
                    ->label('Estado')
                    ->options([
                        'nuevo'             => 'Nuevo',
                        'contactado'        => 'Contactado',
                        'precalificado'     => 'Precalificado',
                        'propuesta_enviada' => 'Propuesta enviada',
                        'convertido'        => 'Convertido',
                        'descartado'        => 'Descartado',
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
                    ->options(User::pluck('name', 'id')),
            ])
            ->actions([
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
