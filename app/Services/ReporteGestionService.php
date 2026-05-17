<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\SeguimientoExpediente;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reúne todos los datos necesarios para los reportes de gestión
 * (diario, semanal, mensual) en una estructura uniforme.
 */
class ReporteGestionService
{
    /**
     * Genera el array de datos para el reporte de un período.
     *
     * @param  Carbon  $desde  Inicio del período (inclusive)
     * @param  Carbon  $hasta  Fin del período (inclusive, fin del día)
     * @param  string  $tipo   'diario' | 'semanal' | 'mensual'
     */
    public function generar(Carbon $desde, Carbon $hasta, string $tipo): array
    {
        return [
            'tipo'           => $tipo,
            'desde'          => $desde,
            'hasta'          => $hasta,
            'generado_en'    => now(),
            'expedientes'    => $this->expedientes($desde, $hasta),
            'prospectos'     => $this->prospectos($desde, $hasta),
            'comisiones'     => $this->comisiones($desde, $hasta),
            'seguimientos'   => $this->seguimientos($desde, $hasta),
            'sin_movimiento' => $this->expedientesSinMovimiento(),
            'documentos'     => $this->documentosPendientes(),
            'por_asesor'     => $this->actividadPorAsesor($desde, $hasta),
        ];
    }

    // ─── Sección 1: Expedientes ────────────────────────────────────────────

    private function expedientes(Carbon $desde, Carbon $hasta): array
    {
        $abiertos = Expediente::whereBetween('created_at', [$desde, $hasta])->get();
        $cerrados = Expediente::where('estado', 'cerrado')
            ->whereBetween('updated_at', [$desde, $hasta])
            ->get();

        $activos = Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])->get();

        $porEstado = Expediente::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        return [
            'abiertos_periodo' => $abiertos->count(),
            'cerrados_periodo'  => $cerrados->count(),
            'activos_total'     => $activos->count(),
            'por_estado'        => $porEstado,
            'lista_cerrados'    => $cerrados->map(fn ($e) => [
                'folio'    => $e->folio,
                'cliente'  => $e->acreditado_nombre,
                'asesor'   => $e->asesor?->name ?? '—',
                'monto'    => $e->honorarios_monto,
            ])->values()->toArray(),
        ];
    }

    // ─── Sección 2: Prospectos ─────────────────────────────────────────────

    private function prospectos(Carbon $desde, Carbon $hasta): array
    {
        $nuevos     = Contacto::whereBetween('created_at', [$desde, $hasta])->count();
        $convertidos = Contacto::where('estado_prospecto', 'convertido')
            ->whereBetween('updated_at', [$desde, $hasta])
            ->count();
        $pendientesCierre = Contacto::where('estado_prospecto', 'pendiente_cierre')->count();

        return [
            'nuevos_periodo'       => $nuevos,
            'convertidos_periodo'  => $convertidos,
            'pendientes_cierre'    => $pendientesCierre,
        ];
    }

    // ─── Sección 3: Comisiones ─────────────────────────────────────────────

    private function comisiones(Carbon $desde, Carbon $hasta): array
    {
        $generadas = Comision::whereBetween('created_at', [$desde, $hasta])->get();
        $pagadas   = Comision::where('estado', 'pagada')
            ->whereBetween('updated_at', [$desde, $hasta])
            ->get();

        $pendientes = Comision::where('estado', 'pendiente')->get();

        return [
            'generadas_count'    => $generadas->count(),
            'generadas_monto'    => $generadas->sum('monto_comision'),
            'pagadas_count'      => $pagadas->count(),
            'pagadas_monto'      => $pagadas->sum('monto_comision'),
            'pendientes_count'   => $pendientes->count(),
            'pendientes_monto'   => $pendientes->sum('monto_comision'),
            'lista_generadas'    => $generadas->map(fn ($c) => [
                'folio'   => $c->expediente?->folio ?? "#{$c->expediente_id}",
                'asesor'  => $c->asesor?->name ?? '—',
                'monto'   => $c->monto_comision,
                'estado'  => $c->estado,
            ])->values()->toArray(),
        ];
    }

    // ─── Sección 4: Seguimientos registrados ──────────────────────────────

    private function seguimientos(Carbon $desde, Carbon $hasta): array
    {
        $total = SeguimientoExpediente::whereBetween('created_at', [$desde, $hasta])->count();
        $porAsesor = SeguimientoExpediente::whereBetween('created_at', [$desde, $hasta])
            ->selectRaw('usuario_id, count(*) as total')
            ->groupBy('usuario_id')
            ->with('usuario')
            ->get()
            ->map(fn ($s) => [
                'asesor' => $s->usuario?->name ?? '—',
                'total'  => $s->total,
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();

        return [
            'total_periodo'  => $total,
            'por_asesor'     => $porAsesor,
        ];
    }

    // ─── Sección 5: Expedientes sin movimiento ─────────────────────────────

    private function expedientesSinMovimiento(int $dias = 7): array
    {
        $corte = Carbon::now()->subDays($dias);

        $expedientes = Expediente::with(['asesor', 'tipoTramite'])
            ->whereIn('estado', ['en_proceso', 'nuevo'])
            ->whereDoesntHave('seguimientos', fn ($q) => $q->where('created_at', '>=', $corte))
            ->get();

        return [
            'umbral_dias' => $dias,
            'total'       => $expedientes->count(),
            'lista'       => $expedientes->map(fn ($e) => [
                'folio'   => $e->folio,
                'cliente' => $e->acreditado_nombre,
                'asesor'  => $e->asesor?->name ?? '—',
                'tipo'    => $e->tipoTramite?->nombre ?? '—',
            ])->values()->toArray(),
        ];
    }

    // ─── Sección 6: Documentos pendientes ─────────────────────────────────

    private function documentosPendientes(): array
    {
        // Expedientes activos con al menos un documento pendiente
        $expedientes = Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])
            ->whereHas('documentos', fn ($q) => $q->where('estado', 'pendiente'))
            ->with(['asesor', 'documentos' => fn ($q) => $q->where('estado', 'pendiente')])
            ->get();

        return [
            'expedientes_con_pendientes' => $expedientes->count(),
            'documentos_total'           => $expedientes->sum(fn ($e) => $e->documentos->count()),
            'lista'                      => $expedientes->map(fn ($e) => [
                'folio'    => $e->folio,
                'cliente'  => $e->acreditado_nombre,
                'asesor'   => $e->asesor?->name ?? '—',
                'pendientes' => $e->documentos->count(),
            ])->sortByDesc('pendientes')->values()->toArray(),
        ];
    }

    // ─── Sección 7: Actividad por asesor ──────────────────────────────────

    private function actividadPorAsesor(Carbon $desde, Carbon $hasta): array
    {
        $asesores = User::role('asesor')->where('activo', true)->get();

        return $asesores->map(function (User $asesor) use ($desde, $hasta) {
            $expedientesAbiertos = Expediente::where('asesor_id', $asesor->id)
                ->whereBetween('created_at', [$desde, $hasta])
                ->count();

            $expedientesCerrados = Expediente::where('asesor_id', $asesor->id)
                ->where('estado', 'cerrado')
                ->whereBetween('updated_at', [$desde, $hasta])
                ->count();

            $seguimientos = SeguimientoExpediente::where('usuario_id', $asesor->id)
                ->whereBetween('created_at', [$desde, $hasta])
                ->count();

            $prospectos = Contacto::where('asesor_id', $asesor->id)
                ->whereBetween('created_at', [$desde, $hasta])
                ->count();

            $comisionesMonto = Comision::where('asesor_id', $asesor->id)
                ->whereBetween('created_at', [$desde, $hasta])
                ->sum('monto_comision');

            return [
                'asesor'               => $asesor->name,
                'expedientes_abiertos' => $expedientesAbiertos,
                'expedientes_cerrados' => $expedientesCerrados,
                'seguimientos'         => $seguimientos,
                'prospectos'           => $prospectos,
                'comisiones_monto'     => $comisionesMonto,
            ];
        })->values()->toArray();
    }
}
