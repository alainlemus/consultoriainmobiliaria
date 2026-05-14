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
use Illuminate\Database\Eloquent\Model;
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

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'folio',
            'acreditado_nombre',
            'acreditado_curp',
            'acreditado_rfc',
            'acreditado_telefono',
            'acreditado_email',
            'acreditado_numero_credito',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->folio . ' — ' . ($record->acreditado_nombre ?: 'Sin nombre');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Trámite'  => $record->tipoTramite?->nombre ?? '—',
            'Estado'   => ucfirst(str_replace('_', ' ', $record->estado)),
            'Asesor'   => $record->asesor?->name ?? '—',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

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
            Forms\Components\Tabs::make('Expediente')
                ->tabs([

                    // ── TAB 1: TRÁMITE ────────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Trámite')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            // ── Stepper de progreso ───────────────────────
                            Forms\Components\View::make('filament.components.expediente-stepper')
                                ->viewData(fn (Forms\Get $get, $record) => ['record' => $record])
                                ->columnSpanFull()
                                ->visible(fn ($record) => $record !== null),

                            Forms\Components\TextInput::make('folio')
                                ->label('Folio')
                                ->disabled()
                                ->dehydrated()
                                ->placeholder('Se genera automáticamente')
                                ->hint('Formato EXP-AÑO-NNNN, asignado por el sistema'),
                            Forms\Components\Select::make('tipo_tramite_id')
                                ->label('Tipo de Trámite')
                                ->options(TipoTramite::where('activo', true)->orderBy('orden')->pluck('nombre', 'id'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('etapa_tramite_id', null))
                                ->hint('Selecciona primero para cargar las etapas'),
                            Forms\Components\Select::make('etapa_tramite_id')
                                ->label('Etapa actual')
                                ->options(fn (Forms\Get $get) =>
                                    EtapaTramite::where('tipo_tramite_id', $get('tipo_tramite_id'))
                                        ->orderBy('orden')
                                        ->pluck('nombre', 'id')
                                )
                                ->required()
                                ->searchable()
                                ->hint('Depende del tipo de trámite seleccionado'),
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
                                ->required()
                                ->hint('Al cambiar a "Cerrado" se genera la comisión automáticamente'),
                            Forms\Components\Select::make('asesor_id')
                                ->label('Asesor asignado')
                                ->options(User::where('activo', true)->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->hint('Asesor responsable del seguimiento'),
                            Forms\Components\Select::make('contacto_id')
                                ->label('Prospecto origen')
                                ->options(Contacto::pluck('nombre', 'id'))
                                ->searchable()
                                ->nullable()
                                ->hint('Opcional — vincula con el prospecto del sitio web'),
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
                                ->default(now())
                                ->hint('Fecha en que se abre formalmente el expediente'),
                        ])->columns(2),

                    // ── TAB 2: ACREDITADO ─────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Acreditado')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\TextInput::make('acreditado_nombre')
                                ->label('Nombre completo')
                                ->required()
                                ->maxLength(255)
                                ->hint('Nombre como aparece en identificación oficial'),
                            Forms\Components\TextInput::make('acreditado_curp')
                                ->label('CURP')
                                ->maxLength(18)
                                ->minLength(18)
                                ->regex('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i')
                                ->validationMessages(['regex' => 'La CURP no tiene el formato correcto (18 caracteres, ej: GOML800101HDFRZS09).'])
                                ->extraAttributes(['style' => 'text-transform:uppercase'])
                                ->hint('18 caracteres — requerido para INFONAVIT/FOVISSSTE'),
                            Forms\Components\TextInput::make('acreditado_rfc')
                                ->label('RFC')
                                ->maxLength(13)
                                ->minLength(12)
                                ->regex('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i')
                                ->validationMessages(['regex' => 'El RFC no tiene el formato correcto (12 o 13 caracteres, ej: GOML800101AB2).'])
                                ->extraAttributes(['style' => 'text-transform:uppercase'])
                                ->hint('12-13 caracteres con homoclave'),
                            Forms\Components\DatePicker::make('acreditado_fecha_nacimiento')
                                ->label('Fecha de nacimiento')
                                ->before('today')
                                ->validationMessages(['before' => 'La fecha de nacimiento debe ser anterior a hoy.']),
                            Forms\Components\TextInput::make('acreditado_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->regex('/^\d{10}$/')
                                ->validationMessages(['regex' => 'El teléfono debe tener exactamente 10 dígitos numéricos.'])
                                ->maxLength(10)
                                ->hint('10 dígitos sin espacios ni guiones'),
                            Forms\Components\TextInput::make('acreditado_email')
                                ->label('Correo')
                                ->email()
                                ->maxLength(150)
                                ->validationMessages(['email' => 'Ingresa un correo electrónico válido.']),
                            Forms\Components\Select::make('acreditado_estado_civil')
                                ->label('Estado civil')
                                ->required()
                                ->options([
                                    'soltero'     => 'Soltero(a)',
                                    'casado'      => 'Casado(a)',
                                    'union_libre' => 'Unión libre',
                                    'divorciado'  => 'Divorciado(a)',
                                    'viudo'       => 'Viudo(a)',
                                ])
                                ->validationMessages(['required' => 'El estado civil es obligatorio para las escrituras.'])
                                ->hint('Importante para el régimen patrimonial en escrituras'),
                            Forms\Components\TextInput::make('acreditado_antiguedad_laboral')
                                ->label('Antigüedad laboral (años)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(50)
                                ->validationMessages([
                                    'min' => 'La antigüedad no puede ser negativa.',
                                    'max' => 'La antigüedad no puede superar 50 años.',
                                ])
                                ->hint('Años completos de cotización'),
                            Forms\Components\TextInput::make('acreditado_numero_credito')
                                ->label('Número de crédito asignado')
                                ->maxLength(50)
                                ->hint('Se asigna una vez que la institución aprueba el crédito'),
                            Forms\Components\TextInput::make('obligado_solidario_nombre')
                                ->label('Obligado solidario (nombre completo)')
                                ->maxLength(255)
                                ->columnSpanFull()
                                ->hint('Aparece en el bloque de firmas del contrato'),
                            Forms\Components\TextInput::make('acreditado_domicilio')
                                ->label('Calle y número')
                                ->maxLength(255)
                                ->columnSpanFull()
                                ->hint('Ej: Av. Insurgentes Sur 1234 Int. 5'),
                            Forms\Components\TextInput::make('acreditado_colonia')
                                ->label('Colonia')
                                ->maxLength(150),
                            Forms\Components\TextInput::make('acreditado_municipio')
                                ->label('Municipio / Alcaldía')
                                ->maxLength(150),
                            Forms\Components\TextInput::make('acreditado_estado')
                                ->label('Estado')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('acreditado_cp')
                                ->label('Código postal')
                                ->regex('/^\d{5}$/')
                                ->maxLength(5)
                                ->validationMessages(['regex' => 'El código postal debe tener exactamente 5 dígitos.'])
                                ->hint('5 dígitos numéricos'),
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
                        ])->columns(2),

                    // ── TAB 3: VENDEDOR ───────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Vendedor')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Forms\Components\Placeholder::make('_vendedor_info')
                                ->label('')
                                ->columnSpanFull()
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<p class="text-sm text-gray-500">Solo aplica en trámites de compraventa. En retiro directo puedes omitir esta sección.</p>'
                                )),
                            Forms\Components\TextInput::make('vendedor_nombre')
                                ->label('Nombre completo')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('vendedor_curp')
                                ->label('CURP')
                                ->maxLength(18)
                                ->minLength(18)
                                ->regex('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i')
                                ->validationMessages(['regex' => 'La CURP del vendedor no tiene el formato correcto.'])
                                ->extraAttributes(['style' => 'text-transform:uppercase']),
                            Forms\Components\TextInput::make('vendedor_rfc')
                                ->label('RFC')
                                ->maxLength(13)
                                ->minLength(12)
                                ->regex('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i')
                                ->validationMessages(['regex' => 'El RFC del vendedor no tiene el formato correcto.'])
                                ->extraAttributes(['style' => 'text-transform:uppercase']),
                            Forms\Components\TextInput::make('vendedor_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->regex('/^\d{10}$/')
                                ->maxLength(10)
                                ->validationMessages(['regex' => 'El teléfono del vendedor debe tener exactamente 10 dígitos.']),
                            Forms\Components\TextInput::make('vendedor_email')
                                ->label('Correo')
                                ->email()
                                ->maxLength(150)
                                ->validationMessages(['email' => 'Ingresa un correo electrónico válido para el vendedor.']),
                            Forms\Components\TextInput::make('vendedor_domicilio')
                                ->label('Domicilio')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('vendedor_banco')
                                ->label('Banco destino')
                                ->maxLength(100)
                                ->hint('Banco donde se depositará el pago al vendedor'),
                            Forms\Components\TextInput::make('vendedor_clabe')
                                ->label('CLABE interbancaria')
                                ->maxLength(18)
                                ->minLength(18)
                                ->regex('/^\d{18}$/')
                                ->validationMessages(['regex' => 'La CLABE debe tener exactamente 18 dígitos numéricos.'])
                                ->hint('18 dígitos — para transferencia del crédito al vendedor'),
                            Forms\Components\Toggle::make('vendedor_requiere_acta_matrimonio')
                                ->label('Requiere acta de matrimonio')
                                ->hint('Activar si el vendedor está casado bajo sociedad conyugal')
                                ->columnSpanFull(),
                        ])->columns(2),

                    // ── TAB 4: VIVIENDA ───────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Vivienda')
                        ->icon('heroicon-o-home')
                        ->schema([
                            Forms\Components\Select::make('vivienda_tipo')
                                ->label('Tipo de inmueble')
                                ->options([
                                    'casa'         => 'Casa',
                                    'departamento' => 'Departamento',
                                    'terreno'      => 'Terreno',
                                ]),
                            Forms\Components\TextInput::make('vivienda_superficie')
                                ->label('Superficie (m²)')
                                ->numeric()
                                ->minValue(0)
                                ->validationMessages(['min' => 'La superficie no puede ser negativa.']),
                            Forms\Components\TextInput::make('vivienda_calle')
                                ->label('Calle')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('vivienda_numero')
                                ->label('Número exterior/interior')
                                ->maxLength(30)
                                ->hint('Ej: 45 Int. 3-B'),
                            Forms\Components\TextInput::make('vivienda_colonia')
                                ->label('Colonia')
                                ->maxLength(150),
                            Forms\Components\TextInput::make('vivienda_cp')
                                ->label('Código postal')
                                ->maxLength(5)
                                ->regex('/^\d{5}$/')
                                ->validationMessages(['regex' => 'El código postal de la vivienda debe tener exactamente 5 dígitos.']),
                            Forms\Components\TextInput::make('vivienda_municipio')
                                ->label('Municipio')
                                ->maxLength(150),
                            Forms\Components\TextInput::make('vivienda_estado')
                                ->label('Estado')
                                ->maxLength(100),
                            Forms\Components\Textarea::make('vivienda_descripcion_titulo')
                                ->label('Datos del título de propiedad')
                                ->hint('Folio real, notaría, número de escritura y fecha — como aparece en el título')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])->columns(2),

                    // ── TAB 5: FINANCIERO ─────────────────────────────────
                    Forms\Components\Tabs\Tab::make('Financiero')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\Section::make('Montos del crédito')
                                ->description('Al marcar "Honorarios cobrados" y cambiar el estado a "Cerrado", el sistema generará automáticamente la comisión del asesor.')
                                ->schema([
                                    Forms\Components\TextInput::make('monto_credito')
                                        ->label('Monto del crédito')
                                        ->numeric()->prefix('$')
                                        ->minValue(0)
                                        ->validationMessages(['min' => 'El monto del crédito no puede ser negativo.'])
                                        ->hint('Monto aprobado por la institución'),
                                    Forms\Components\TextInput::make('subcuenta_vivienda')
                                        ->label('Subcuenta de vivienda')
                                        ->numeric()->prefix('$')
                                        ->minValue(0)
                                        ->hint('Saldo acumulado INFONAVIT/FOVISSSTE'),
                                    Forms\Components\TextInput::make('monto_total_estimado')
                                        ->label('Monto total estimado')
                                        ->numeric()->prefix('$')
                                        ->minValue(0)
                                        ->hint('Crédito + subcuenta + otros recursos'),
                                    Forms\Components\TextInput::make('total_gastos_financiados')
                                        ->label('Total gastos financiados')
                                        ->numeric()->prefix('$')
                                        ->disabled()
                                        ->hint('Calculado automáticamente'),
                                ])->columns(2),
                            Forms\Components\Section::make('Honorarios')
                                ->visible(fn () => Auth::user()?->hasRole('super_admin'))
                                ->schema([
                                    Forms\Components\TextInput::make('honorarios_porcentaje')
                                        ->label('% Honorarios')
                                        ->numeric()->suffix('%')
                                        ->minValue(0)->maxValue(100)
                                        ->validationMessages([
                                            'min' => 'El porcentaje no puede ser negativo.',
                                            'max' => 'El porcentaje no puede superar 100%.',
                                        ])
                                        ->hint('Porcentaje sobre el monto total'),
                                    Forms\Components\TextInput::make('honorarios_monto')
                                        ->label('Monto de honorarios')
                                        ->numeric()->prefix('$')
                                        ->minValue(0)
                                        ->hint('Monto fijo pactado con el cliente'),
                                    Forms\Components\Toggle::make('honorarios_pagados')
                                        ->label('Honorarios cobrados')
                                        ->hint('Activa cuando el cliente haya pagado'),
                                    Forms\Components\DatePicker::make('fecha_pago_honorarios')
                                        ->label('Fecha de cobro')
                                        ->beforeOrEqual('today')
                                        ->validationMessages(['before_or_equal' => 'La fecha de cobro no puede ser futura.']),
                                    Forms\Components\DatePicker::make('fecha_cierre')
                                        ->label('Fecha de cierre')
                                        ->beforeOrEqual('today')
                                        ->hint('Fecha en que se formalizó el trámite'),
                                ])->columns(2),
                            Forms\Components\Section::make('Notas internas')
                                ->description('Visible solo para el equipo admin.')
                                ->schema([
                                    Forms\Components\Textarea::make('notas_internas')
                                        ->label('Notas')
                                        ->placeholder('Ej: Cliente confirmó que entregará el acta la próxima semana...')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                ])->columnSpanFull()->persistTabInQueryString(),
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
                    ->label('Progreso')
                    ->getStateUsing(function ($record) {
                        if (! $record->etapa || ! $record->tipo_tramite_id) return '—';

                        $etapas = \App\Models\EtapaTramite::where('tipo_tramite_id', $record->tipo_tramite_id)
                            ->orderBy('orden')->get();
                        $total   = $etapas->count();
                        $current = $record->etapa->orden;

                        $dots = '';
                        foreach ($etapas as $etapa) {
                            if ($etapa->orden < $current) {
                                // completada
                                $dots .= '<span title="' . e($etapa->nombre) . '" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#16a34a;margin:0 2px;vertical-align:middle;"></span>';
                            } elseif ($etapa->orden === $current) {
                                // actual
                                $dots .= '<span title="' . e($etapa->nombre) . '" style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#2563eb;margin:0 2px;vertical-align:middle;outline:2px solid #93c5fd;outline-offset:1px;"></span>';
                            } else {
                                // pendiente
                                $dots .= '<span title="' . e($etapa->nombre) . '" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#d1d5db;margin:0 2px;vertical-align:middle;"></span>';
                            }
                        }

                        return '<div style="display:flex;align-items:center;gap:4px;">'
                            . $dots
                            . '<span style="font-size:0.7rem;color:#6b7280;margin-left:4px;">' . $current . '/' . $total . '</span>'
                            . '</div>';
                    })
                    ->html()
                    ->tooltip(fn ($record) => $record?->etapa?->nombre ?? ''),
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
                    })
                    ->tooltip('Al cerrar se genera la comisión automáticamente'),
                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor')
                    ->toggleable()
                    ->tooltip('Asesor responsable del expediente'),
                Tables\Columns\TextColumn::make('monto_credito')
                    ->label('Monto crédito')
                    ->money('MXN')
                    ->toggleable()
                    ->tooltip('Monto del crédito aprobado por la institución'),
                Tables\Columns\TextColumn::make('honorarios_monto')
                    ->label('Honorarios')
                    ->money('MXN')
                    ->toggleable()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin'))
                    ->tooltip('Honorarios pactados con el cliente'),
                Tables\Columns\IconColumn::make('honorarios_pagados')
                    ->label('Cobrado')
                    ->boolean()
                    ->alignCenter()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin'))
                    ->tooltip('Indica si los honorarios ya fueron cobrados al cliente'),
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
                    ->label('Honorarios cobrados')
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
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
