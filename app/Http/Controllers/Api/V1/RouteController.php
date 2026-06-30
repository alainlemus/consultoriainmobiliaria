<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RoutePoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * POST /api/v1/routes/points
     * Guarda uno o varios puntos de ruta del asesor.
     * Acepta punto individual o batch de puntos (para sync offline).
     */
    public function storePoints(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'points' => ['required', 'array', 'min:1', 'max:100'],
            'points.*.lat'       => ['required', 'numeric', 'between:-90,90'],
            'points.*.lng'       => ['required', 'numeric', 'between:-180,180'],
            'points.*.precision'  => ['nullable', 'integer', 'min:0'],
            'points.*.velocidad' => ['nullable', 'numeric', 'min:0'],
            'points.*.timestamp' => ['required', 'date'],
        ]);

        $saved = [];
        $now = now();

        foreach ($data['points'] as $point) {
            $saved[] = RoutePoint::create([
                'user_id'   => $user->id,
                'lat'       => $point['lat'],
                'lng'       => $point['lng'],
                'precision' => $point['precision'] ?? 0,
                'velocidad' => $point['velocidad'] ?? 0,
                'timestamp' => $point['timestamp'],
                'synced_at' => $now,
            ]);
        }

        return response()->json([
            'data' => [
                'saved' => count($saved),
                'ids'   => array_column($saved, 'id'),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/routes/asesores
     * Lista de asesores con rutas (solo super_admin).
     */
    public function listAsesores(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('super_admin')) {
            abort(403, 'Solo el administrador puede ver las rutas de otros asesores.');
        }

        $asesores = \App\Models\User::role('asesor')
            ->whereHas('routePoints', fn ($q) => $q->whereDate('timestamp', now()->toDateString()))
            ->withCount(['routePoints as puntos_hoy' => fn ($q) => $q->whereDate('timestamp', now()->toDateString())])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $asesores]);
    }

    /**
     * GET /api/v1/routes/points?asesor_id=X&fecha=Y
     * Devuelve los puntos de ruta para mostrar en el mapa del CRM.
     */
    public function getPoints(Request $request): JsonResponse
    {
        $user = $request->user();

        $asesorId = $request->input('asesor_id');
        $fecha = $request->input('fecha', now()->toDateString());

        if (! $user->hasRole('super_admin')) {
            $asesorId = $user->id;
        }

        if (! $asesorId) {
            return response()->json(['data' => []]);
        }

        $query = RoutePoint::where('user_id', $asesorId)
            ->whereDate('timestamp', $fecha)
            ->orderBy('timestamp');

        $points = $query->get([
            'id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp',
        ]);

        $formatted = $points->map(fn (RoutePoint $p) => [
            'id'        => $p->id,
            'lat'       => $p->lat,
            'lng'       => $p->lng,
            'precision' => $p->precision,
            'velocidad' => $p->velocidad,
            'hora'      => $p->timestamp->format('H:i:s'),
            'timestamp' => $p->timestamp->toIso8601String(),
        ]);

        return response()->json([
            'data' => $formatted,
            'meta' => [
                'asesor_id' => (int) $asesorId,
                'fecha'     => $fecha,
                'total'     => $points->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/routes/dias?asesor_id=X
     * Devuelve las fechas disponibles para un asesor.
     */
    public function getDias(Request $request): JsonResponse
    {
        $user = $request->user();

        $asesorId = $request->input('asesor_id');

        if (! $user->hasRole('super_admin')) {
            $asesorId = $user->id;
        }

        if (! $asesorId) {
            return response()->json(['data' => []]);
        }

        $dias = RoutePoint::where('user_id', $asesorId)
            ->selectRaw('DATE(timestamp) as fecha, COUNT(*) as puntos')
            ->groupByRaw('DATE(timestamp)')
            ->orderByDesc('fecha')
            ->limit(30)
            ->get();

        return response()->json(['data' => $dias]);
    }
}
