<?php

namespace App\Filament\Widgets;

use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

        $data = Cache::remember("dashboard:asesor_stats:{$id}", 90, function () use ($id) {
            $mios = Contacto::where('asesor_id', $id)->whereNotIn('estado_prospecto', ['descartado']);

            return [
                'prospectos'    => (clone $mios)->count(),
                'prospectosMes' => (clone $mios)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                'sinDocumentos' => (clone $mios)->whereDoesntHave('documentos', fn ($q) => $q->whereNotNull('ruta_archivo'))->count(),
                'documentosMes' => DocumentoExpediente::whereNotNull('ruta_archivo')
                    ->whereHas('expediente', fn ($q) => $q->where('asesor_id', $id))
                    ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)
                    ->count(),
            ];
        });

        return [
            Stat::make('Mis prospectos', $data['prospectos'])
                ->description('Sin contar descartados')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Registrados este mes', $data['prospectosMes'])
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Sin documentos', $data['sinDocumentos'])
                ->description('Prospectos que aún no suben nada')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('warning'),

            Stat::make('Documentos del mes', $data['documentosMes'])
                ->description('Subidos desde la app')
                ->descriptionIcon('heroicon-m-document-arrow-up')
                ->color('info'),
        ];
    }
}
