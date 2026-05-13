<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CrmStatsOverview;
use App\Filament\Widgets\ExpedientesSinMovimientoWidget;
use App\Filament\Widgets\FunnelEtapasWidget;
use App\Filament\Widgets\RankingAsesoresWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'KPIs';
    protected static ?string $title = 'KPIs';
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            CrmStatsOverview::class,
            FunnelEtapasWidget::class,
            ExpedientesSinMovimientoWidget::class,
            RankingAsesoresWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
