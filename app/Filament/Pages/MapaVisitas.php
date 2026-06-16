<?php

namespace App\Filament\Pages;

use App\Models\Anuncio;
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

    public function getAsesores(): Collection
    {
        return User::role('asesor')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function esSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

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

    /**
     * Anuncios para la capa de propaganda en el mapa.
     * Todos los roles ven todos los anuncios activos (para evitar duplicar territorios).
     * El super_admin también ve los retirados.
     */
    public function getAnunciosJson(): string
    {
        $query = Anuncio::with(['user:id,name', 'fotos'])
            ->orderByDesc('colocado_en');

        if (! $this->esSuperAdmin()) {
            $query->where('estado', 'activo');
        }

        $anuncios = $query->get()->map(fn (Anuncio $a) => [
            'id'          => $a->id,
            'latitud'     => $a->latitud,
            'longitud'    => $a->longitud,
            'tipo'        => $a->tipo,
            'estado'      => $a->estado,
            'descripcion' => $a->descripcion,
            'direccion'   => $a->direccion,
            'colonia'     => $a->colonia,
            'municipio'   => $a->municipio,
            'estado_geo'  => $a->estado_geo,
            'colocado_en' => $a->colocado_en?->format('d/m/Y'),
            'asesor'      => $a->user?->name,
            'asesor_id'   => $a->user_id,
            'fotos'       => $a->fotos->map(fn ($f) => [
                'id'  => $f->id,
                'url' => \URL::signedRoute('api.anuncio.foto', ['fotoId' => $f->id], now()->addMinutes(30)),
            ])->values(),
        ]);

        return json_encode($anuncios);
    }

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

        // Anuncios activos (todos los asesores ven el total del equipo)
        $anuncios = Anuncio::where('estado', 'activo')->count();

        return compact('total', 'clientes', 'props', 'escuelas', 'asesores', 'anuncios');
    }
}

