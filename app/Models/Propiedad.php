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
