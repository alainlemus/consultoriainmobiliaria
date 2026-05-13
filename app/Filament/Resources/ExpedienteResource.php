<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpedienteResource\Pages;
use App\Filament\Resources\ExpedienteResource\RelationManagers;
use App\Models\Contacto;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExpedienteResource extends Resource
{
    protected static ?string $model = Expediente::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationLabel = 'Expedientes';
    protected static ?string $modelLabel = 'Expediente';
    protected static ?string $pluralModelLabel = 'Expedientes';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'folio';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Asesores solo ven sus propios expedientes
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::whereIn('estado', ['en_proceso', 'aprobado', 'firmado']);

        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        $count = $query->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── ENCABEZADO DEL EXPEDIENTE ─────────────────────────────────
            Forms\Components\Section::make('Información del Trámite')->schema([
                Forms\Components\TextInput::make('folio')
                    ->label('Folio')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('Se genera automáticamente'),
                Forms\Components\Select::make('tipo_tramite_id')
                    ->label('Tipo de Trámite')
                    ->options(TipoTramite::where('activo', true)->orderBy('orden')->pluck('nombre', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('etapa_tramite_id', null)),
                Forms\Components\Select::make('etapa_tramite_id')
                    ->label('Etapa actual')
                    ->options(fn (Forms\Get $get) =>
                        EtapaTramite::where('tipo_tramite_id', $get('tipo_tramite_id'))
                            ->orderBy('orden')
                            ->pluck('nombre', 'id')
                    )
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('estado')
                    ->label('Estado general')
                    ->options([
                        'en_proceso' => 'En proceso',
                        'pausado'    => 'Pausado',
                        'aprobado'   => 'Aprobado',
                        'firmado'    => 'Firmado',
                        'cerrado'    => 'Cerrado',
                        'cancelado'  => 'Cancelado',
                    ])
                    ->default('en_proceso')
                    ->required(),
                Forms\Components\Select::make('asesor_id')
                    ->label('Asesor asignado')
                    ->options(User::where('activo', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('contacto_id')
                    ->label('Prospecto origen')
                    ->options(Contacto::pluck('nombre', 'id'))
                    ->searchable()
                    ->nullable(),
                Forms\Components\Select::make('uso_credito')
                    ->label('Uso del crédito')
                    ->options([
                        'retiro_directo' => 'Retiro directo',
                        'compraventa'    => 'Compraventa',
                        'construccion'   => 'Construcción',
                        'otro'           => 'Otro',
                    ])
                    ->default('retiro_directo')
                    ->required(),
                Forms\Components\DatePicker::make('fecha_apertura')
                    ->label('Fecha de apertura')
                    ->default(now()),
            ])->columns(2),

            // ── SECCIÓN A: ACREDITADO ─────────────────────────────────────
            Forms\Components\Section::make('Sección A — Acreditado (Cliente)')
                ->schema([
                    Forms\Components\TextInput::make('acreditado_nombre')
                        ->label('Nombre completo')->required(),
                    Forms\Components\TextInput::make('acreditado_curp')
                        ->label('CURP')->maxLength(18)
                        ->extraAttributes(['style' => 'text-transform:uppercase']),
                    Forms\Components\TextInput::make('acreditado_rfc')
                        ->label('RFC')->maxLength(13)
                        ->extraAttributes(['style' => 'text-transform:uppercase']),
                    Forms\Components\DatePicker::make('acreditado_fecha_nacimiento')
                        ->label('Fecha de nacimiento'),
                    Forms\Components\TextInput::make('acreditado_telefono')
                        ->label('Teléfono'),
                    Forms\Components\TextInput::make('acreditado_email')
                        ->label('Correo')->email(),
                    Forms\Components\TextInput::make('acreditado_domicilio')
                        ->label('Calle y número')->columnSpanFull(),
                    Forms\Components\TextInput::make('acreditado_colonia')
                        ->label('Colonia'),
                    Forms\Components\TextInput::make('acreditado_municipio')
                        ->label('Municipio'),
                    Forms\Components\TextInput::make('acreditado_estado')
                        ->label('Estado'),
                    Forms\Components\TextInput::make('acreditado_cp')
                        ->label('Código postal')->maxLength(10),
                    Forms\Components\Select::make('acreditado_estado_civil')
                        ->label('Estado civil')
                        ->options([
                            'soltero'     => 'Soltero(a)',
                            'casado'      => 'Casado(a)',
                            'union_libre' => 'Unión libre',
                            'divorciado'  => 'Divorciado(a)',
                            'viudo'       => 'Viudo(a)',
                        ]),
                    Forms\Components\TextInput::make('acreditado_antiguedad_laboral')
                        ->label('Antigüedad laboral (años)')->numeric(),
                    Forms\Components\TextInput::make('acreditado_numero_credito')
                        ->label('Número de crédito asignado'),

                    // ── Acceso rápido simuladores ─────────────────────────
                    Forms\Components\Placeholder::make('acceso_simulador_exp')
                        ->label('Consultar crédito disponible')
                        ->columnSpanFull()
                        ->content(function (Forms\Get $get): \Illuminate\Support\HtmlString {
                            $curp = strtoupper(trim($get('acreditado_curp') ?? ''));
                            $hint = $curp
                                ? "<span class='text-xs text-gray-500 ml-2'>CURP: <strong>{$curp}</strong></span>"
                                : "<span class='text-xs text-gray-400 ml-2'>Captura la CURP del acreditado arriba</span>";

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
                                . $hint
                                . '</div>'
                            );
                        }),
                ])->columns(2)->collapsible(),

            // ── SECCIÓN B: VENDEDOR ───────────────────────────────────────
            Forms\Components\Section::make('Sección B — Vendedor / Propietario')
                ->schema([
                    Forms\Components\TextInput::make('vendedor_nombre')
                        ->label('Nombre completo'),
                    Forms\Components\TextInput::make('vendedor_curp')
                        ->label('CURP')->maxLength(18)
                        ->extraAttributes(['style' => 'text-transform:uppercase']),
                    Forms\Components\TextInput::make('vendedor_rfc')
                        ->label('RFC')->maxLength(13)
                        ->extraAttributes(['style' => 'text-transform:uppercase']),
                    Forms\Components\TextInput::make('vendedor_telefono')
                        ->label('Teléfono'),
                    Forms\Components\TextInput::make('vendedor_email')
                        ->label('Correo')->email(),
                    Forms\Components\TextInput::make('vendedor_domicilio')
                        ->label('Domicilio')->columnSpanFull(),
                    Forms\Components\Toggle::make('vendedor_requiere_acta_matrimonio')
                        ->label('Requiere acta de matrimonio'),
                    Forms\Components\TextInput::make('vendedor_banco')
                        ->label('Banco destino'),
                    Forms\Components\TextInput::make('vendedor_clabe')
                        ->label('CLABE interbancaria')->maxLength(18),
                ])->columns(2)->collapsible(),

            // ── SECCIÓN C: VIVIENDA ───────────────────────────────────────
            Forms\Components\Section::make('Sección C — Vivienda / Propiedad')
                ->schema([
                    Forms\Components\Select::make('vivienda_tipo')
                        ->label('Tipo')
                        ->options([
                            'casa'        => 'Casa',
                            'departamento'=> 'Departamento',
                            'terreno'     => 'Terreno',
                        ]),
                    Forms\Components\TextInput::make('vivienda_calle')
                        ->label('Calle'),
                    Forms\Components\TextInput::make('vivienda_numero')
                        ->label('Número exterior/interior'),
                    Forms\Components\TextInput::make('vivienda_colonia')
                        ->label('Colonia'),
                    Forms\Components\TextInput::make('vivienda_municipio')
                        ->label('Municipio'),
                    Forms\Components\TextInput::make('vivienda_estado')
                        ->label('Estado'),
                    Forms\Components\TextInput::make('vivienda_cp')
                        ->label('Código postal')->maxLength(10),
                    Forms\Components\Textarea::make('vivienda_descripcion_titulo')
                        ->label('Datos del título de propiedad')
                        ->rows(2)->columnSpanFull(),
                ])->columns(2)->collapsible(),

            // ── FINANCIERO ────────────────────────────────────────────────
            Forms\Components\Section::make('Datos Financieros')
                ->schema([
                    Forms\Components\TextInput::make('monto_credito')
                        ->label('Monto del crédito')->numeric()->prefix('$'),
                    Forms\Components\TextInput::make('subcuenta_vivienda')
                        ->label('Subcuenta de vivienda')->numeric()->prefix('$'),
                    Forms\Components\TextInput::make('monto_total_estimado')
                        ->label('Monto total estimado')->numeric()->prefix('$'),
                    Forms\Components\TextInput::make('honorarios_porcentaje')
                        ->label('% Honorarios')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('honorarios_monto')
                        ->label('Monto de honorarios')->numeric()->prefix('$'),
                    Forms\Components\TextInput::make('total_gastos_financiados')
                        ->label('Total gastos financiados')->numeric()->prefix('$')->disabled(),
                    Forms\Components\Toggle::make('honorarios_pagados')
                        ->label('Honorarios cobrados'),
                    Forms\Components\DatePicker::make('fecha_pago_honorarios')
                        ->label('Fecha de cobro'),
                    Forms\Components\DatePicker::make('fecha_cierre')
                        ->label('Fecha de cierre'),
                ])->columns(2)->collapsible(),

            // ── NOTAS ─────────────────────────────────────────────────────
            Forms\Components\Section::make('Notas internas')->schema([
                Forms\Components\Textarea::make('notas_internas')
                    ->label('Notas')
                    ->rows(4)
                    ->columnSpanFull(),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('acreditado_nombre')
                    ->label('Acreditado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipoTramite.nombre')
                    ->label('Tipo')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('etapa.nombre')
                    ->label('Etapa')
                    ->badge()
                    ->color(fn ($record) => $record?->etapa?->color ?? 'gray'),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'gray'    => 'en_proceso',
                        'warning' => 'pausado',
                        'primary' => 'aprobado',
                        'info'    => 'firmado',
                        'success' => 'cerrado',
                        'danger'  => 'cancelado',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'en_proceso' => 'En proceso',
                        'pausado'    => 'Pausado',
                        'aprobado'   => 'Aprobado',
                        'firmado'    => 'Firmado',
                        'cerrado'    => 'Cerrado',
                        'cancelado'  => 'Cancelado',
                        default      => $state,
                    }),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('monto_credito')
                    ->label('Monto crédito')
                    ->money('MXN')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('honorarios_monto')
                    ->label('Honorarios')
                    ->money('MXN')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('honorarios_pagados')
                    ->label('Cobrado')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('fecha_apertura')
                    ->label('Apertura')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'en_proceso' => 'En proceso',
                        'pausado'    => 'Pausado',
                        'aprobado'   => 'Aprobado',
                        'firmado'    => 'Firmado',
                        'cerrado'    => 'Cerrado',
                        'cancelado'  => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_tramite_id')
                    ->label('Tipo de trámite')
                    ->options(TipoTramite::pluck('nombre', 'id')),
                Tables\Filters\SelectFilter::make('asesor_id')
                    ->label('Asesor')
                    ->options(User::pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('honorarios_pagados')
                    ->label('Honorarios cobrados'),
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
        return [
            RelationManagers\DocumentosRelationManager::class,
            RelationManagers\GastosRelationManager::class,
            RelationManagers\SeguimientosRelationManager::class,
            RelationManagers\ComisionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpedientes::route('/'),
            'create' => Pages\CreateExpediente::route('/create'),
            'edit'   => Pages\EditExpediente::route('/{record}/edit'),
        ];
    }
}
