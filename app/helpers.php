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

if (! function_exists('setting_email_list')) {
    /**
     * Obtiene un valor de configuración que puede contener varios correos
     * separados por coma (ej. "correo_contacto") como un array limpio.
     *
     * @return array<int, string>
     */
    function setting_email_list(string $clave): array
    {
        return collect(explode(',', (string) setting($clave)))
            ->map(fn ($correo) => trim($correo))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
