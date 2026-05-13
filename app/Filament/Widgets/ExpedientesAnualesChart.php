<?php

namespace App\Filament\Widgets;

use App\Models\Comision;
use App\Models\Expediente;
use App\Models\GastoFinanciado;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ExpedientesAnualesChart extends ChartWidget
{
    protected static ?string $heading = 'Flujo Anual — Expedientes, Ingresos y Egresos';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = null;

    public function mount(): void
    {
        $this->filter = (string) now()->year;
        parent::mount();
    }

    protected function getFilters(): ?array
    {
        $years = [];
        for ($y = now()->year; $y >= now()->year - 3; $y--) {
            $years[(string) $y] = (string) $y;
        }
        return $years;
    }

    protected function getData(): array
    {
        // Siempre usar el primer key del filtro (año actual) si no hay selección
        $filters = $this->getFilters();
        $year    = (int) ($this->filter && isset($filters[$this->filter])
            ? $this->filter
            : array_key_first($filters));

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $expedientesPorMes   = array_fill(0, 12, 0);
        $cerradosPorMes      = array_fill(0, 12, 0);
        $ingresosPorMes      = array_fill(0, 12, 0.0);
        $egresoAsesoresMes   = array_fill(0, 12, 0.0);
        $egresoGastosMes     = array_fill(0, 12, 0.0);
        $flujoNetoPorMes     = array_fill(0, 12, 0.0);

        // Expedientes abiertos por mes
        Expediente::whereYear('fecha_apertura', $year)
            ->selectRaw('MONTH(fecha_apertura) as mes, COUNT(*) as total')
            ->groupBy('mes')
            ->get()
            ->each(fn ($r) => $expedientesPorMes[$r->mes - 1] = (int) $r->total);

        // Expedientes cerrados por mes
        Expediente::where('estado', 'cerrado')
            ->whereYear('fecha_cierre', $year)
            ->selectRaw('MONTH(fecha_cierre) as mes, COUNT(*) as total')
            ->groupBy('mes')
            ->get()
            ->each(fn ($r) => $cerradosPorMes[$r->mes - 1] = (int) $r->total);

        // Ingresos cobrados por mes (honorarios_pagados = true)
        Expediente::where('estado', 'cerrado')
            ->where('honorarios_pagados', true)
            ->whereYear('fecha_cierre', $year)
            ->selectRaw('MONTH(fecha_cierre) as mes, SUM(honorarios_monto) as total')
            ->groupBy('mes')
            ->get()
            ->each(fn ($r) => $ingresosPorMes[$r->mes - 1] = (float) $r->total);

        // Egresos: comisiones pagadas a asesores por mes
        Comision::where('estado', 'pagada')
            ->whereYear('fecha_pago', $year)
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto_comision) as total')
            ->groupBy('mes')
            ->get()
            ->each(fn ($r) => $egresoAsesoresMes[$r->mes - 1] = (float) $r->total);

        // Egresos: gastos financiados pagados por mes
        GastoFinanciado::whereYear('fecha_pago', $year)
            ->whereNotNull('fecha_pago')
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto) as total')
            ->groupBy('mes')
            ->get()
            ->each(fn ($r) => $egresoGastosMes[$r->mes - 1] = (float) $r->total);

        // Flujo neto: ingresos - (comisiones + gastos) por mes
        for ($i = 0; $i < 12; $i++) {
            $flujoNetoPorMes[$i] = round($ingresosPorMes[$i] - $egresoAsesoresMes[$i] - $egresoGastosMes[$i], 2);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Expedientes Abiertos',
                    'data'            => $expedientesPorMes,
                    'borderColor'     => '#6366f1',
                    'backgroundColor' => 'rgba(99,102,241,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Expedientes Cerrados',
                    'data'            => $cerradosPorMes,
                    'borderColor'     => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Ingresos Cobrados (MXN)',
                    'data'            => $ingresosPorMes,
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.08)',
                    'fill'            => false,
                    'tension'         => 0.4,
                    'yAxisID'         => 'y1',
                ],
                [
                    'label'           => 'Egresos Asesores (MXN)',
                    'data'            => $egresoAsesoresMes,
                    'borderColor'     => '#ef4444',
                    'backgroundColor' => 'rgba(239,68,68,0.08)',
                    'fill'            => false,
                    'tension'         => 0.4,
                    'yAxisID'         => 'y1',
                ],
                [
                    'label'           => 'Gastos Financiados (MXN)',
                    'data'            => $egresoGastosMes,
                    'borderColor'     => '#f97316',
                    'backgroundColor' => 'rgba(249,115,22,0.08)',
                    'fill'            => false,
                    'tension'         => 0.4,
                    'yAxisID'         => 'y1',
                ],
                [
                    'label'           => 'Flujo Neto (MXN)',
                    'data'            => $flujoNetoPorMes,
                    'borderColor'     => '#8b5cf6',
                    'backgroundColor' => 'rgba(139,92,246,0.08)',
                    'fill'            => false,
                    'tension'         => 0.4,
                    'borderDash'      => [6, 3],
                    'yAxisID'         => 'y1',
                ],
            ],
            'labels' => $meses,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode'      => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'type'     => 'linear',
                    'display'  => true,
                    'position' => 'left',
                    'title'    => ['display' => true, 'text' => 'Expedientes'],
                    'ticks'    => ['stepSize' => 1],
                ],
                'y1' => [
                    'type'     => 'linear',
                    'display'  => true,
                    'position' => 'right',
                    'title'    => ['display' => true, 'text' => 'Monto (MXN)'],
                    'grid'     => ['drawOnChartArea' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'top'],
                'tooltip' => [
                    'callbacks' => [],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
