<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AsesorComisionesWidget;
use App\Filament\Widgets\AsesorExpedientesWidget;
use App\Filament\Widgets\AsesorProspectosWidget;
use App\Filament\Widgets\AsesorStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class DashboardAsesor extends BaseDashboard
{
    protected static string  $routePath       = '/dashboard-asesor';
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Mi Dashboard';
    protected static ?string $title           = 'Mi Dashboard';
    protected static ?int    $navigationSort  = -3;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    public function getWidgets(): array
    {
        return [
            AsesorStatsOverview::class,
            AsesorProspectosWidget::class,
            AsesorExpedientesWidget::class,
            AsesorComisionesWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
