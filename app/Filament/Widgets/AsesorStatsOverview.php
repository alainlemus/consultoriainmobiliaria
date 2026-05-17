<?php

namespace App\Filament\Widgets;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\Expediente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AsesorStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    protected function getStats(): array
    {
        $id = Auth::id();

        $prospectos = Contacto::where('asesor_id', $id)
            ->whereNotIn('estado_prospecto', ['descartado', 'convertido'])
            ->count();

        $pendientesCierre = Contacto::where('asesor_id', $id)
            ->where('estado_prospecto', 'pendiente_cierre')
            ->count();

        $expedientesActivos = Expediente::where('asesor_id', $id)
            ->whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])
            ->count();

        $expedientesCerrados = Expediente::where('asesor_id', $id)
            ->where('estado', 'cerrado')
            ->count();

        $comisionesPendientes = Comision::where('asesor_id', $id)
            ->where('estado', 'pendiente')
            ->sum('monto_comision');

        $comisionesAprobadas = Comision::where('asesor_id', $id)
            ->where('estado', 'aprobada')
            ->sum('monto_comision');

        $comisionesTotal = Comision::where('asesor_id', $id)
            ->where('estado', 'pagada')
            ->sum('monto_comision');

        return [
            Stat::make('Prospectos activos', $prospectos)
                ->description('Sin contar descartados ni convertidos')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Pendientes de cierre', $pendientesCierre)
                ->description('Listos para iniciar expediente')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Expedientes en proceso', $expedientesActivos)
                ->description('Activos, aprobados o firmados')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('info'),

            Stat::make('Expedientes cerrados', $expedientesCerrados)
                ->description('Trámites concluidos')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Comisiones por cobrar', '$' . number_format($comisionesPendientes + $comisionesAprobadas, 0, '.', ','))
                ->description('Pendientes + aprobadas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Comisiones cobradas', '$' . number_format($comisionesTotal, 0, '.', ','))
                ->description('Historial total pagado')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
