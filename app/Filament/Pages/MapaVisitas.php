<?php

namespace App\Filament\Pages;

use App\Models\Ubicacion;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MapaVisitas extends Page
{
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Mapa de Visitas';
    protected static ?string $title           = 'Mapa de Visitas y Alcance';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 9;
    protected static ?string $slug            = 'mapa-visitas';

    protected string $view = 'filament.pages.mapa-visitas';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('asesor'));
    }

    /** Asesores para el filtro — solo visible para super_admin */
    public function getAsesores(): Collection
    {
        return User::role('asesor')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Indica si el usuario actual es super_admin (usado en la blade) */
    public function esSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /** Ubicaciones filtradas por rol */
    public function getUbicacionesJson(): string
    {
        $query = Ubicacion::with(['contacto:id,nombre', 'user:id,name', 'fotos'])
            ->orderByDesc('visitado_en');

        if (! $this->esSuperAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $ubicaciones = $query->get()->map(fn (Ubicacion $u) => [
            'id'             => $u->id,
            'latitud'        => $u->latitud,
            'longitud'       => $u->longitud,
            'tipo'           => $u->tipo,
            'semaforo'       => $u->semaforo,
            'semaforo_notas' => $u->semaforo_notas,
            'nombre_lugar'   => $u->nombre_lugar,
            'notas'          => $u->notas,
            'visitado_en'    => $u->visitado_en?->format('d/m/Y H:i'),
            'contacto'       => $u->contacto?->nombre,
            'asesor'         => $u->user?->name,
            'asesor_id'      => $u->user_id,
            'fotos'          => $u->fotos->map(fn ($f) => [
                'id'  => $f->id,
                'url' => \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $f->id], now()->addMinutes(30)),
            ])->values(),
        ]);

        return json_encode($ubicaciones);
    }

    /** Stats filtradas por rol */
    public function getStats(): array
    {
        $base = $this->esSuperAdmin()
            ? Ubicacion::query()
            : Ubicacion::where('user_id', auth()->id());

        $total    = (clone $base)->count();
        $clientes = (clone $base)->where('tipo', 'visita_cliente')->count();
        $props    = (clone $base)->where('tipo', 'propiedad')->count();
        $escuelas = (clone $base)->where('tipo', 'escuela')->count();
        $asesores = $this->esSuperAdmin()
            ? Ubicacion::distinct('user_id')->count('user_id')
            : 1;

        return compact('total', 'clientes', 'props', 'escuelas', 'asesores');
    }
}
