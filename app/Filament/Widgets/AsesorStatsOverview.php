<?php

namespace App\Filament\Widgets;

use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
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
            $mios = Contacto::where('asesor_id', $id)->whereNotIn('estado_prospecto', ['descartado', 'convertido'])->whereDoesntHave('expedientes');

            return [
                'prospectos'    => (clone $mios)->count(),
                'prospectosMes' => Contacto::where('asesor_id', $id)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                'expedientes'   => Expediente::where('asesor_id', $id)->count(),
                'documentosMes' => DocumentoExpediente::whereNotNull('ruta_archivo')
                    ->whereHas('expediente', fn ($q) => $q->where('asesor_id', $id))
                    ->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)
                    ->count(),
            ];
        });

        return [
            Stat::make('Mis prospectos', $data['prospectos'])
                ->description('En evaluación')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Registrados este mes', $data['prospectosMes'])
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('Mis expedientes', $data['expedientes'])
                ->description('Clientes con expediente')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('info'),

            Stat::make('Documentos del mes', $data['documentosMes'])
                ->description('Subidos desde la app')
                ->descriptionIcon('heroicon-m-document-arrow-up')
                ->color('warning'),
        ];
    }
}
