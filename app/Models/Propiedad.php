<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Propiedad extends Model
{
    protected $table = 'propiedades';

    protected $fillable = [
        'titulo', 'slug', 'tipo', 'descripcion', 'precio',
        'estado', 'municipio', 'colonia', 'direccion',
        'latitud', 'longitud', 'mapa_iframe',
        'recamaras', 'banos', 'metros_construccion', 'metros_terreno',
        'acepta_infonavit', 'acepta_fovissste',
        'imagenes', 'estatus', 'destacada',
    ];

    public const CACHE_HAY_EN_VENTA = 'propiedades:hay_en_venta';

    /** ¿Hay alguna propiedad visible en el sitio público (en venta)? */
    public static function hayEnVenta(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember(self::CACHE_HAY_EN_VENTA, 300, fn () => static::where('estatus', 'en_venta')->exists());
    }

    protected $casts = [
        'imagenes'         => 'array',
        'acepta_infonavit' => 'boolean',
        'acepta_fovissste' => 'boolean',
        'destacada'        => 'boolean',
        'precio'           => 'decimal:2',
        'latitud'          => 'decimal:7',
        'longitud'         => 'decimal:7',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // El menú público muestra "Propiedades" solo si hay alguna en venta: se recalcula al cambiar
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget(self::CACHE_HAY_EN_VENTA));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget(self::CACHE_HAY_EN_VENTA));

        static::creating(function ($propiedad) {
            if (empty($propiedad->slug)) {
                $propiedad->slug = Str::slug($propiedad->titulo);
            }
        });
    }

    public function scopeDisponibles($query)
    {
        return $query->where('estatus', 'disponible');
    }

    public function scopeDestacadas($query)
    {
        return $query->where('destacada', true);
    }

    public function getImagenPrincipalAttribute(): ?string
    {
        return isset($this->imagenes[0]) ? $this->imagenes[0] : null;
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return $this->precio
            ? '$' . number_format($this->precio, 0, '.', ',')
            : 'Consultar precio';
    }

    public static function tipos(): array
    {
        return [
            'Casa'         => 'Casa',
            'Departamento' => 'Departamento',
            'Terreno'      => 'Terreno',
            'Local'        => 'Local comercial',
            'Bodega'       => 'Bodega',
            'Oficina'      => 'Oficina',
            'Rancho'       => 'Rancho / Finca',
        ];
    }

    public static function estatuses(): array
    {
        return [
            'en_venta' => 'En venta',
            'pausada'  => 'Pausada',
            'vendida'  => 'Vendida',
        ];
    }
}
