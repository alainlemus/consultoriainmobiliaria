<?php

namespace App\Filament\Widgets;

use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class ActividadAnualChart extends ChartWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $heading = 'Prospectos y documentos por mes';
    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $year = now()->year;

        [$prospectos, $documentos] = Cache::remember("dashboard:actividad_anual:{$year}", 300, fn () => [
            $this->porMes(Contacto::query(), $year),
            $this->porMes(DocumentoExpediente::whereNotNull('ruta_archivo'), $year),
        ]);

        return [
            'datasets' => [
                ['label' => 'Prospectos registrados', 'data' => $prospectos, 'backgroundColor' => '#B8960C'],
                ['label' => 'Documentos subidos',     'data' => $documentos, 'backgroundColor' => '#0e7490'],
            ],
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        ];
    }

    /** @return list<int> */
    private function porMes($query, int $year): array
    {
        $meses = array_fill(0, 12, 0);

        $query->whereYear('created_at', $year)->get(['created_at'])
            ->each(function ($r) use (&$meses) {
                $meses[$r->created_at->month - 1]++;
            });

        return $meses;
    }
}
