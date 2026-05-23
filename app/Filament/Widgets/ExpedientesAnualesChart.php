<?php

namespace App\Filament\Widgets;

use App\Models\Comision;
use App\Models\Expediente;
use App\Models\GastoFinanciado;
use Filament\Widgets\Widget;

class ExpedientesAnualesChart extends Widget
{
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.expedientes-anuales-chart';

    protected function getViewData(): array
    {
        $year = now()->year;

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $expedientesPorMes = array_fill(0, 12, 0);
        $cerradosPorMes    = array_fill(0, 12, 0);
        $ingresosPorMes    = array_fill(0, 12, 0);
        $comisionesMes     = array_fill(0, 12, 0);
        $gastosMes         = array_fill(0, 12, 0);
        $flujoNetoMes      = array_fill(0, 12, 0);

        Expediente::whereYear('fecha_apertura', $year)
            ->selectRaw('MONTH(fecha_apertura) as mes, COUNT(*) as total')
            ->groupBy('mes')->get()
            ->each(function ($r) use (&$expedientesPorMes) {
                $expedientesPorMes[$r->mes - 1] = (int) $r->total;
            });

        Expediente::where('estado', 'cerrado')
            ->whereNotNull('fecha_cierre')
            ->whereYear('fecha_cierre', $year)
            ->selectRaw('MONTH(fecha_cierre) as mes, COUNT(*) as total')
            ->groupBy('mes')->get()
            ->each(function ($r) use (&$cerradosPorMes) {
                $cerradosPorMes[$r->mes - 1] = (int) $r->total;
            });

        Expediente::where('estado', 'cerrado')
            ->where('honorarios_pagados', true)
            ->whereNotNull('fecha_cierre')
            ->whereYear('fecha_cierre', $year)
            ->selectRaw('MONTH(fecha_cierre) as mes, ROUND(SUM(honorarios_monto)/1000, 1) as total')
            ->groupBy('mes')->get()
            ->each(function ($r) use (&$ingresosPorMes) {
                $ingresosPorMes[$r->mes - 1] = (float) $r->total;
            });

        Comision::where('estado', 'pagada')
            ->whereNotNull('fecha_pago')
            ->whereYear('fecha_pago', $year)
            ->selectRaw('MONTH(fecha_pago) as mes, ROUND(SUM(monto_comision)/1000, 1) as total')
            ->groupBy('mes')->get()
            ->each(function ($r) use (&$comisionesMes) {
                $comisionesMes[$r->mes - 1] = (float) $r->total;
            });

        GastoFinanciado::whereNotNull('fecha_pago')
            ->whereYear('fecha_pago', $year)
            ->selectRaw('MONTH(fecha_pago) as mes, ROUND(SUM(monto)/1000, 1) as total')
            ->groupBy('mes')->get()
            ->each(function ($r) use (&$gastosMes) {
                $gastosMes[$r->mes - 1] = (float) $r->total;
            });

        for ($i = 0; $i < 12; $i++) {
            $flujoNetoMes[$i] = round($ingresosPorMes[$i] - $comisionesMes[$i] - $gastosMes[$i], 1);
        }

        $datasets = [
            [
                'label'           => 'Exp. Abiertos',
                'data'            => array_values($expedientesPorMes),
                'borderColor'     => '#6366f1',
                'backgroundColor' => 'rgba(99,102,241,0.15)',
                'fill'            => true,
                'tension'         => 0.4,
                'yAxisID'         => 'y',
            ],
            [
                'label'           => 'Exp. Cerrados',
                'data'            => array_values($cerradosPorMes),
                'borderColor'     => '#22c55e',
                'backgroundColor' => 'rgba(34,197,94,0.15)',
                'fill'            => true,
                'tension'         => 0.4,
                'yAxisID'         => 'y',
            ],
            [
                'label'           => 'Ingresos (miles $)',
                'data'            => array_values($ingresosPorMes),
                'borderColor'     => '#10b981',
                'backgroundColor' => 'rgba(16,185,129,0.1)',
                'fill'            => false,
                'tension'         => 0.4,
                'borderWidth'     => 2,
                'yAxisID'         => 'y1',
            ],
            [
                'label'           => 'Comisiones (miles $)',
                'data'            => array_values($comisionesMes),
                'borderColor'     => '#ef4444',
                'backgroundColor' => 'rgba(239,68,68,0.1)',
                'fill'            => false,
                'tension'         => 0.4,
                'borderWidth'     => 2,
                'yAxisID'         => 'y1',
            ],
            [
                'label'           => 'Gastos (miles $)',
                'data'            => array_values($gastosMes),
                'borderColor'     => '#f97316',
                'backgroundColor' => 'rgba(249,115,22,0.1)',
                'fill'            => false,
                'tension'         => 0.4,
                'borderWidth'     => 2,
                'yAxisID'         => 'y1',
            ],
            [
                'label'           => 'Flujo Neto (miles $)',
                'data'            => array_values($flujoNetoMes),
                'borderColor'     => '#8b5cf6',
                'backgroundColor' => 'rgba(139,92,246,0.1)',
                'fill'            => false,
                'tension'         => 0.4,
                'borderWidth'     => 2,
                'yAxisID'         => 'y1',
            ],
        ];

        return [
            'year'     => $year,
            'labels'   => $meses,
            'datasets' => $datasets,
        ];
    }
}
