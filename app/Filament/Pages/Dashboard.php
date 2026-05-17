<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CrmStatsOverview;
use App\Filament\Widgets\ExpedientesAnualesChart;
use App\Filament\Widgets\ExpedientesSinMovimientoWidget;
use App\Filament\Widgets\FunnelEtapasWidget;
use App\Filament\Widgets\KpisMensualesWidget;
use App\Filament\Widgets\ProspectosPendientesCierreWidget;
use App\Filament\Widgets\RankingAsesoresWidget;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        // El asesor no ve este dashboard, pero no debe recibir 403 —
        // el middleware RedirectAsesorToExpedientes lo lleva a su propio dashboard.
        if (auth()->user()->hasRole('asesor')) {
            return false;
        }

        return auth()->user()->hasRole('super_admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'KPIs';
    protected static ?string $title           = 'KPIs';
    protected static ?int    $navigationSort  = -2;

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            ProspectosPendientesCierreWidget::class,
            CrmStatsOverview::class,
            ExpedientesAnualesChart::class,
            KpisMensualesWidget::class,
            FunnelEtapasWidget::class,
            ExpedientesSinMovimientoWidget::class,
            RankingAsesoresWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        $anios = [];
        for ($y = now()->year; $y >= now()->year - 3; $y--) {
            $anios[$y] = (string) $y;
        }

        $meses = [
            ''   => 'Todo el año',
            '1'  => 'Enero',   '2'  => 'Febrero',  '3'  => 'Marzo',
            '4'  => 'Abril',   '5'  => 'Mayo',      '6'  => 'Junio',
            '7'  => 'Julio',   '8'  => 'Agosto',    '9'  => 'Septiembre',
            '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];

        return [
            Action::make('exportar_excel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->form([
                    Select::make('anio')
                        ->label('Año')
                        ->options($anios)
                        ->default(now()->year)
                        ->required(),
                    Select::make('mes')
                        ->label('Mes')
                        ->options($meses)
                        ->default('')
                        ->placeholder('Todo el año'),
                ])
                ->action(function (array $data) {
                    $params = http_build_query([
                        'anio' => $data['anio'],
                        'mes'  => $data['mes'] ?? '',
                    ]);
                    $this->redirect(url('/admin/reportes/kpis/excel?' . $params));
                })
                ->visible(fn () => auth()->user()?->hasRole('super_admin')),

            Action::make('exportar_pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    Select::make('anio')
                        ->label('Año')
                        ->options($anios)
                        ->default(now()->year)
                        ->required(),
                    Select::make('mes')
                        ->label('Mes')
                        ->options($meses)
                        ->default('')
                        ->placeholder('Todo el año'),
                ])
                ->action(function (array $data) {
                    $params = http_build_query([
                        'anio' => $data['anio'],
                        'mes'  => $data['mes'] ?? '',
                    ]);
                    $this->redirect(url('/admin/reportes/kpis/pdf?' . $params));
                })
                ->visible(fn () => auth()->user()?->hasRole('super_admin')),
        ];
    }
}

