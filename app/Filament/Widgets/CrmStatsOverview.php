<?php

namespace App\Filament\Widgets;

use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CrmStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $data = Cache::remember('dashboard:crm_stats', 90, function () {
            $activos = Contacto::whereNotIn('estado_prospecto', ['descartado']);

            return [
                'prospectosMes'  => Contacto::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                'prospectosNuevos' => Contacto::where('estado_prospecto', 'nuevo')->count(),
                'sinDocumentos'  => (clone $activos)->whereDoesntHave('documentos', fn ($q) => $q->whereNotNull('ruta_archivo'))->count(),
                'documentosMes'  => DocumentoExpediente::whereNotNull('ruta_archivo')
                    ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                'porCapturar'    => DocumentoExpediente::whereNotNull('ruta_archivo')->whereNull('capturado_at')->count(),
            ];
        });

        return [
            Stat::make('Prospectos del mes', $data['prospectosMes'])
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Prospectos nuevos', $data['prospectosNuevos'])
                ->description('Sin atender')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Sin documentos', $data['sinDocumentos'])
                ->description('Prospectos que aún no suben nada')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('danger'),

            Stat::make('Documentos del mes', $data['documentosMes'])
                ->description('Subidos desde la app')
                ->descriptionIcon('heroicon-m-document-arrow-up')
                ->color('success'),

            Stat::make('Por capturar', $data['porCapturar'])
                ->description('Documentos pendientes de capturar en el panel')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color($data['porCapturar'] > 0 ? 'warning' : 'success'),
        ];
    }
}
