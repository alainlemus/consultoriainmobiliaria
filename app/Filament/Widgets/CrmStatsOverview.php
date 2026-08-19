<?php

namespace App\Filament\Widgets;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\Expediente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CrmStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        $data = Cache::remember('dashboard:crm_stats', 90, function () {
            return [
                'activos' => Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])->count(),
                'cerradosMes' => Expediente::where('estado', 'cerrado')
                    ->whereMonth('fecha_cierre', now()->month)
                    ->whereYear('fecha_cierre', now()->year)
                    ->count(),
                'ingresosEstimados' => Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado', 'cerrado'])
                    ->sum('honorarios_monto'),
                'prospectoNuevos' => Contacto::where('estado_prospecto', 'nuevo')->count(),
                'comisionesPendientes' => Comision::where('estado', 'aprobada')->sum('monto_comision'),
            ];
        });

        return [
            Stat::make('Expedientes activos', $data['activos'])
                ->description('En proceso, aprobados o firmados')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('primary'),

            Stat::make('Cerrados este mes', $data['cerradosMes'])
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Prospectos nuevos', $data['prospectoNuevos'])
                ->description('Sin atender del sitio web')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            Stat::make('Honorarios estimados', '$' . number_format($data['ingresosEstimados'], 0, '.', ','))
                ->description('Expedientes activos + cerrados')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Comisiones por pagar', '$' . number_format($data['comisionesPendientes'], 0, '.', ','))
                ->description('Comisiones aprobadas pendientes de pago')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),
        ];
    }
}
