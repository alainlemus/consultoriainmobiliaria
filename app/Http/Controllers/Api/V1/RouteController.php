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
     * Lista todos los asesores activos (solo super_admin).
     * Incluye cuántos puntos registraron hoy para mostrar estado en la app.
     */
    public function listAsesores(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole('super_admin')) {
            abort(403, 'Solo el administrador puede ver las rutas de otros asesores.');
        }

        // Devolver TODOS los asesores activos, no solo los que registraron ruta hoy.
        // El filtro anterior impedía ver rutas de días anteriores porque
        // si el asesor no tenía ruta hoy nunca aparecía en la lista.
        $asesores = \App\Models\User::role('asesor')
            ->where('activo', true)
            ->withCount(['routePoints as puntos_hoy' => fn ($q) => $q->whereDate('timestamp', now()->toDateString())])
            ->withCount(['routePoints as total_puntos'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $asesores]);
    }

    /**
     * GET /api/v1/routes/points?asesor_id=X&fecha=Y
     * Devuelve los puntos de ruta para mostrar en el mapa del CRM.
     *
     * `asesor_id=todos` (solo super_admin) devuelve los puntos de TODOS los
     * asesores para esa fecha, cada uno etiquetado con su asesor_id/nombre
     * para poder pintarlos con colores distintos en el mapa.
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

        $todos = $asesorId === 'todos';

        $query = RoutePoint::whereDate('timestamp', $fecha)->orderBy('timestamp');

        if ($todos) {
            $query->whereHas('user', fn ($q) => $q->role('asesor'))->with('user:id,name');
        } else {
            $query->where('user_id', $asesorId);
        }

        $points = $query->get([
            'id', 'user_id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp',
        ]);

        $formatted = $points->map(fn (RoutePoint $p) => [
            'id'            => $p->id,
            'lat'           => $p->lat,
            'lng'           => $p->lng,
            'precision'     => $p->precision,
            'velocidad'     => $p->velocidad,
            'hora'          => $p->timestamp->format('H:i:s'),
            'timestamp'     => $p->timestamp->toIso8601String(),
            'asesor_id'     => $p->user_id,
            'asesor_nombre' => $todos ? $p->user?->name : null,
        ]);

        return response()->json([
            'data' => $formatted,
            'meta' => [
                'asesor_id' => $todos ? null : (int) $asesorId,
                'fecha'     => $fecha,
                'total'     => $points->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/routes/dias?asesor_id=X
     * Devuelve las fechas disponibles para un asesor.
     * `asesor_id=todos` (solo super_admin) devuelve la unión de fechas de
     * todos los asesores.
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

        $query = RoutePoint::query();

        if ($asesorId === 'todos') {
            $query->whereHas('user', fn ($q) => $q->role('asesor'));
        } else {
            $query->where('user_id', $asesorId);
        }

        $dias = $query->selectRaw('DATE(timestamp) as fecha, COUNT(*) as puntos')
            ->groupByRaw('DATE(timestamp)')
            ->orderByDesc('fecha')
            ->limit(30)
            ->get();

        return response()->json(['data' => $dias]);
    }
}
