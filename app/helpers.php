<?php

use App\Models\Configuracion;

if (! function_exists('setting')) {
    /**
     * Obtiene un valor de configuración del sitio.
     *
     * @param  string  $clave
     * @param  mixed   $default
     * @return mixed
     */
    function setting(string $clave, mixed $default = null): mixed
    {
        return Configuracion::get($clave, $default);
    }
}
