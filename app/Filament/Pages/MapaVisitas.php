<?php

namespace App\Filament\Pages;

use App\Models\Ubicacion;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MapaVisitas extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Mapa de Visitas';
    protected static ?string $title           = 'Mapa de Visitas y Alcance';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 9;
    protected static ?string $slug            = 'mapa-visitas';

    protected static string $view = 'filament.pages.mapa-visitas';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    /** Todos los asesores para el filtro */
    public function getAsesores(): Collection
    {
        return User::role('asesor')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Ubicaciones con relaciones, listas para JSON */
    public function getUbicacionesJson(): string
    {
        $ubicaciones = Ubicacion::with(['contacto:id,nombre', 'user:id,name', 'fotos'])
            ->orderByDesc('visitado_en')
            ->get()
            ->map(fn (Ubicacion $u) => [
                'id'          => $u->id,
                'latitud'     => $u->latitud,
                'longitud'    => $u->longitud,
                'tipo'        => $u->tipo,
                'notas'       => $u->notas,
                'visitado_en' => $u->visitado_en?->format('d/m/Y H:i'),
                'contacto'    => $u->contacto?->nombre,
                'asesor'      => $u->user?->name,
                'asesor_id'   => $u->user_id,
                'fotos'       => $u->fotos->map(fn ($f) => [
                    'id'  => $f->id,
                    // URL firmada válida 30 min — no requiere sesión, el browser la carga directo
                    'url' => \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $f->id], now()->addMinutes(30)),
                ])->values(),
            ]);

        return json_encode($ubicaciones);
    }

    /** Stats rápidas para las tarjetas */
    public function getStats(): array
    {
        $total    = Ubicacion::count();
        $clientes = Ubicacion::where('tipo', 'visita_cliente')->count();
        $props    = Ubicacion::where('tipo', 'propiedad')->count();
        $asesores = Ubicacion::distinct('user_id')->count('user_id');

        return compact('total', 'clientes', 'props', 'asesores');
    }
}
