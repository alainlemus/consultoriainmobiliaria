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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ExpedienteResource extends Resource
{
    protected static ?string $model = Expediente::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationLabel = 'Expedientes';
    protected static ?string $modelLabel = 'Expediente';
    protected static ?string $pluralModelLabel = 'Expedientes';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
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

        // Solo el asesor ve únicamente sus propios expedientes
        if (Auth::check() && Auth::user()->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::whereIn('estado', [
            'en_proceso', 'documentacion', 'en_catastro',
            'pre_avaluo', 'cuv_generada', 'avaluo_cerrado',
            'en_notaria', 'firmado',
        ]);

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

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Expediente')
                ->tabs([

                    // ── TAB 1: TRÁMITE ────────────────────────────────────
                    Tabs\Tab::make('Trámite')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            // ── Stepper de progreso ───────────────────────
                            SchemaView::make('filament.components.expediente-stepper')
                                ->viewData(fn ($livewire) => [
                                    'record'       => $livewire->getRecord(),
                                    'etapaActivaId' => $livewire->data['etapa_tramite_id'] ?? $livewire->getRecord()?->etapa_tramite_id,
                                ])
                                ->columnSpanFull()
                                ->visible(fn ($livewire) => $livewire->getRecord() !== null),

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
                                ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, $state) {
                                    // Auto-seleccionar la primera etapa al cambiar el tipo de trámite
                                    $primera = $state
                                        ? \App\Models\EtapaTramite::where('tipo_tramite_id', $state)->orderBy('orden')->first()
                                        : null;
                                    $set('etapa_tramite_id', $primera?->id);
                                })
                                ->validationMessages([
                                    'required' => 'Debes seleccionar el tipo de trámite.',
                                ]),
                            Forms\Components\Select::make('etapa_tramite_id')
                                ->label('Etapa actual')
                                ->options(function (\Filament\Schemas\Components\Utilities\Get $get, $livewire) {
                                    $tipoId = $get('tipo_tramite_id')
                                        ?? $livewire->getRecord()?->tipo_tramite_id;

                                    if (! $tipoId) return [];

                                    return EtapaTramite::where('tipo_tramite_id', $tipoId)
                                        ->orderBy('orden')
                                        ->pluck('nombre', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->live()
                                ->hidden(fn ($livewire) =>
                                    ! auth()->user()?->hasRole('super_admin') ||
                                    $livewire instanceof \App\Filament\Resources\ExpedienteResource\Pages\EditExpediente
                                )
                                ->dehydrated(fn ($livewire) =>
                                    auth()->user()?->hasRole('super_admin') &&
                                    ! ($livewire instanceof \App\Filament\Resources\ExpedienteResource\Pages\EditExpediente)
                                )
                                ->validationMessages([
                                    'required' => 'La etapa del trámite es obligatoria.',
                                ]),
                            Forms\Components\Placeholder::make('etapa_tramite_display')
                                ->label('Etapa actual')
                                ->content(fn ($record) => $record?->etapa?->nombre ?? '—')
                                ->visible(fn ($livewire) =>
                                    ! auth()->user()?->hasRole('super_admin') ||
                                    $livewire instanceof \App\Filament\Resources\ExpedienteResource\Pages\EditExpediente
                                ),
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
                                ->hidden(fn () => ! auth()->user()?->hasRole('super_admin'))
                                ->dehydrated(fn () => auth()->user()?->hasRole('super_admin'))
                                ->hint('Al cambiar a "Cerrado" se genera la comisión automáticamente')
                                ->validationMessages([
                                    'required' => 'El estado del expediente es obligatorio.',
                                ]),
                            Forms\Components\Placeholder::make('estado_display')
                                ->label('Estado')
                                ->content(fn ($record) => match($record?->estado) {
                                    'en_proceso' => 'En proceso',
                                    'pausado'    => 'Pausado',
                                    'aprobado'   => 'Aprobado',
                                    'firmado'    => 'Firmado',
                                    'cerrado'    => 'Cerrado',
                                    'cancelado'  => 'Cancelado',
                                    default      => '—',
                                })
                                ->visible(fn () => ! auth()->user()?->hasRole('super_admin')),
                            Forms\Components\Select::make('asesor_id')
                                ->label('Asesor asignado')
                                ->options(User::where('activo', true)->pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->hint('Asesor responsable del seguimiento')
                                ->validationMessages([
                                    'required' => 'Debes asignar un asesor al expediente.',
                                ]),
                            Forms\Components\Select::make('contacto_id')
                                ->label('Prospecto origen')
                                ->options(Contacto::pluck('nombre', 'id'))
                                ->searchable()
                                ->nullable()
                                ->live()
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
                                ->required()
                                ->live()
                                ->validationMessages([
                                    'required' => 'Debes indicar el uso del crédito.',
                                ]),
                            Forms\Components\Select::make('modalidad_credito')
                                ->label('Modalidad')
                                ->options([
                                    'individual'   => 'Individual',
                                    'mancomunado'  => 'Mancomunado (con cónyuge)',
                                ])
                                ->default('individual')
                                ->live()
                                ->hint('Mancomunado suma la capacidad de crédito de ambos cónyuges'),
                            Forms\Components\Select::make('banco_participante')
                                ->label('Banco participante')
                                ->options([
                                    'HSBC'    => 'HSBC',
                                    'Banorte' => 'Banorte',
                                    'BBVA'    => 'BBVA',
                                ])
                                ->nullable()
                                ->hint('Solo aplica a FOVISSSTE Para Todos'),
                            Forms\Components\DatePicker::make('fecha_apertura')
                                ->label('Fecha de apertura')
                                ->default(now())
                                ->hint('Fecha en que se abre formalmente el expediente'),
                            Forms\Components\Textarea::make('notas_internas')
                                ->label('Notas internas')
                                ->placeholder('Ej: Cliente confirmó que entregará el acta la próxima semana...')
                                ->rows(3)
                                ->columnSpanFull(),

                            // ── Montos del crédito ────────────────────────────
                            \Filament\Schemas\Components\Section::make('Montos del crédito')
                                ->description('Al marcar "Honorarios cobrados" y cambiar el estado a "Cerrado", el sistema generará automáticamente la comisión del asesor.')
                                ->columnSpanFull()
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
                                        ->hint('Saldo acumulado INFONAVIT/FOVISSSTE')
                                        ->validationMessages([
                                            'min' => 'La subcuenta de vivienda no puede ser negativa.',
                                        ]),
                                    Forms\Components\TextInput::make('monto_total_estimado')
                                        ->label('Monto total estimado')
                                        ->numeric()->prefix('$')
                                        ->minValue(0)
                                        ->hint('Crédito + subcuenta + otros recursos')
                                        ->validationMessages([
                                            'min' => 'El monto total estimado no puede ser negativo.',
                                        ])
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $total = floatval($state);
                                            $pct   = floatval($get('honorarios_porcentaje') ?? 0);
                                            $set('honorarios_monto', $total > 0 && $pct > 0
                                                ? round($total * $pct / 100, 2)
                                                : null
                                            );
                                        }),
                                    Forms\Components\TextInput::make('total_gastos_financiados')
                                        ->label('Total gastos financiados')
                                        ->numeric()->prefix('$')
                                        ->disabled()
                                        ->hint('Calculado automáticamente'),
                                ])->columns(2),

                            // ── Honorarios (solo super_admin) ─────────────────
                            \Filament\Schemas\Components\Section::make('Honorarios')
                                ->visible(fn () => Auth::user()?->hasRole('super_admin'))
                                ->columnSpanFull()
                                ->schema([
                                    Forms\Components\TextInput::make('honorarios_porcentaje')
                                        ->label('% Honorarios')
                                        ->numeric()->suffix('%')
                                        ->minValue(0)->maxValue(100)
                                        ->validationMessages([
                                            'min' => 'El porcentaje no puede ser negativo.',
                                            'max' => 'El porcentaje no puede superar 100%.',
                                        ])
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $pct   = floatval($state);
                                            $total = floatval($get('monto_total_estimado') ?? 0);
                                            $set('honorarios_monto', $total > 0 && $pct > 0
                                                ? round($total * $pct / 100, 2)
                                                : null
                                            );
                                        }),
                                    Forms\Components\TextInput::make('honorarios_monto')
                                        ->label('Monto de honorarios (calculado)')
                                        ->prefix('$')
                                        ->disabled()
                                        ->dehydrated()
                                        ->placeholder('Se calcula automáticamente')
                                        ->hint(fn (\Filament\Schemas\Components\Utilities\Get $get) => ($get('honorarios_porcentaje') && $get('monto_total_estimado'))
                                            ? number_format(floatval($get('monto_total_estimado')) * floatval($get('honorarios_porcentaje')) / 100, 2) . ' MXN'
                                            : 'Captura el % y el monto total estimado'
                                        ),
                                    Forms\Components\Toggle::make('honorarios_pagados')
                                        ->label('Honorarios cobrados')
                                        ->hint('Activa cuando el cliente haya pagado')
                                        ->visible(fn ($record) => $record?->etapa?->es_final ?? false),
                                    Forms\Components\DatePicker::make('fecha_pago_honorarios')
                                        ->label('Fecha de cobro')
                                        ->beforeOrEqual('today')
                                        ->validationMessages(['before_or_equal' => 'La fecha de cobro no puede ser futura.'])
                                        ->visible(fn ($record) => $record?->etapa?->es_final ?? false),
                                    Forms\Components\DatePicker::make('fecha_cierre')
                                        ->label('Fecha de cierre')
                                        ->beforeOrEqual('today')
                                        ->hint('Fecha en que se formalizó el trámite')
                                        ->validationMessages([
                                            'before_or_equal' => 'La fecha de cierre no puede ser futura.',
                                        ]),
                                ])->columns(2),
                        ])->columns(2),

                    // ── TAB 2: ACREDITADO ─────────────────────────────────
                     Tabs\Tab::make('Acreditado')
                         ->icon('heroicon-o-user')
                         ->schema([
                            // ── Datos del prospecto (readonly) ────────────────
                            \Filament\Schemas\Components\Section::make('Datos del prospecto')
                                ->description('Información registrada durante la etapa de prospección. Solo lectura.')
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('contacto_id'))
                                ->columnSpanFull()
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Placeholder::make('_prospecto_info')
                                        ->label('')
                                        ->columnSpanFull()
                                        ->content(function ($record): \Illuminate\Support\HtmlString {
                                            $c = $record?->contacto;
                                            if (! $c) return new \Illuminate\Support\HtmlString('');

                                            $fotoHtml = $c->foto_url
                                                ? "<img src='{$c->foto_url}' style='width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;' />"
                                                : "<div style='width:72px;height:72px;border-radius:50%;background:#1c1917;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#d97706;'>" . strtoupper(substr($c->nombre ?? '?', 0, 1)) . "</div>";

                                            $servicio = strtoupper($c->servicio ?? '');
                                            $row = fn(string $lbl, ?string $val) => $val
                                                ? "<tr><td style='padding:4px 12px 4px 0;font-size:13px;color:#6b7280;white-space:nowrap;font-weight:600;'>{$lbl}</td><td style='padding:4px 0;font-size:13px;color:#111827;'>" . e($val) . "</td></tr>"
                                                : '';

                                            $tabla  = '<table style="border-collapse:collapse;width:100%;">';
                                            $tabla .= $row('Teléfono', $c->telefono);
                                            $tabla .= $row('Correo',   $c->email);
                                            $tabla .= $row('CURP',     $c->curp);
                                            $tabla .= $row('NSS',      $c->nss);
                                            $tabla .= $row('Servicio', $servicio ?: null);

                                            if ($servicio === 'FOVISSSTE') {
                                                $tabla .= $row('Estado (crédito)',    $c->estado_uso_credito);
                                                $tabla .= $row('Municipio (crédito)', $c->municipio_uso_credito);
                                                $tabla .= $row('Estado (residencia)', $c->estado_residencia);
                                                if ($c->regimen_pensionario) {
                                                    $label = $c->regimen_pensionario === 'decimo_transitorio' ? 'Décimo Transitorio' : 'Cuenta Individual';
                                                    $tabla .= $row('Régimen', $label);
                                                }
                                                $tabla .= $row('Discapacidad', $c->tiene_discapacidad ? 'Sí' : 'No');
                                            }

                                            if ($servicio === 'INFONAVIT') {
                                                $tabla .= $row('Estado (crédito)',    $c->estado_uso_credito);
                                                $tabla .= $row('Municipio (crédito)', $c->municipio_uso_credito);
                                            }

                                            $tabla .= '</table>';

                                            $screenshotHtml = '';
                                            if ($c->simulador_screenshot_url) {
                                                $lbl = $servicio === 'INFONAVIT' ? 'Mi Cuenta INFONAVIT' : 'Simulador FOVISSSTE';
                                                $screenshotHtml = "<div style='margin-top:16px;'>"
                                                    . "<p style='font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;'>Captura {$lbl}</p>"
                                                    . "<a href='{$c->simulador_screenshot_url}' target='_blank' rel='noopener'>"
                                                    . "<img src='{$c->simulador_screenshot_url}' style='max-width:100%;max-height:300px;border-radius:8px;border:1px solid #e5e7eb;object-fit:contain;' /></a>"
                                                    . "</div>";
                                            }

                                            return new \Illuminate\Support\HtmlString(
                                                "<div style='display:flex;gap:20px;align-items:flex-start;'>"
                                                . "<div style='flex-shrink:0;'>{$fotoHtml}</div>"
                                                . "<div style='flex:1;'>{$tabla}{$screenshotHtml}</div>"
                                                . "</div>"
                                            );
                                        }),
                                ]),

                             Forms\Components\TextInput::make('acreditado_nombre')
                                ->label('Nombre completo')
                                ->required()
                                ->maxLength(255)
                                ->hint('Nombre como aparece en identificación oficial')
                                ->validationMessages([
                                    'required' => 'El nombre del acreditado es obligatorio.',
                                    'max'      => 'El nombre no puede superar los 255 caracteres.',
                                ]),
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
                                ->content(function (\Filament\Schemas\Components\Utilities\Get $get): \Illuminate\Support\HtmlString {
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

                            // ── Portal FOVISSSTE (Paso 4-J) ───────────────────
                            \Filament\Schemas\Components\Section::make('Portal FOVISSSTE — Inscripción al portal de activación continua')
                                ->description('Paso 4-J: generación de cuenta en el portal con CURP, correo y celular del acreditado')
                                ->columnSpanFull()
                                ->collapsible()
                                ->collapsed(fn ($record) => !$record?->portal_fovissste_activado)
                                ->schema([
                                    Forms\Components\Toggle::make('portal_fovissste_activado')
                                        ->label('Portal FOVISSSTE activado')
                                        ->helperText('Contraseña: primeros 10 dígitos del CURP en mayúsculas. El acreditado debe validar el correo (puede llegar a spam).')
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('portal_fovissste_notas')
                                        ->label('Notas del portal')
                                        ->rows(2)
                                        ->placeholder('Ej: correo validado el 15/06/2026, contraseña temporal cambiada')
                                        ->columnSpanFull(),
                                ]),
                        ])->columns(2),

                    // ── TAB 3: VENDEDOR ───────────────────────────────────
                    Tabs\Tab::make('Vendedor')
                        ->icon('heroicon-o-user-circle')
                        ->visible(fn (Get $get) => $get('uso_credito') === 'compraventa')
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

                            // ── Situación fiscal del vendedor (Paso 16) ───────
                            \Filament\Schemas\Components\Section::make('Situación fiscal del vendedor')
                                ->description('Exención de ISR en la venta de vivienda')
                                ->columnSpanFull()
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Toggle::make('vendedor_exencion_isr')
                                        ->label('¿Aplica exención de ISR?')
                                        ->helperText('El vendedor NO ha vendido otra propiedad en los últimos 3 años')
                                        ->reactive()
                                        ->columnSpanFull(),
                                    Forms\Components\Toggle::make('vendedor_requiere_avaluo_referido')
                                        ->label('¿Requiere avalúo referido?')
                                        ->helperText('Si el vendedor sí vendió antes de 3 años, requiere avalúo referido (tiene costo adicional)')
                                        ->visible(fn ($get) => !$get('vendedor_exencion_isr'))
                                        ->columnSpanFull(),
                                ]),
                        ])->columns(2),

                    // ── TAB 4: VIVIENDA ───────────────────────────────────
                    Tabs\Tab::make('Vivienda')
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
                                ->suffix('m²')
                                ->minValue(0)
                                ->validationMessages(['min' => 'La superficie no puede ser negativa.'])
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('superficie_total_predio')
                                ->label('Superficie total del predio (m²)')
                                ->numeric()
                                ->suffix('m²')
                                ->helperText('Solo si se vende una fracción del predio')
                                ->columnSpan(1),
                            Forms\Components\Toggle::make('requiere_subdivision')
                                ->label('¿Requiere subdivisión/apeo y deslinde?')
                                ->helperText('Cuando la superficie del predio supera la regla 3:1 de FOVISSSTE')
                                ->columnSpanFull(),
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

                    // ── TAB 5: CÓNYUGE (visible solo cuando aplica) ───────
                    Tabs\Tab::make('Cónyuge')
                        ->icon('heroicon-o-user-group')
                        ->visible(fn (Get $get) =>
                            $get('modalidad_credito') === 'mancomunado' ||
                            in_array((int) $get('tipo_tramite_id'), [3, 7])
                        )
                        ->schema([
                            Forms\Components\Placeholder::make('info_conyuge')
                                ->label('')
                                ->content('Llena esta sección para créditos Conyugales o Mancomunados (FOVISSSTE-INFONAVIT Individual). Si el crédito es individual, deja esta sección vacía.')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('conyuge_nombre')
                                ->label('Nombre completo (cónyuge)')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('conyuge_curp')
                                ->label('CURP (cónyuge)')
                                ->maxLength(18)
                                ->minLength(18)
                                ->regex('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i')
                                ->validationMessages(['regex' => 'La CURP del cónyuge no tiene el formato correcto.'])
                                ->extraAttributes(['style' => 'text-transform:uppercase']),
                            Forms\Components\TextInput::make('conyuge_rfc')
                                ->label('RFC (cónyuge)')
                                ->maxLength(13)
                                ->extraAttributes(['style' => 'text-transform:uppercase']),
                            Forms\Components\TextInput::make('conyuge_telefono')
                                ->label('Teléfono (cónyuge)')
                                ->tel()
                                ->regex('/^\d{10}$/')
                                ->maxLength(10)
                                ->validationMessages(['regex' => 'El teléfono del cónyuge debe tener exactamente 10 dígitos.']),
                            Forms\Components\Select::make('conyuge_institucion')
                                ->label('Institución donde cotiza el cónyuge')
                                ->options([
                                    'FOVISSSTE' => 'FOVISSSTE (ISSSTE)',
                                    'INFONAVIT'  => 'INFONAVIT',
                                ])
                                ->hint('Selecciona según el tipo de crédito conyugal'),
                            Forms\Components\TextInput::make('conyuge_numero_credito')
                                ->label('Número de crédito (cónyuge)')
                                ->maxLength(50)
                                ->hint('Número asignado por FOVISSSTE o INFONAVIT'),
                        ])->columns(2),

                    // ── TAB 6: PENSIONADO ─────────────────────────────────
                    // ── TAB 6: PENSIONADO ─────────────────────────────────
                    Tabs\Tab::make('Pensionado')
                        ->icon('heroicon-o-identification')
                        ->visible(fn (Get $get) => (int) $get('tipo_tramite_id') === 2)
                        ->schema([
                            Forms\Components\Placeholder::make('info_pensionado')
                                ->label('')
                                ->content('Llena esta sección solo para el producto "Crédito Pensionados FOVISSSTE". Verifica la clave de pensión en el talón de pago del acreditado.')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('numero_pension')
                                ->label('Número de pensión')
                                ->maxLength(50)
                                ->hint('Aparece en el talón de pago como "NUMERO PENSION"'),
                            Forms\Components\Select::make('clave_pension')
                                ->label('Clave de pensión')
                                ->options([
                                    '101' => '101 — Jubilación',
                                    '102' => '102 — Retiro por Edad y Tiempo de Servicio',
                                    '634' => '634 — Cesantía en Edad Avanzada',
                                ])
                                ->hint('Verificar en talón de pago, columna "PENSION ACTUAL"'),
                            Forms\Components\DatePicker::make('fecha_inicio_pension')
                                ->label('Fecha de inicio de pensión')
                                ->hint('Aparece en el talón como "FECHA DE INICIO DE PENSIÓN"'),
                            Forms\Components\TextInput::make('monto_pension_mensual')
                                ->label('Monto de pensión mensual')
                                ->numeric()->prefix('$')
                                ->minValue(0)
                                ->hint('Concepto 001 del talón (pensión base, sin bonos). Mín. $32,200.60 para crédito máximo.'),
                        ])->columns(2),

                    // ── TAB 7: SEGUIMIENTO DEL PROCESO ────────────────────
                    Tabs\Tab::make('Seguimiento')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            // ── Trámite ante catastro (Paso 10) ───────────────
                            \Filament\Schemas\Components\Section::make('Trámite ante catastro (Paso 10)')
                                ->columnSpanFull()
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Toggle::make('requiere_subdivision')
                                        ->label('¿Requiere subdivisión?')
                                        ->columnSpanFull(),
                                ]),

                            // ── CUV — Clave Única de Vivienda (Pasos 14-15) ───
                            \Filament\Schemas\Components\Section::make('CUV — Clave Única de Vivienda (Pasos 14-15)')
                                ->columnSpanFull()
                                ->columns(3)
                                ->collapsible()
                                ->schema([
                                    Forms\Components\TextInput::make('cuv')
                                        ->label('CUV')
                                        ->placeholder('Clave Única de Vivienda asignada por RUV')
                                        ->columnSpan(2),
                                    Forms\Components\DatePicker::make('cuv_fecha_pago')
                                        ->label('Fecha de pago CUV')
                                        ->columnSpan(1),
                                    Forms\Components\Toggle::make('cuv_activa')
                                        ->label('CUV activa (confirmada por SOFOM)')
                                        ->columnSpanFull(),
                                ]),

                            // ── Instrucción notarial — SOFOM (Paso 18) ────────
                            \Filament\Schemas\Components\Section::make('Instrucción notarial — SOFOM (Paso 18)')
                                ->columnSpanFull()
                                ->columns(2)
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Toggle::make('instruccion_notarial_recibida')
                                        ->label('Instrucción notarial recibida')
                                        ->columnSpanFull(),
                                    Forms\Components\DatePicker::make('instruccion_notarial_fecha')
                                        ->label('Fecha de recepción')
                                        ->columnSpan(1),
                                ]),

                            // ── Notaría — CLG y firma (Paso 19) ───────────────
                            \Filament\Schemas\Components\Section::make('Notaría — CLG y firma (Paso 19)')
                                ->columnSpanFull()
                                ->columns(2)
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Toggle::make('clg_solicitado')
                                        ->label('CLG solicitado')
                                        ->columnSpanFull(),
                                    Forms\Components\DatePicker::make('clg_fecha_solicitud')
                                        ->label('Fecha solicitud CLG')
                                        ->columnSpan(1),
                                    Forms\Components\Toggle::make('clg_recibido')
                                        ->label('CLG recibido')
                                        ->columnSpanFull(),
                                    Forms\Components\DatePicker::make('fecha_limite_firma')
                                        ->label('Fecha límite para firma')
                                        ->helperText('30 días hábiles desde solicitud del CLG')
                                        ->columnSpan(1),
                                    Forms\Components\DatePicker::make('fecha_firma')
                                        ->label('Fecha real de firma')
                                        ->columnSpan(1),
                                ]),

                            // ── Guarda Valores y pago (Paso 20) ───────────────
                            \Filament\Schemas\Components\Section::make('Guarda Valores y pago (Paso 20)')
                                ->columnSpanFull()
                                ->columns(2)
                                ->collapsible()
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_envio_guarda_valores')
                                        ->label('Envío a Guarda Valores FOVISSSTE')
                                        ->columnSpan(1),
                                    Forms\Components\DatePicker::make('fecha_esperada_pago')
                                        ->label('Fecha esperada de pago')
                                        ->helperText('20 días hábiles desde envío a Guarda Valores')
                                        ->columnSpan(1),
                                    Forms\Components\Toggle::make('pago_recibido')
                                        ->label('Pago recibido')
                                        ->columnSpanFull(),
                                    Forms\Components\DatePicker::make('fecha_pago_recibido')
                                        ->label('Fecha real del pago')
                                        ->columnSpan(1),
                                ]),
                        ])->columnSpanFull(),

                ])->columnSpanFull()->persistTabInQueryString(),
        ]);
    }

public static function canDelete(Model $record): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        if (! $user) return false;
        if (! $user->can('Update:Expediente')) return false;
        if ($user->hasRole('super_admin')) return true;
        // El asesor solo puede editar expedientes asignados a él
        if ($user->hasRole('asesor')) return $record->asesor_id === $user->id;
        // Cualquier otro rol con permiso (capturista, etc.) puede editar todos
        return true;
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
                    ->placeholder('Sin monto de crédito')
                    ->toggleable()
                    ->tooltip('Monto del crédito aprobado por la institución'),
                Tables\Columns\TextColumn::make('honorarios_monto')
                    ->label('Honorarios')
                    ->money('MXN')
                    ->placeholder('Sin porcentaje de honorarios asignado')
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
                \Filament\Actions\EditAction::make()
                    ->hidden(fn ($record) => Auth::user()?->hasRole('asesor')
                        && $record->etapa && $record->etapa->orden >= 5),
                \Filament\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasRole('super_admin')),
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
