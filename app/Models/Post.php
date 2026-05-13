<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    // Estados posibles
    const ESTADO_BORRADOR   = 'borrador';
    const ESTADO_PROGRAMADO = 'programado';
    const ESTADO_PUBLICADO  = 'publicado';

    protected $fillable = [
        'titulo', 'slug', 'categoria', 'imagen',
        'resumen', 'contenido', 'publicado', 'published_at', 'estado',
    ];

    protected $casts = [
        'publicado'    => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->titulo);
            }
        });

        // Sincronizar `publicado` al guardar según el estado
        static::saving(function ($post) {
            $post->publicado = ($post->estado === self::ESTADO_PUBLICADO);
        });
    }

    /** Artículos visibles en el frontend */
    public function scopePublished($query)
    {
        return $query->where('estado', self::ESTADO_PUBLICADO);
    }

    /** Etiqueta legible del estado */
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PUBLICADO  => 'Publicado',
            self::ESTADO_PROGRAMADO => 'Programado',
            default                 => 'Borrador',
        };
    }
}
