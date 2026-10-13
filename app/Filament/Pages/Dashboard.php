<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CrmStatsOverview;
use App\Filament\Widgets\ActividadAnualChart;
use App\Filament\Widgets\ProspectosPendientesCierreWidget;
use App\Filament\Widgets\RankingAsesoresWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // El asesor tiene su propio dashboard; el middleware ya lo redirige
        if (auth()->user()->hasRole('asesor')) {
            return false;
        }

        return auth()->user()->hasRole('super_admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'KPIs';
    protected static ?string $title           = 'KPIs';
    protected static ?int    $navigationSort  = -2;

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            CrmStatsOverview::class,
            ProspectosPendientesCierreWidget::class,
            ActividadAnualChart::class,
            RankingAsesoresWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
