<?php

namespace App\Support;

use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Models\Contacto;
use App\Models\EscaneoQr;
use App\Models\Expediente;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Métricas del embudo de los QR impresos en un rango de fechas:
 * escaneos → registros en la app → solicitudes (prospectos) → expedientes.
 *
 * Cada paso se cuenta por su propia fecha (cuándo escanearon, cuándo se
 * registraron…), así una lona pegada hace un mes sigue sumando expedientes
 * este mes. Todo se agrupa en PHP para que funcione igual en MySQL y SQLite.
 */
class MetricasQr
{
    public const PASOS = ['escaneos', 'registros', 'solicitudes', 'expedientes'];

    public function __construct(
        private readonly CarbonInterface $desde,
        private readonly CarbonInterface $hasta,
        private readonly ?int $asesorId = null,
        private readonly ?string $medio = null,
    ) {}

    /** @return Collection<int, CodigoQr> los QR que entran en el filtro (asesor / medio) */
    public function codigos(): Collection
    {
        return CodigoQr::query()
            ->with('asesor')
            ->when($this->asesorId, fn ($q) => $q->where('asesor_id', $this->asesorId))
            ->when($this->medio,    fn ($q) => $q->where('medio', $this->medio))
            ->get();
    }

    /**
     * Fechas de cada evento por QR: ['escaneos' => [codigo_qr_id => [Carbon…]], …]
     * @return array<string, array<int, list<Carbon>>>
     */
    public function eventos(): array
    {
        $ids = $this->codigos()->pluck('id');
        if ($ids->isEmpty()) {
            return array_fill_keys(self::PASOS, []);
        }

        $rango = fn ($q, string $col = 'created_at') => $q->whereBetween($col, [$this->desde->copy()->startOfDay(), $this->hasta->copy()->endOfDay()]);

        $agrupar = fn (Collection $filas) => $filas
            ->groupBy('codigo_qr_id')
            ->map(fn ($g) => $g->pluck('created_at')->map(fn ($f) => Carbon::parse($f))->values()->all())
            ->all();

        return [
            'escaneos'    => $agrupar($rango(EscaneoQr::whereIn('codigo_qr_id', $ids))->get(['codigo_qr_id', 'created_at'])),
            'registros'   => $agrupar($rango(Acreditado::whereIn('codigo_qr_id', $ids))->get(['codigo_qr_id', 'created_at'])),
            'solicitudes' => $agrupar($rango(Contacto::whereIn('codigo_qr_id', $ids))->get(['codigo_qr_id', 'created_at'])),
            'expedientes' => $agrupar(
                $rango(Expediente::query()->join('contactos', 'contactos.id', '=', 'expedientes.contacto_id'), 'expedientes.created_at')
                    ->whereIn('contactos.codigo_qr_id', $ids)
                    ->get(['contactos.codigo_qr_id as codigo_qr_id', 'expedientes.created_at as created_at'])
            ),
        ];
    }

    /** Totales del rango + conversión escaneo → expediente. */
    public function totales(): array
    {
        $ev = $this->eventos();
        $t  = [];
        foreach (self::PASOS as $paso) {
            $t[$paso] = array_sum(array_map('count', $ev[$paso]));
        }
        $t['conversion'] = $t['escaneos'] > 0 ? round($t['expedientes'] / $t['escaneos'] * 100, 1) : 0.0;
        $t['qrs']        = $this->codigos()->count();

        return $t;
    }

    /** Filas por asesor, ordenadas por escaneos. */
    public function porAsesor(): Collection
    {
        return $this->agruparPor(fn (CodigoQr $qr) => $qr->asesor_id, fn (CodigoQr $qr) => $qr->asesor?->name ?? 'Sin asesor');
    }

    /** Filas por medio (lona, tarjeta…), ordenadas por escaneos. */
    public function porMedio(): Collection
    {
        return $this->agruparPor(fn (CodigoQr $qr) => $qr->medio, fn (CodigoQr $qr) => $qr->medio_label);
    }

    /**
     * Serie semanal para la gráfica: etiquetas y una lista por paso.
     * @return array{labels: list<string>, series: array<string, list<int>>}
     */
    public function porSemana(): array
    {
        $ev      = $this->eventos();
        $inicio  = $this->desde->copy()->startOfWeek();
        $fin     = $this->hasta->copy()->endOfWeek();
        $labels  = [];
        $indice  = [];

        for ($s = $inicio->copy(); $s <= $fin; $s->addWeek()) {
            $indice[$s->format('o-W')] = count($labels);
            $labels[] = $s->translatedFormat('j M');
        }

        $series = [];
        foreach (self::PASOS as $paso) {
            $serie = array_fill(0, count($labels), 0);
            foreach ($ev[$paso] as $fechas) {
                foreach ($fechas as $f) {
                    $k = $f->format('o-W');
                    if (isset($indice[$k])) $serie[$indice[$k]]++;
                }
            }
            $series[$paso] = $serie;
        }

        return ['labels' => $labels, 'series' => $series];
    }

    private function agruparPor(callable $clave, callable $etiqueta): Collection
    {
        $ev    = $this->eventos();
        $filas = [];

        foreach ($this->codigos() as $qr) {
            $k = $clave($qr);
            $filas[$k] ??= ['clave' => $k, 'nombre' => $etiqueta($qr), 'qrs' => 0] + array_fill_keys(self::PASOS, 0);
            $filas[$k]['qrs']++;
            foreach (self::PASOS as $paso) {
                $filas[$k][$paso] += count($ev[$paso][$qr->id] ?? []);
            }
        }

        return collect($filas)
            ->map(function ($f) {
                $f['conversion'] = $f['escaneos'] > 0 ? round($f['expedientes'] / $f['escaneos'] * 100, 1) : 0.0;
                return $f;
            })
            ->sortByDesc('escaneos')
            ->values();
    }
}
