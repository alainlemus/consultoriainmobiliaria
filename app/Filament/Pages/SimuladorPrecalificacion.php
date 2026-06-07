<?php

namespace App\Filament\Pages;

use App\Services\UmaService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;

/**
 * Simulador interno de precalificación FOVISSSTE / INFONAVIT.
 *
 * Reglas de negocio:
 *  - UMA diaria: obtenida desde BD (actualizada automáticamente cada febrero)
 *  - UMA mensual = UMA diaria × 30.4
 *  - Tope Crédito Tradicional: 954 × UMA mensual ≈ $3.28M (2025)
 *  - Capacidad de pago: mensualidad ≤ 30% del sueldo básico
 *  - Plazo máximo Tradicional: 30 años
 *  - Plazo máximo Pensionados: 20 años
 *  - Para Todos (bancos): hasta $2,740,000 tasa fija 10.38%
 *  - Tasa Crédito Tradicional: 4% (sueldo ≤ 4 UMA), hasta 6% (> 7 UMA)
 *  - Subcuenta de vivienda se suma al crédito para el valor del inmueble
 */
class SimuladorPrecalificacion extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Simulador';
    protected static ?string $title           = 'Simulador de Precalificación';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $slug            = 'simulador';

    protected string $view = 'filament.pages.simulador-precalificacion';

    // ── Constantes que NO dependen de la UMA ─────────────────────────────
    const TOPE_VSM           = 954;      // tope máximo en veces UMA mensual (Tradicional)
    const TOPE_PARA_TODOS    = 2740000;  // tope crédito Para Todos
    const TOPE_PENSIONADOS   = 1600000;  // aprox. tope pensionados
    const PORCENTAJE_PAGO    = 0.30;     // máximo 30% sueldo para mensualidad
    const PLAZO_TRADICIONAL  = 30;       // años
    const PLAZO_PENSIONADOS  = 20;
    const HONORARIOS_DEFAULT = 0.08;     // 8% honorarios orientativo
    const TOPE_UMA_INFONAVIT = 430;      // 430 UMA = crédito FOVISSSTE en crédito Unidos

    public ?array $data = [];
    public array $resultado = [];

    public static function canAccess(): bool
    {
        return auth()->check() && (
            auth()->user()->hasRole('super_admin') ||
            auth()->user()->hasRole('asesor')
        );
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        $uma = UmaService::info();

        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Datos del acreditado')
                    ->description(fn () => 'UMA vigente ' . UmaService::getVigencia() . ': $' . number_format(UmaService::getUmaDiaria(), 2) . '/día · $' . number_format(UmaService::getUmaMensual(), 2) . '/mes')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('tipo_credito')
                            ->label('Tipo de crédito')
                            ->options([
                                'tradicional'  => 'Crédito Tradicional FOVISSSTE',
                                'pensionados'  => 'Crédito Pensionados FOVISSSTE',
                                'conyugal'     => 'Crédito Conyugal FOVISSSTE',
                                'para_todos'   => 'FOVISSSTE Para Todos (Bancos)',
                                'infonavit'    => 'FOVISSSTE-INFONAVIT Individual',
                                'infonavit_puro' => 'INFONAVIT (sector privado)',
                            ])
                            ->default('tradicional')
                            ->required()
                            ->live()
                            ->validationMessages(['required' => 'Selecciona el tipo de crédito.']),

                        Forms\Components\TextInput::make('sueldo_mensual')
                            ->label('Sueldo mensual neto ($)')
                            ->numeric()->prefix('$')->required()->minValue(1)
                            ->live(onBlur: true)
                            ->validationMessages([
                                'required'  => 'El sueldo mensual es obligatorio.',
                                'min_value' => 'El sueldo debe ser mayor a cero.',
                            ]),

                        Forms\Components\TextInput::make('subcuenta_vivienda')
                            ->label('Subcuenta de vivienda ($)')
                            ->numeric()->prefix('$')->default(0)->minValue(0)
                            ->live(onBlur: true)
                            ->helperText('Saldo acumulado FOVISSSTE/INFONAVIT'),

                        Forms\Components\TextInput::make('antiguedad_laboral')
                            ->label('Antigüedad laboral (años)')
                            ->numeric()->required()->minValue(1)->maxValue(50)
                            ->live(onBlur: true)
                            ->validationMessages([
                                'required'  => 'La antigüedad es obligatoria.',
                                'min_value' => 'La antigüedad debe ser de al menos 1 año.',
                            ]),

                        Forms\Components\TextInput::make('edad')
                            ->label('Edad (años)')
                            ->numeric()->required()->minValue(18)->maxValue(74)
                            ->live(onBlur: true)
                            ->validationMessages([
                                'required'  => 'La edad es obligatoria.',
                                'min_value' => 'La edad mínima es 18 años.',
                                'max_value' => 'La edad máxima para crédito Pensionados es 74 años.',
                            ]),

                        // Solo visible para Conyugal
                        Forms\Components\TextInput::make('sueldo_conyuge')
                            ->label('Sueldo mensual cónyuge ($)')
                            ->numeric()->prefix('$')->minValue(0)->default(0)
                            ->live(onBlur: true)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_credito') === 'conyugal')
                            ->helperText('Sueldo neto del cónyuge — se suma para el crédito conyugal'),

                        // Solo visible para Pensionados
                        Forms\Components\TextInput::make('monto_pension')
                            ->label('Monto de pensión mensual ($)')
                            ->numeric()->prefix('$')->minValue(0)->default(0)
                            ->live(onBlur: true)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_credito') === 'pensionados')
                            ->helperText('Mínimo requerido: $32,200.60 para monto máximo'),

                        // Solo visible para FOVISSSTE-INFONAVIT Individual
                        Forms\Components\TextInput::make('subcuenta_infonavit')
                            ->label('Subcuenta INFONAVIT ($)')
                            ->numeric()->prefix('$')->default(0)->minValue(0)
                            ->live(onBlur: true)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('tipo_credito'), ['infonavit', 'infonavit_puro']))
                            ->helperText('Saldo acumulado en subcuenta INFONAVIT'),
                    ]),
            ])
            ->statePath('data');
    }

    public function calcular(): void
    {
        $this->form->validate();

        $d = $this->data;

        $tipo               = $d['tipo_credito']         ?? 'tradicional';
        $sueldo             = (float) ($d['sueldo_mensual']      ?? 0);
        $subcuenta          = (float) ($d['subcuenta_vivienda']  ?? 0);
        $antiguedad         = (int)   ($d['antiguedad_laboral']  ?? 0);
        $edad               = (int)   ($d['edad']                ?? 0);
        $sueldoConyuge      = (float) ($d['sueldo_conyuge']      ?? 0);
        $pension            = (float) ($d['monto_pension']       ?? 0);
        $subcuentaInfonavit = (float) ($d['subcuenta_infonavit'] ?? 0);

        // UMA desde BD (se actualiza cada febrero)
        $vsm = UmaService::getUmaMensual();

        $this->resultado = match($tipo) {
            'tradicional'    => $this->calcularTradicional($sueldo, $subcuenta, $antiguedad, $edad, $vsm),
            'pensionados'    => $this->calcularPensionados($pension, $subcuenta, $edad, $vsm),
            'conyugal'       => $this->calcularConyugal($sueldo, $sueldoConyuge, $subcuenta, $antiguedad, $edad, $vsm),
            'para_todos'     => $this->calcularParaTodos($sueldo, $subcuenta, $edad),
            'infonavit'      => $this->calcularInfonavitFovissste($sueldo, $subcuenta, $subcuentaInfonavit, $antiguedad, $edad, $vsm),
            'infonavit_puro' => $this->calcularInfonavitPuro($sueldo, $subcuentaInfonavit, $antiguedad, $edad),
            default          => [],
        };
    }

    // ─── Crédito Tradicional ─────────────────────────────────────────────

    private function calcularTradicional(float $sueldo, float $subcuenta, int $antiguedad, int $edad, float $vsm): array
    {
        $errores = [];
        if ($antiguedad < 1)  $errores[] = 'Se requiere al menos 1 año de antigüedad.';
        if ($edad > 70)       $errores[] = 'La edad máxima para Crédito Tradicional es 70 años.';

        // UMA del trabajador (en veces la UMA mensual)
        $umasTrabajador = $sueldo / $vsm;

        // Tasa según UMA
        $tasa = $umasTrabajador <= 4 ? 0.04 : ($umasTrabajador <= 7 ? 0.05 : 0.06);

        // Plazo disponible
        $plazoDisponible = min(self::PLAZO_TRADICIONAL, 70 - $edad);
        if ($plazoDisponible < 5) {
            $errores[] = "Con {$edad} años, el plazo disponible es solo {$plazoDisponible} años.";
        }

        // Capacidad de pago mensual
        $pagoMaxMensual = $sueldo * self::PORCENTAJE_PAGO;

        // Monto máximo por capacidad de pago (valor presente de anualidad)
        $tasaMensual = $tasa / 12;
        $n           = $plazoDisponible * 12;
        $montoCapacidad = ($n > 0 && $tasaMensual > 0)
            ? $pagoMaxMensual * (1 - pow(1 + $tasaMensual, -$n)) / $tasaMensual
            : 0;

        // Tope por UMA: 954 × UMA mensual (CORRECTO — sin × 12)
        $topeUma = self::TOPE_VSM * $vsm;

        $montoCredito  = min($montoCapacidad, $topeUma);
        $valorInmueble = $montoCredito + $subcuenta;

        $mensualidad = ($tasaMensual > 0 && $n > 0)
            ? $montoCredito * $tasaMensual / (1 - pow(1 + $tasaMensual, -$n))
            : 0;

        $honorarios = $montoCredito * self::HONORARIOS_DEFAULT;

        return [
            'tipo'            => 'Crédito Tradicional FOVISSSTE',
            'elegible'        => empty($errores),
            'errores'         => $errores,
            'monto_credito'   => $montoCredito,
            'subcuenta'       => $subcuenta,
            'valor_inmueble'  => $valorInmueble,
            'mensualidad'     => $mensualidad,
            'tasa_anual'      => $tasa * 100,
            'plazo_años'      => $plazoDisponible,
            'pago_max_mensual'=> $pagoMaxMensual,
            'honorarios'      => $honorarios,
            'notas'           => [
                "UMA del trabajador: " . number_format($umasTrabajador, 2) . " veces",
                "Tasa de interés anual: " . ($tasa * 100) . "%",
                "Plazo: {$plazoDisponible} años",
                "30% sueldo para mensualidad: $" . number_format($pagoMaxMensual, 2),
                "Tope 954 UMA: $" . number_format($topeUma, 2, '.', ','),
            ],
        ];
    }

    // ─── Crédito Pensionados ─────────────────────────────────────────────

    private function calcularPensionados(float $pension, float $subcuenta, int $edad, float $vsm): array
    {
        $errores = [];
        if ($edad < 47) $errores[] = 'Edad mínima para crédito Pensionados: 47 años.';
        if ($edad > 74) $errores[] = 'Edad máxima para crédito Pensionados: 74 años.';
        if ($pension < 1) $errores[] = 'Ingresa el monto de la pensión mensual.';

        $pagoMaxMensual = $pension * self::PORCENTAJE_PAGO;
        $plazo = min(self::PLAZO_PENSIONADOS, 74 - $edad);
        if ($plazo < 3) $errores[] = "Con {$edad} años el plazo disponible es solo {$plazo} años.";

        $tasa        = 0.04;
        $tasaMensual = $tasa / 12;
        $n           = $plazo * 12;

        $montoCapacidad = ($n > 0 && $tasaMensual > 0)
            ? $pagoMaxMensual * (1 - pow(1 + $tasaMensual, -$n)) / $tasaMensual
            : 0;

        $montoCredito  = min($montoCapacidad, self::TOPE_PENSIONADOS);
        $valorInmueble = $montoCredito + $subcuenta;
        $mensualidad   = ($tasaMensual > 0 && $n > 0)
            ? $montoCredito * $tasaMensual / (1 - pow(1 + $tasaMensual, -$n))
            : 0;
        $honorarios = $montoCredito * self::HONORARIOS_DEFAULT;

        return [
            'tipo'            => 'Crédito Pensionados FOVISSSTE',
            'elegible'        => empty($errores),
            'errores'         => $errores,
            'monto_credito'   => $montoCredito,
            'subcuenta'       => $subcuenta,
            'valor_inmueble'  => $valorInmueble,
            'mensualidad'     => $mensualidad,
            'tasa_anual'      => $tasa * 100,
            'plazo_años'      => $plazo,
            'pago_max_mensual'=> $pagoMaxMensual,
            'honorarios'      => $honorarios,
            'notas'           => [
                "Pensión mensual: $" . number_format($pension, 2),
                "30% para mensualidad: $" . number_format($pagoMaxMensual, 2),
                "Plazo: {$plazo} años",
                $pension >= 32200.60
                    ? "Pensión suficiente para monto máximo"
                    : "Pensión menor al ideal ($32,200.60) — monto reducido",
            ],
        ];
    }

    // ─── Crédito Conyugal ────────────────────────────────────────────────

    private function calcularConyugal(float $sueldo, float $sueldoConyuge, float $subcuenta, int $antiguedad, int $edad, float $vsm): array
    {
        $sueldoTotal = $sueldo + $sueldoConyuge;
        $resultado   = $this->calcularTradicional($sueldoTotal, $subcuenta, $antiguedad, $edad, $vsm);
        $resultado['tipo'] = 'Crédito Conyugal FOVISSSTE';
        $resultado['notas'][] = "Sueldo titular:  $" . number_format($sueldo, 2);
        $resultado['notas'][] = "Sueldo cónyuge:  $" . number_format($sueldoConyuge, 2);
        $resultado['notas'][] = "Sueldo combinado: $" . number_format($sueldoTotal, 2);
        return $resultado;
    }

    // ─── FOVISSSTE Para Todos ────────────────────────────────────────────

    private function calcularParaTodos(float $sueldo, float $subcuenta, int $edad): array
    {
        $errores = [];
        if ($edad > 64) $errores[] = 'Edad máxima recomendada Para Todos: 64 años.';

        $tasa        = 0.1038;
        $tasaMensual = $tasa / 12;
        $plazo       = min(20, 64 - $edad);
        $n           = $plazo * 12;

        $pagoMaxMensual = $sueldo * self::PORCENTAJE_PAGO;
        $montoCapacidad = ($n > 0 && $tasaMensual > 0)
            ? $pagoMaxMensual * (1 - pow(1 + $tasaMensual, -$n)) / $tasaMensual
            : 0;

        $montoCredito  = min($montoCapacidad, self::TOPE_PARA_TODOS);
        $valorInmueble = $montoCredito + $subcuenta;
        $mensualidad   = ($tasaMensual > 0 && $n > 0)
            ? $montoCredito * $tasaMensual / (1 - pow(1 + $tasaMensual, -$n))
            : 0;
        $honorarios = $montoCredito * self::HONORARIOS_DEFAULT;

        return [
            'tipo'            => 'FOVISSSTE Para Todos (Bancos: HSBC / Banorte / BBVA)',
            'elegible'        => empty($errores),
            'errores'         => $errores,
            'monto_credito'   => $montoCredito,
            'subcuenta'       => $subcuenta,
            'valor_inmueble'  => $valorInmueble,
            'mensualidad'     => $mensualidad,
            'tasa_anual'      => $tasa * 100,
            'plazo_años'      => $plazo,
            'pago_max_mensual'=> $pagoMaxMensual,
            'honorarios'      => $honorarios,
            'notas'           => [
                "Tasa fija: 10.38% anual (varía por banco)",
                "Bancos participantes: HSBC, Banorte, BBVA",
                "Plazo: {$plazo} años",
                "Tope máximo: $" . number_format(self::TOPE_PARA_TODOS, 0, '.', ','),
            ],
        ];
    }

    // ─── FOVISSSTE-INFONAVIT Individual (Crédito Unidos) ────────────────

    private function calcularInfonavitFovissste(
        float $sueldo,
        float $subcuentaFovissste,
        float $subcuentaInfonavit,
        int $antiguedad,
        int $edad,
        float $vsm
    ): array {
        $errores = [];
        if ($antiguedad < 2) $errores[] = 'Se requieren al menos 18 meses de aportaciones en ambas subcuentas.';
        if ($edad > 70)      $errores[] = 'Edad máxima para este crédito: 70 años.';

        $umasTrabajador = $sueldo / $vsm;
        $tasa = $umasTrabajador <= 4 ? 0.04 : ($umasTrabajador <= 7 ? 0.05 : 0.06);

        $plazo = min(self::PLAZO_TRADICIONAL, 70 - $edad);
        if ($plazo < 5) $errores[] = "Con {$edad} años el plazo disponible es solo {$plazo} años.";

        $pagoMaxMensual = $sueldo * self::PORCENTAJE_PAGO;
        $tasaMensual    = $tasa / 12;
        $n              = $plazo * 12;

        $montoCapacidad = ($n > 0 && $tasaMensual > 0)
            ? $pagoMaxMensual * (1 - pow(1 + $tasaMensual, -$n)) / $tasaMensual
            : 0;

        // Tope FOVISSSTE: 430 UMA diaria × 365 + subcuenta FOVISSSTE
        $umaAnual        = UmaService::getUmaAnual();
        $topeFovissste   = (self::TOPE_UMA_INFONAVIT * $umaAnual) + $subcuentaFovissste;
        $creditoFovissste = min($montoCapacidad, $topeFovissste);

        // Subcuenta INFONAVIT como primer pago
        $montoTotalFinanciado = $creditoFovissste + $subcuentaInfonavit;

        $mensualidad = ($tasaMensual > 0 && $n > 0)
            ? $creditoFovissste * $tasaMensual / (1 - pow(1 + $tasaMensual, -$n))
            : 0;

        $honorarios = $creditoFovissste * self::HONORARIOS_DEFAULT;

        return [
            'tipo'            => 'FOVISSSTE-INFONAVIT Individual (Crédito Unidos)',
            'elegible'        => empty($errores),
            'errores'         => $errores,
            'monto_credito'   => $creditoFovissste,
            'subcuenta'       => $subcuentaFovissste,
            'valor_inmueble'  => $montoTotalFinanciado,
            'mensualidad'     => $mensualidad,
            'tasa_anual'      => $tasa * 100,
            'plazo_años'      => $plazo,
            'pago_max_mensual'=> $pagoMaxMensual,
            'honorarios'      => $honorarios,
            'notas'           => [
                "Crédito FOVISSSTE (hasta 430 UMA anuales): $" . number_format($creditoFovissste, 2, '.', ','),
                "Subcuenta INFONAVIT (primer pago): $"         . number_format($subcuentaInfonavit, 2, '.', ','),
                "Financiamiento total estimado: $"             . number_format($montoTotalFinanciado, 2, '.', ','),
                "Tasa FOVISSSTE: " . ($tasa * 100) . "% anual",
                "30% sueldo para mensualidad: $"               . number_format($pagoMaxMensual, 2, '.', ','),
                "50% gastos notariales cubiertos por FOVISSSTE",
                "⚠️ Requiere precalificación INFONAVIT por separado en micuenta.infonavit.org.mx",
            ],
        ];
    }

    // ─── INFONAVIT Puro (sector privado) ─────────────────────────────────
    //
    // INFONAVIT no tiene fórmula pública simple: el monto depende del
    // score crediticio (NSS), puntos acumulados, VSM del trabajador y la
    // subcuenta. Esta es una estimación orientativa basada en las reglas
    // generales publicadas por INFONAVIT 2024-2025.
    //
    private function calcularInfonavitPuro(
        float $sueldo,
        float $subcuenta,
        int $antiguedad,
        int $edad
    ): array {
        $errores = [];
        if ($antiguedad < 2)  $errores[] = 'Se requieren mínimo 2 años de cotización continua.';
        if ($edad > 65)       $errores[] = 'Edad máxima recomendada para INFONAVIT: 65 años.';
        if ($sueldo < 2000)   $errores[] = 'Sueldo mínimo aproximado: $2,000/mes.';

        // VSM (salario diario integrado × 30.4)
        // INFONAVIT usa UDI para su crédito, aproximamos con tasa promedio 10% anual
        $tasa        = 0.10; // tasa aproximada INFONAVIT en pesos (varía entre 6.47%-12%)
        $tasaMensual = $tasa / 12;
        $plazo       = min(30, 65 - $edad);
        $n           = $plazo * 12;

        $pagoMaxMensual = $sueldo * self::PORCENTAJE_PAGO;
        $montoCapacidad = ($n > 0 && $tasaMensual > 0)
            ? $pagoMaxMensual * (1 - pow(1 + $tasaMensual, -$n)) / $tasaMensual
            : 0;

        // Tope INFONAVIT 2025: aprox $2,191,736 (varía según VSM/UMA)
        $topeInfonavit = 2191736.0;
        $montoCredito  = min($montoCapacidad + $subcuenta, $topeInfonavit);
        $valorInmueble = $montoCredito;

        $mensualidad = ($tasaMensual > 0 && $n > 0 && $montoCredito > $subcuenta)
            ? ($montoCredito - $subcuenta) * $tasaMensual / (1 - pow(1 + $tasaMensual, -$n))
            : 0;

        $honorarios = $montoCredito * self::HONORARIOS_DEFAULT;

        return [
            'tipo'            => 'INFONAVIT (sector privado)',
            'elegible'        => empty($errores),
            'errores'         => $errores,
            'monto_credito'   => $montoCredito,
            'subcuenta'       => $subcuenta,
            'valor_inmueble'  => $valorInmueble,
            'mensualidad'     => $mensualidad,
            'tasa_anual'      => $tasa * 100,
            'plazo_años'      => $plazo,
            'pago_max_mensual'=> $pagoMaxMensual,
            'honorarios'      => $honorarios,
            'notas'           => [
                "⚠️ Estimación orientativa — el monto exacto requiere precalificación oficial",
                "Tasa aproximada: 10% anual (rango real: 6.47%–12% según salario)",
                "Tope máximo 2025: $" . number_format($topeInfonavit, 0, '.', ','),
                "Subcuenta aplicada como enganche: $" . number_format($subcuenta, 2, '.', ','),
                "Verifica tu precalificación en: micuenta.infonavit.org.mx",
            ],
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    public static function fmt(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',');
    }
}
