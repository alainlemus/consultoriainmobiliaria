<?php

namespace App\Filament\Pages;

use App\Models\Anuncio;
use App\Models\Ubicacion;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MapaVisitas extends Page
{
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Mapa de Visitas';
    protected static ?string $title           = 'Mapa de Visitas y Alcance';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 9;
    protected static ?string $slug            = 'mapa-visitas';

    protected string $view = 'filament.pages.mapa-visitas';

    /**
     * Clave de versión de caché. Se incrementa cada vez que cambia una
     * ubicación, anuncio o sus fotos (ver MapaVisitasCacheObserver),
     * invalidando así todo lo que esta página tiene cacheado.
     */
    public const CACHE_VERSION_KEY = 'mapa_visitas:cache_version';

    // Colchón de seguridad por si algún cambio no dispara el observer correspondiente.
    protected const CACHE_TTL = 300;

    public static function bumpCache(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, ((int) Cache::get(self::CACHE_VERSION_KEY, 1)) + 1);
    }

    protected function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

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
        $scope = $this->esSuperAdmin() ? 'all' : 'user_' . auth()->id();
        $key   = sprintf('mapa_visitas:v%d:ubicaciones:%s', $this->cacheVersion(), $scope);

        return Cache::remember($key, self::CACHE_TTL, function () {
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
        });
    }

    /**
     * Anuncios para la capa de propaganda en el mapa.
     * Todos los roles ven todos los anuncios activos (para evitar duplicar territorios).
     * El super_admin también ve los retirados.
     */
    public function getAnunciosJson(): string
    {
        $scope = $this->esSuperAdmin() ? 'all' : 'activos';
        $key   = sprintf('mapa_visitas:v%d:anuncios:%s', $this->cacheVersion(), $scope);

        return Cache::remember($key, self::CACHE_TTL, function () {
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
        });
    }

    public function getStats(): array
    {
        $scope = $this->esSuperAdmin() ? 'all' : 'user_' . auth()->id();
        $key   = sprintf('mapa_visitas:v%d:stats:%s', $this->cacheVersion(), $scope);

        return Cache::remember($key, self::CACHE_TTL, function () {
            $base = $this->esSuperAdmin()
                ? Ubicacion::query()
                : Ubicacion::where('user_id', auth()->id());

            // Un solo query con conteo condicional en vez de 4 COUNT(*) separados.
            $counts = (clone $base)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN tipo = 'visita_cliente' THEN 1 ELSE 0 END) as clientes,
                    SUM(CASE WHEN tipo = 'propiedad' THEN 1 ELSE 0 END) as props,
                    SUM(CASE WHEN tipo = 'escuela' THEN 1 ELSE 0 END) as escuelas
                ")
                ->first();

            $asesores = $this->esSuperAdmin()
                ? Ubicacion::distinct('user_id')->count('user_id')
                : 1;

            // Anuncios activos (todos los asesores ven el total del equipo)
            $anuncios = Anuncio::where('estado', 'activo')->count();

            return [
                'total'    => (int) $counts->total,
                'clientes' => (int) $counts->clientes,
                'props'    => (int) $counts->props,
                'escuelas' => (int) $counts->escuelas,
                'asesores' => $asesores,
                'anuncios' => $anuncios,
            ];
        });
    }
}

