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

if (! function_exists('logo_path')) {
    /**
     * Ruta en disco del logo del sitio (el configurado en Ajustes o, si no hay, el de respaldo),
     * en una copia liviana (máx. 256 px) para incrustarlo en correos y PDFs sin pesar de más
     * ni depender de que la URL pública sea alcanzable.
     */
    function logo_path(): ?string
    {
        $original = null;
        $configurado = setting('logo');
        if ($configurado && is_file($ruta = storage_path('app/public/' . ltrim($configurado, '/')))) {
            $original = $ruta;
        } elseif (is_file($respaldo = public_path('images/logo-correo.png'))) {
            $original = $respaldo;
        }

        if (! $original) {
            return null;
        }

        // Copia reducida y cacheada (se regenera si cambia el logo)
        if (! function_exists('imagecreatefromstring')) {
            return $original;
        }

        $destino = storage_path('app/cache/logo-correo-' . md5($original . filemtime($original)) . '.png');
        if (is_file($destino)) {
            return $destino;
        }

        try {
            $img = @imagecreatefromstring((string) file_get_contents($original));
            if (! $img) {
                return $original;
            }
            if (imagesx($img) > 256) {
                $img = imagescale($img, 256, -1, IMG_BICUBIC);
            }
            imagesavealpha($img, true);
            @mkdir(dirname($destino), 0775, true);
            imagepng($img, $destino, 9);

            return $destino;
        } catch (\Throwable) {
            return $original;
        }
    }
}

if (! function_exists('email_logo_src')) {
    /**
     * `src` del logo para un correo. Al enviar, el logo viaja dentro del mensaje (cid:) y se ve
     * siempre, aunque la dirección del sitio no sea pública (local, staging) o el cliente de
     * correo bloquee imágenes remotas. Fuera del envío (vista previa) usa un enlace.
     */
    function email_logo_src(mixed $message = null): ?string
    {
        $ruta = logo_path();
        if (! $ruta) {
            return null;
        }

        if ($message instanceof \Illuminate\Mail\Message) {
            return $message->embed($ruta);
        }

        // Vista previa (sin envío): enlace público al logo configurado o al de respaldo
        $configurado = setting('logo');

        return $configurado && is_file(storage_path('app/public/' . ltrim($configurado, '/')))
            ? asset('storage/' . ltrim($configurado, '/'))
            : asset('images/logo-correo.png');
    }
}

if (! function_exists('logo_data_uri')) {
    /** Logo como data URI (para PDFs de DomPDF, que no cargan imágenes remotas). */
    function logo_data_uri(): ?string
    {
        $ruta = logo_path();
        if (! $ruta) {
            return null;
        }

        $mime = mime_content_type($ruta) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($ruta));
    }
}
