<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class ChatbotPaso extends Model
{
    protected $fillable = [
        'clave', 'tipo', 'etiqueta', 'mensaje',
        'opciones', 'siguiente_paso', 'activo',
        'requerido', 'orden',
    ];

    protected $casts = [
        'opciones'  => 'array',
        'activo'    => 'boolean',
        'requerido' => 'boolean',
    ];

    /**
     * Pasos activos ordenados, cacheados 5 min.
     */
    public static function flujoActivo(): Collection
    {
        return cache()->remember('chatbot_pasos_activos', 300, function () {
            return static::where('activo', true)
                ->orderBy('orden')
                ->get();
        });
    }

    /**
     * Invalida el caché al guardar o eliminar.
     */
    protected static function booted(): void
    {
        static::saved(fn ()   => cache()->forget('chatbot_pasos_activos'));
        static::deleted(fn () => cache()->forget('chatbot_pasos_activos'));
    }

    public static function porClave(string $clave): ?self
    {
        return static::flujoActivo()->firstWhere('clave', $clave);
    }
}
