<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * www.dominio → dominio con 301.
 *
 * El dominio canónico es el de APP_URL (sin www). Si alguien entra con www,
 * se le manda a la misma ruta sin el prefijo para que Google no vea dos
 * copias del sitio.
 */
class RedirigirSinWww
{
    public function handle(Request $request, Closure $next): Response
    {
        $host         = $request->getHost();
        $hostCanonico = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (
            str_starts_with($host, 'www.')
            && $hostCanonico
            && ! str_starts_with($hostCanonico, 'www.')
        ) {
            $destino = $request->getScheme() . '://' . substr($host, 4) . $request->getRequestUri();

            return redirect()->to($destino, 301);
        }

        return $next($request);
    }
}
