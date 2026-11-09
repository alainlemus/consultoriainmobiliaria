<?php

namespace App\Filament\Widgets;

use App\Support\MetricasQr;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Gráfica semanal del embudo de QR. La página Rendimiento de QR la monta con sus filtros. */
class EscaneosQrSemanaChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';
    protected ?string $heading   = 'Por semana';
    protected ?string $maxHeight = '300px';

    public string  $desde    = '';
    public string  $hasta    = '';
    public ?int    $asesorId = null;
    public ?string $medio    = null;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $m = new MetricasQr(Carbon::parse($this->desde), Carbon::parse($this->hasta), $this->asesorId, $this->medio);
        $s = $m->porSemana();

        return [
            'labels'   => $s['labels'],
            'datasets' => [
                ['label' => 'Escaneos',    'data' => $s['series']['escaneos'],    'backgroundColor' => '#B8960C'],
                ['label' => 'Registros',   'data' => $s['series']['registros'],   'backgroundColor' => '#0e7490'],
                ['label' => 'Solicitudes', 'data' => $s['series']['solicitudes'], 'backgroundColor' => '#7e22ce'],
                ['label' => 'Expedientes', 'data' => $s['series']['expedientes'], 'backgroundColor' => '#15803d'],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales'  => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }
}
