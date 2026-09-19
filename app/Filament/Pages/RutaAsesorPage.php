<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\RoutePoint;
use App\Models\Ubicacion;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Renderless;

class RutaAsesorPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Rutas de Asesores';
    protected static ?string $title = 'Rutas de Asesores';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $slug            = 'rutas-asesores';

    protected string $view = 'filament.pages.ruta-asesor-page';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:RutaAsesorPage') ?? false;
    }

    public function getAsesores(): Collection
    {
        return User::role('asesor')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Días con puntos GPS registrados o visitas, para un asesor puntual.
     *
     * Combina route_points (inicio/fin del toggle) + ubicaciones (visitas) —
     * un día en el que el asesor solo registró visitas sin activar el toggle
     * también debe aparecer (ver RouteController::getDias en la API).
     */
    #[Renderless]
    public function getDiasDisponiblesAsesor(string $asesorId): array
    {
        return $this->diasDisponibles(fn ($q) => $q->where('user_id', $asesorId));
    }

    /**
     * Días con puntos GPS registrados o visitas, entre todos los asesores.
     */
    #[Renderless]
    public function getDiasDisponiblesTodos(): array
    {
        return $this->diasDisponibles(fn ($q) => $q->whereHas('user', fn ($q2) => $q2->role('asesor')));
    }

    private function diasDisponibles(\Closure $scope): array
    {
        $diasRoute = $scope(RoutePoint::query())
            ->selectRaw('DATE(timestamp) as fecha')
            ->pluck('fecha');

        $diasUbicaciones = $scope(Ubicacion::query()->whereNotNull('latitud')->whereNotNull('longitud'))
            ->selectRaw('DATE(visitado_en) as fecha')
            ->pluck('fecha');

        return $diasRoute->merge($diasUbicaciones)
            ->unique()
            ->sortDesc()
            ->take(30)
            ->values()
            ->toArray();
    }

    /**
     * Puntos de la ruta de un asesor en una fecha concreta.
     *
     * Combina route_points (inicio/fin del toggle) + ubicaciones (visitas a
     * clientes/escuelas/propiedades, ya capturadas en foreground desde
     * app/mapa.tsx) — mismo criterio que RouteController::getPoints en la
     * API, para que la ruta siga teniendo sentido ahora que ya no hay
     * tracking continuo en background.
     */
    #[Renderless]
    public function getRutasAsesor(string $asesorId, string $fecha): array
    {
        $routePoints = RoutePoint::where('user_id', $asesorId)
            ->whereDate('timestamp', $fecha)
            ->get(['id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp']);

        $ubicaciones = Ubicacion::where('user_id', $asesorId)
            ->whereDate('visitado_en', $fecha)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get(['id', 'latitud', 'longitud', 'visitado_en']);

        return $this->mergePuntos($routePoints, $ubicaciones);
    }

    /**
     * Puntos de la ruta de todos los asesores en una fecha concreta.
     * Dos consultas para todos los asesores (evita N+1 por asesor/día).
     */
    #[Renderless]
    public function getRutasTodos(string $fecha): array
    {
        $routePointsPorAsesor = RoutePoint::whereDate('timestamp', $fecha)
            ->get(['id', 'user_id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp'])
            ->groupBy('user_id');

        $ubicacionesPorAsesor = Ubicacion::whereDate('visitado_en', $fecha)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get(['id', 'user_id', 'latitud', 'longitud', 'visitado_en'])
            ->groupBy('user_id');

        return $this->getAsesores()
            ->filter(fn (User $asesor) => $routePointsPorAsesor->has($asesor->id) || $ubicacionesPorAsesor->has($asesor->id))
            ->map(fn (User $asesor) => [
                'name'   => $asesor->name,
                'puntos' => $this->mergePuntos(
                    $routePointsPorAsesor->get($asesor->id, collect()),
                    $ubicacionesPorAsesor->get($asesor->id, collect()),
                ),
            ])
            ->values()
            ->toArray();
    }

    private function mergePuntos(Collection $routePoints, Collection $ubicaciones): array
    {
        return $routePoints->map(fn (RoutePoint $p) => $this->formatPunto($p))
            ->concat($ubicaciones->map(fn (Ubicacion $u) => $this->formatUbicacion($u)))
            ->sortBy('timestamp')
            ->values()
            ->toArray();
    }

    private function formatPunto(RoutePoint $p): array
    {
        return [
            'id'        => 'rp_' . $p->id,
            'lat'       => $p->lat,
            'lng'       => $p->lng,
            'precision' => $p->precision,
            'velocidad' => $p->velocidad,
            'hora'      => $p->timestamp->format('H:i:s'),
            'timestamp' => $p->timestamp->toIso8601String(),
        ];
    }

    private function formatUbicacion(Ubicacion $u): array
    {
        return [
            'id'        => 'ub_' . $u->id,
            'lat'       => $u->latitud,
            'lng'       => $u->longitud,
            'precision' => 0,
            'velocidad' => 0,
            'hora'      => $u->visitado_en->format('H:i:s'),
            'timestamp' => $u->visitado_en->toIso8601String(),
        ];
    }
}
