<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    protected $table = 'configuraciones';
    protected $fillable = ['clave', 'valor'];

    /**
     * Obtiene el valor de una clave de configuración.
     */
    public static function get(string $clave, mixed $default = null): mixed
    {
        return Cache::rememberForever("config_{$clave}", function () use ($clave, $default) {
            $row = static::where('clave', $clave)->first();
            return $row ? $row->valor : $default;
        });
    }

    /**
     * Guarda o actualiza una clave de configuración e invalida el caché.
     */
    public static function set(string $clave, mixed $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        Cache::forget("config_{$clave}");
    }
}
