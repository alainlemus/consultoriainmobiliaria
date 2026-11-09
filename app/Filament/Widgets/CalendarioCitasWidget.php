<?php

namespace App\Filament\Widgets;

use App\Models\Cita;
use App\Services\CitaService;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Calendario mensual de citas, arriba de la lista en CRM → Citas.
 * El super admin ve todas; el asesor solo las de sus clientes.
 */
class CalendarioCitasWidget extends Widget
{
    protected string $view = 'filament.widgets.calendario-citas';

    protected int | string | array $columnSpan = 'full';

    /** Se renderiza con la página (no en diferido) para que el calendario aparezca de inmediato. */
    protected static bool $isLazy = false;

    public int $anio;
    public int $mes;

    public function mount(): void
    {
        $hoy = Carbon::now(CitaService::TZ);
        $this->anio = $hoy->year;
        $this->mes  = $hoy->month;
    }

    public function anterior(): void
    {
        $d = Carbon::create($this->anio, $this->mes, 1, 0, 0, 0, CitaService::TZ)->subMonth();
        [$this->anio, $this->mes] = [$d->year, $d->month];
    }

    public function siguiente(): void
    {
        $d = Carbon::create($this->anio, $this->mes, 1, 0, 0, 0, CitaService::TZ)->addMonth();
        [$this->anio, $this->mes] = [$d->year, $d->month];
    }

    public function hoy(): void
    {
        $this->mount();
    }

    protected function getViewData(): array
    {
        $tz     = CitaService::TZ;
        $inicio = Carbon::create($this->anio, $this->mes, 1, 0, 0, 0, $tz);
        $fin    = $inicio->copy()->endOfMonth();

        $query = Cita::query()->with(['cobertura', 'asesor'])
            ->whereBetween('inicia_at', [$inicio->copy()->startOfWeek()->utc(), $fin->copy()->endOfWeek()->utc()])
            ->orderBy('inicia_at');
        if (Auth::user()?->hasRole('asesor') && ! Auth::user()->hasRole('super_admin')) {
            $query->where('asesor_id', Auth::id());
        }

        $porDia = $query->get()->groupBy(fn (Cita $c) => $c->inicia_at->setTimezone($tz)->toDateString());

        // Celdas de lunes a domingo cubriendo el mes completo
        $celdas = [];
        $hoy    = Carbon::now($tz)->toDateString();
        for ($d = $inicio->copy()->startOfWeek(); $d <= $fin->copy()->endOfWeek(); $d->addDay()) {
            $clave = $d->toDateString();
            $celdas[] = [
                'fecha'    => $clave,
                'dia'      => $d->day,
                'delMes'   => $d->month === $this->mes,
                'esHoy'    => $clave === $hoy,
                'citas'    => ($porDia[$clave] ?? collect())->map(fn (Cita $c) => [
                    'id'     => $c->id,
                    'hora'   => $c->inicia_at->setTimezone($tz)->format('g:i a'),
                    'nombre' => $c->nombre,
                    'tipo'   => $c->tipo,
                    'estado' => $c->estado,
                    'lugar'  => $c->lugar(),
                    'url'    => \App\Filament\Resources\CitaResource::getUrl('edit', ['record' => $c]),
                ])->values()->all(),
            ];
        }

        return [
            'titulo'   => ucfirst($inicio->locale('es')->translatedFormat('F Y')),
            'celdas'   => $celdas,
            'semanas'  => array_chunk($celdas, 7),
            'leyenda'  => Cita::ESTADOS,
        ];
    }
}
