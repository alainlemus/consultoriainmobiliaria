<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\RoutePoint;
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
     * Días con puntos GPS registrados, para un asesor puntual.
     */
    #[Renderless]
    public function getDiasDisponiblesAsesor(string $asesorId): array
    {
        return RoutePoint::where('user_id', $asesorId)
            ->selectRaw('DATE(timestamp) as fecha')
            ->groupByRaw('DATE(timestamp)')
            ->orderByDesc('fecha')
            ->limit(30)
            ->pluck('fecha')
            ->toArray();
    }

    /**
     * Días con puntos GPS registrados, entre todos los asesores.
     */
    #[Renderless]
    public function getDiasDisponiblesTodos(): array
    {
        return RoutePoint::selectRaw('DATE(timestamp) as fecha')
            ->groupByRaw('DATE(timestamp)')
            ->orderByDesc('fecha')
            ->limit(30)
            ->pluck('fecha')
            ->toArray();
    }

    /**
     * Puntos de la ruta de un asesor en una fecha concreta.
     */
    #[Renderless]
    public function getRutasAsesor(string $asesorId, string $fecha): array
    {
        return RoutePoint::where('user_id', $asesorId)
            ->whereDate('timestamp', $fecha)
            ->orderBy('timestamp')
            ->get(['id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp'])
            ->map(fn (RoutePoint $p) => $this->formatPunto($p))
            ->toArray();
    }

    /**
     * Puntos de la ruta de todos los asesores en una fecha concreta.
     * Una sola consulta para todos los asesores (evita N+1 por asesor/día).
     */
    #[Renderless]
    public function getRutasTodos(string $fecha): array
    {
        $puntosPorAsesor = RoutePoint::whereDate('timestamp', $fecha)
            ->orderBy('timestamp')
            ->get(['id', 'user_id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp'])
            ->groupBy('user_id');

        return $this->getAsesores()
            ->filter(fn (User $asesor) => $puntosPorAsesor->has($asesor->id))
            ->map(fn (User $asesor) => [
                'name'   => $asesor->name,
                'puntos' => $puntosPorAsesor->get($asesor->id)
                    ->map(fn (RoutePoint $p) => $this->formatPunto($p))
                    ->values()
                    ->toArray(),
            ])
            ->values()
            ->toArray();
    }

    private function formatPunto(RoutePoint $p): array
    {
        return [
            'id'        => $p->id,
            'lat'       => $p->lat,
            'lng'       => $p->lng,
            'precision' => $p->precision,
            'velocidad' => $p->velocidad,
            'hora'      => $p->timestamp->format('H:i:s'),
            'timestamp' => $p->timestamp->toIso8601String(),
        ];
    }
}
