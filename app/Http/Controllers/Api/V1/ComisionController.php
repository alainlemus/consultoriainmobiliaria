<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComisionController extends Controller
{
    /**
     * Lista paginada de comisiones del asesor autenticado.
     *
     * Reglas de negocio:
     *  - Las comisiones "pagadas"   → siempre se muestran.
     *  - Las comisiones "pendientes/aprobadas" → solo si el expediente está cerrado.
     *  - Las comisiones "rechazadas" → no se muestran en la app.
     *
     * Query params:
     *  - estado: pagada | pendiente  (filtra la lista; "pendiente" incluye "aprobada")
     *  - page
     */
    public function index(Request $request): JsonResponse
    {
        $asesorId = $request->user()->id;
        $estado   = $request->query('estado'); // 'pagada' | 'pendiente' | null

        $query = Comision::with(['expediente:id,acreditado_nombre,estado,monto_credito'])
            ->where('asesor_id', $asesorId)
            ->where(function ($q) use ($estado) {
                if ($estado === 'pagada') {
                    $q->where('comisiones.estado', 'pagada');
                } elseif ($estado === 'pendiente') {
                    // Pendientes solo de expedientes cerrados
                    $q->whereIn('comisiones.estado', ['pendiente', 'aprobada'])
                      ->whereHas('expediente', fn ($e) => $e->where('estado', 'cerrado'));
                } else {
                    // Sin filtro: pagadas + pendientes/aprobadas de expedientes cerrados
                    $q->where('comisiones.estado', 'pagada')
                      ->orWhere(function ($sub) {
                          $sub->whereIn('comisiones.estado', ['pendiente', 'aprobada'])
                              ->whereHas('expediente', fn ($e) => $e->where('estado', 'cerrado'));
                      });
                }
            })
            ->orderByRaw("FIELD(comisiones.estado, 'pendiente', 'aprobada', 'pagada')")
            ->orderByDesc('fecha_generacion');

        $paginated = $query->paginate(20);

        $items = $paginated->map(fn (Comision $c) => [
            'id'                  => $c->id,
            'expediente_id'       => $c->expediente_id,
            'acreditado'          => $c->expediente?->acreditado_nombre,
            'monto_credito'       => $c->expediente?->monto_credito,
            'expediente_estado'   => $c->expediente?->estado,
            'monto_base'          => $c->monto_base,
            'porcentaje_comision' => $c->porcentaje_comision,
            'monto_comision'      => $c->monto_comision,
            'estado'              => $c->estado,
            'fecha_generacion'    => $c->fecha_generacion?->format('Y-m-d'),
            'fecha_aprobacion'    => $c->fecha_aprobacion?->format('Y-m-d'),
            'fecha_pago'          => $c->fecha_pago?->format('Y-m-d'),
            'notas'               => $c->notas,
        ]);

        return response()->json([
            'data'          => $items,
            'current_page'  => $paginated->currentPage(),
            'last_page'     => $paginated->lastPage(),
            'total'         => $paginated->total(),
        ]);
    }

    /**
     * Resumen de totales para las tarjetas del dashboard.
     */
    public function resumen(Request $request): JsonResponse
    {
        $asesorId = $request->user()->id;

        $totalPagado = Comision::where('asesor_id', $asesorId)
            ->where('estado', 'pagada')
            ->sum('monto_comision');

        $totalPendiente = Comision::where('asesor_id', $asesorId)
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->whereHas('expediente', fn ($e) => $e->where('estado', 'cerrado'))
            ->sum('monto_comision');

        $cantidadPagadas = Comision::where('asesor_id', $asesorId)
            ->where('estado', 'pagada')
            ->count();

        $cantidadPendientes = Comision::where('asesor_id', $asesorId)
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->whereHas('expediente', fn ($e) => $e->where('estado', 'cerrado'))
            ->count();

        return response()->json([
            'data' => [
                'total_pagado'        => (float) $totalPagado,
                'total_pendiente'     => (float) $totalPendiente,
                'cantidad_pagadas'    => $cantidadPagadas,
                'cantidad_pendientes' => $cantidadPendientes,
            ],
        ]);
    }
}
