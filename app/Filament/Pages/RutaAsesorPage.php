<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\RoutePoint;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

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
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function getAsesores(): Collection
    {
        return User::role('asesor')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getRutasJson(string $asesorId, string $fecha): string
    {
        $points = RoutePoint::where('user_id', $asesorId)
            ->whereDate('timestamp', $fecha)
            ->orderBy('timestamp')
            ->get(['id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp']);

        $formatted = $points->map(fn (RoutePoint $p) => [
            'id'        => $p->id,
            'lat'       => $p->lat,
            'lng'       => $p->lng,
            'precision' => $p->precision,
            'velocidad' => $p->velocidad,
            'hora'      => $p->timestamp->format('H:i:s'),
            'timestamp' => $p->timestamp->toIso8601String(),
        ]);

        return json_encode($formatted);
    }

    public function getDiasDisponibles(string $asesorId): array
    {
        return RoutePoint::where('user_id', $asesorId)
            ->selectRaw('DATE(timestamp) as fecha')
            ->groupByRaw('DATE(timestamp)')
            ->orderByDesc('fecha')
            ->limit(14)
            ->pluck('fecha')
            ->toArray();
    }
}
