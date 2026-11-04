<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RoutePoint;
use App\Models\Ubicacion;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * Los asesores trabajan en hora del centro de México. Las horas se guardan
     * en UTC (la app manda ISO con Z), así que "el día" de una ruta y la hora
     * que se muestra se calculan en esta zona; si no, una visita de las 7 pm
     * caería en la ruta del día siguiente y las horas saldrían +6 h.
     */
    private const ZONA = 'America/Mexico_City';

    /** Rango UTC [inicio, fin) que corresponde a un día local. */
    private function rangoDia(string $fecha): array
    {
        $inicio = CarbonImmutable::parse($fecha, self::ZONA)->startOfDay();

        return [$inicio->utc(), $inicio->addDay()->utc()];
    }

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
        //
        // "puntos" combina route_points (inicio/fin de ruta) + ubicaciones
        // (visitas a clientes/escuelas/propiedades con coordenadas) — ambas
        // fuentes alimentan el recorrido del asesor, ver getPoints().
        [$desde, $hasta] = $this->rangoDia(now(self::ZONA)->toDateString());

        $asesores = \App\Models\User::role('asesor')
            ->where('activo', true)
            ->withCount(['routePoints as rp_hoy'   => fn ($q) => $q->where('timestamp', '>=', $desde)->where('timestamp', '<', $hasta)])
            ->withCount(['routePoints as rp_total'])
            ->withCount(['ubicaciones as ub_hoy'    => fn ($q) => $q->where('visitado_en', '>=', $desde)->where('visitado_en', '<', $hasta)->whereNotNull('latitud')])
            ->withCount(['ubicaciones as ub_total'   => fn ($q) => $q->whereNotNull('latitud')])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($a) => [
                'id'          => $a->id,
                'name'        => $a->name,
                'puntos_hoy'  => $a->rp_hoy + $a->ub_hoy,
                'total_puntos'=> $a->rp_total + $a->ub_total,
            ]);

        return response()->json(['data' => $asesores]);
    }

    /**
     * GET /api/v1/routes/points?asesor_id=X&fecha=Y
     * Devuelve los puntos de ruta para mostrar en el mapa del CRM.
     *
     * `asesor_id=todos` (solo super_admin) devuelve los puntos de TODOS los
     * asesores para esa fecha, cada uno etiquetado con su asesor_id/nombre
     * para poder pintarlos con colores distintos en el mapa.
     *
     * Combina dos fuentes, ordenadas por hora real del evento (no por hora
     * de sincronización — un asesor sin señal en la sierra puede sincronizar
     * horas después y el orden cronológico se mantiene igual):
     *  - route_points: inicio/fin de ruta (toggle "Rastreo de ubicación").
     *  - ubicaciones:  visitas a clientes/escuelas/propiedades con coordenadas,
     *    ya capturadas por "Registrar visita" en el mapa.
     */
    public function getPoints(Request $request): JsonResponse
    {
        $user = $request->user();

        $asesorId = $request->input('asesor_id');
        $fecha = $request->input('fecha', now(self::ZONA)->toDateString());

        if (! $user->hasRole('super_admin')) {
            $asesorId = $user->id;
        }

        if (! $asesorId) {
            return response()->json(['data' => []]);
        }

        $todos = $asesorId === 'todos';

        [$desde, $hasta] = $this->rangoDia($fecha);
        $routeQuery = RoutePoint::where('timestamp', '>=', $desde)->where('timestamp', '<', $hasta);
        $ubicacionQuery = Ubicacion::where('visitado_en', '>=', $desde)->where('visitado_en', '<', $hasta)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud');

        if ($todos) {
            $routeQuery->whereHas('user', fn ($q) => $q->role('asesor'))->with('user:id,name');
            $ubicacionQuery->whereHas('user', fn ($q) => $q->role('asesor'))->with('user:id,name');
        } else {
            $routeQuery->where('user_id', $asesorId);
            $ubicacionQuery->where('user_id', $asesorId);
        }

        $routePoints = $routeQuery->get(['id', 'user_id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp']);
        $ubicaciones = $ubicacionQuery->get(['id', 'user_id', 'latitud', 'longitud', 'tipo', 'nombre_lugar', 'visitado_en']);

        $formattedRoute = $routePoints->map(fn (RoutePoint $p) => [
            'id'            => 'rp_' . $p->id,
            'lat'           => $p->lat,
            'lng'           => $p->lng,
            'precision'     => $p->precision,
            'velocidad'     => $p->velocidad,
            'tipo'          => 'gps',
            'nombre_lugar'  => null,
            'hora'          => $p->timestamp->copy()->setTimezone(self::ZONA)->format('H:i:s'),
            'timestamp'     => $p->timestamp->toIso8601String(),
            'asesor_id'     => $p->user_id,
            'asesor_nombre' => $todos ? $p->user?->name : null,
        ]);

        $formattedUbicaciones = $ubicaciones->map(fn (Ubicacion $u) => [
            'id'            => 'ub_' . $u->id,
            'lat'           => $u->latitud,
            'lng'           => $u->longitud,
            'precision'     => 0,
            'velocidad'     => 0,
            'tipo'          => $u->tipo,
            'nombre_lugar'  => $u->nombre_lugar,
            'hora'          => $u->visitado_en->copy()->setTimezone(self::ZONA)->format('H:i:s'),
            'timestamp'     => $u->visitado_en->toIso8601String(),
            'asesor_id'     => $u->user_id,
            'asesor_nombre' => $todos ? $u->user?->name : null,
        ]);

        $puntos = $formattedRoute->concat($formattedUbicaciones)
            ->sortBy('timestamp')
            ->values();

        return response()->json([
            'data' => $puntos,
            'meta' => [
                'asesor_id' => $todos ? null : (int) $asesorId,
                'fecha'     => $fecha,
                'total'     => $puntos->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/routes/dias?asesor_id=X
     * Devuelve las fechas disponibles para un asesor.
     * `asesor_id=todos` (solo super_admin) devuelve la unión de fechas de
     * todos los asesores.
     *
     * Combina route_points (inicio/fin) + ubicaciones (visitas) — un día en
     * el que el asesor solo registró visitas sin activar el toggle de ruta
     * también debe aparecer.
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

        $routeQuery = RoutePoint::query();
        $ubicacionQuery = Ubicacion::query()->whereNotNull('latitud')->whereNotNull('longitud');

        if ($asesorId === 'todos') {
            $routeQuery->whereHas('user', fn ($q) => $q->role('asesor'));
            $ubicacionQuery->whereHas('user', fn ($q) => $q->role('asesor'));
        } else {
            $routeQuery->where('user_id', $asesorId);
            $ubicacionQuery->where('user_id', $asesorId);
        }

        // Agrupar por día local en PHP (DATE() de SQL agrupa en UTC). Solo se
        // muestran los últimos 30 días con registros, así que basta con ~40 días.
        $limite = now()->subDays(40);
        $porDia = fn ($fechas) => $fechas
            ->map(fn ($f) => \Illuminate\Support\Carbon::parse($f)->setTimezone(self::ZONA)->toDateString())
            ->countBy();

        $diasRoute = $porDia($routeQuery->where('timestamp', '>=', $limite)->pluck('timestamp'));
        $diasUbicaciones = $porDia($ubicacionQuery->where('visitado_en', '>=', $limite)->pluck('visitado_en'));

        $dias = collect($diasRoute->keys())
            ->merge($diasUbicaciones->keys())
            ->unique()
            ->map(fn ($fecha) => [
                'fecha'  => $fecha,
                'puntos' => ($diasRoute[$fecha] ?? 0) + ($diasUbicaciones[$fecha] ?? 0),
            ])
            ->sortByDesc('fecha')
            ->take(30)
            ->values();

        return response()->json(['data' => $dias]);
    }
}
