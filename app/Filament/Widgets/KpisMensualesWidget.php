<?php

namespace App\Filament\Widgets;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\GastoFinanciado;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class KpisMensualesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected function getFilters(): ?array
    {
        $meses = [
            '1' => 'Enero',   '2' => 'Febrero',  '3' => 'Marzo',
            '4' => 'Abril',   '5' => 'Mayo',      '6' => 'Junio',
            '7' => 'Julio',   '8' => 'Agosto',    '9' => 'Septiembre',
            '10' => 'Octubre','11' => 'Noviembre','12' => 'Diciembre',
        ];

        $options = ['' => 'Todo el año (' . now()->year . ')'];
        for ($y = now()->year; $y >= now()->year - 3; $y--) {
            $options['year_' . $y] = '— Año ' . $y . ' completo —';
            foreach ($meses as $k => $v) {
                $options[$y . '_' . $k] = $v . ' ' . $y;
            }
        }

        return $options;
    }

    private function parseFilter(): array
    {
        $filter = $this->filter ?? '';

        if ($filter === '' || $filter === null) {
            return ['mes' => null, 'anio' => now()->year];
        }

        if (str_starts_with($filter, 'year_')) {
            return ['mes' => null, 'anio' => (int) substr($filter, 5)];
        }

        [$anio, $mes] = explode('_', $filter);
        return ['mes' => (int) $mes, 'anio' => (int) $anio];
    }

    protected function getStats(): array
    {
        ['mes' => $mes, 'anio' => $anio] = $this->parseFilter();

        $periodoLabel = $mes
            ? Carbon::create($anio, $mes, 1)->translatedFormat('F Y')
            : 'Año ' . $anio;

        $cacheKey = "dashboard:kpis_mensuales:{$anio}:" . ($mes ?? 'todo');

        $d = Cache::remember($cacheKey, 90, function () use ($mes, $anio) {
            // ── Builders reutilizables ────────────────────────────────────────

            $expQ = fn () => Expediente::query()
                ->when($mes, fn ($q) => $q->whereMonth('fecha_apertura', $mes))
                ->whereYear('fecha_apertura', $anio);

            $cerQ = fn () => Expediente::where('estado', 'cerrado')
                ->when($mes, fn ($q) => $q->whereMonth('fecha_cierre', $mes))
                ->whereYear('fecha_cierre', $anio);

            $comQ = fn () => Comision::query()
                ->when($mes, fn ($q) => $q->whereMonth('fecha_generacion', $mes))
                ->whereYear('fecha_generacion', $anio);

            $gastoQ = fn () => GastoFinanciado::query()
                ->when($mes, fn ($q) => $q->whereMonth('fecha_pago', $mes))
                ->whereYear('fecha_pago', $anio);

            // ── Expedientes ───────────────────────────────────────────────────

            $totalAbiertos   = $expQ()->count();
            $totalCerrados   = $cerQ()->count();
            $totalCancelados = Expediente::where('estado', 'cancelado')
                ->when($mes, fn ($q) => $q->whereMonth('updated_at', $mes))
                ->whereYear('updated_at', $anio)->count();
            $activos = Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])->count();

            $tasaCierre = $totalAbiertos > 0
                ? round(($totalCerrados / $totalAbiertos) * 100, 1) : 0;

            // ── 💰 FLUJO DE DINERO ──────────────────────────────────────────────

            // INGRESOS: honorarios efectivamente cobrados (pagados = true)
            $ingresosCobrados = $cerQ()->where('honorarios_pagados', true)->sum('honorarios_monto');

            // INGRESOS PENDIENTES: honorarios de cerrados aún no cobrados
            $ingresosPendientes = $cerQ()->where('honorarios_pagados', false)->sum('honorarios_monto');

            // INGRESOS ESPERADOS: todos los expedientes activos (pipeline)
            $ingresosEsperados = Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])
                ->sum('honorarios_monto');

            // EGRESOS: comisiones pagadas a asesores
            $egresoComisionesPagadas  = $comQ()->where('estado', 'pagada')->sum('monto_comision');
            $egresoComisionesPendientes = $comQ()->whereIn('estado', ['pendiente', 'aprobada'])->sum('monto_comision');

            // EGRESOS: gastos financiados pagados por la consultora
            $egresoGastosPagados = $gastoQ()->sum('monto');

            // TOTAL EGRESOS REALES
            $egresoTotal = $egresoComisionesPagadas + $egresoGastosPagados;

            // FLUJO NETO: lo que realmente entró menos lo que salió
            $flujoNeto = $ingresosCobrados - $egresoTotal;

            // MARGEN: % de lo cobrado que quedó en la empresa
            $margen = $ingresosCobrados > 0
                ? round((($ingresosCobrados - $egresoTotal) / $ingresosCobrados) * 100, 1)
                : 0;

            // ── Honorarios detalle ──────────────────────────────────────────────

            $honTotal     = $cerQ()->sum('honorarios_monto');
            $ticketProm   = $totalCerrados > 0 ? round($honTotal / $totalCerrados, 2) : 0;

            // ── Prospectos ────────────────────────────────────────────────────

            $prospectos      = Contacto::when($mes, fn ($q) => $q->whereMonth('created_at', $mes))
                ->whereYear('created_at', $anio)->count();
            $prospNuevos     = Contacto::where('estado_prospecto', 'nuevo')->count();

            return compact(
                'totalAbiertos', 'totalCerrados', 'totalCancelados', 'activos', 'tasaCierre',
                'ingresosCobrados', 'ingresosPendientes', 'ingresosEsperados',
                'egresoComisionesPagadas', 'egresoComisionesPendientes', 'egresoGastosPagados',
                'flujoNeto', 'margen', 'ticketProm', 'prospectos', 'prospNuevos'
            );
        });

        extract($d);

        // ── Stats ─────────────────────────────────────────────────────────────

        return [

            // ── Bloque 1: Flujo de Dinero ─────────────────────────────────
            Stat::make('💵 Ingresos Cobrados', '$' . number_format($ingresosCobrados, 0, '.', ','))
                ->description('Honorarios efectivamente pagados · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make('🕐 Ingresos Por Cobrar', '$' . number_format($ingresosPendientes, 0, '.', ','))
                ->description('Honorarios de cerrados sin cobrar · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('📈 Pipeline (Esperado)', '$' . number_format($ingresosEsperados, 0, '.', ','))
                ->description('Honorarios de expedientes activos')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('👥 Egresos a Asesores', '$' . number_format($egresoComisionesPagadas, 0, '.', ','))
                ->description('Comisiones ya pagadas · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('danger'),

            Stat::make('⏳ Comisiones Por Pagar', '$' . number_format($egresoComisionesPendientes, 0, '.', ','))
                ->description('Pendientes y aprobadas sin pagar · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('🏠 Gastos Financiados', '$' . number_format($egresoGastosPagados, 0, '.', ','))
                ->description('Dinero adelantado a trámites · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-building-office')
                ->color('danger'),

            Stat::make('💹 Flujo Neto', '$' . number_format($flujoNeto, 0, '.', ','))
                ->description('Cobrado menos egresos reales · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-scale')
                ->color($flujoNeto >= 0 ? 'success' : 'danger'),

            Stat::make('📊 Margen Real', $margen . '%')
                ->description('Del total cobrado, lo que quedó en la empresa')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($margen >= 50 ? 'success' : ($margen >= 25 ? 'warning' : 'danger')),

            // ── Bloque 2: Expedientes ─────────────────────────────────────
            Stat::make('📂 Expedientes Abiertos', $totalAbiertos)
                ->description($periodoLabel)
                ->descriptionIcon('heroicon-m-folder-plus')
                ->color('primary'),

            Stat::make('✅ Expedientes Cerrados', $totalCerrados)
                ->description($periodoLabel)
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('🎯 Tasa de Cierre', $tasaCierre . '%')
                ->description('Cerrados vs abiertos · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($tasaCierre >= 50 ? 'success' : 'warning'),

            Stat::make('❌ Cancelados', $totalCancelados)
                ->description($periodoLabel)
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('🗂️ Activos Hoy', $activos)
                ->description('En proceso, aprobados o firmados')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('primary'),

            Stat::make('🎟️ Ticket Promedio', '$' . number_format($ticketProm, 0, '.', ','))
                ->description('Honorario promedio por expediente cerrado · ' . $periodoLabel)
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary'),

            // ── Bloque 3: Prospectos ──────────────────────────────────────
            Stat::make('👤 Prospectos Captados', $prospectos)
                ->description($periodoLabel)
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('🔔 Sin Atender', $prospNuevos)
                ->description('Prospectos nuevos sin asignar')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($prospNuevos > 0 ? 'warning' : 'success'),
        ];
    }
}
