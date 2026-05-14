<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAsesorToExpedientes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->hasRole('asesor')) {
            $path = $request->path();

            // Si intenta acceder al dashboard (/admin o /admin/) → redirigir a expedientes
            if ($path === 'admin' || $path === 'admin/') {
                return redirect('/admin/expedientes');
            }
        }

        return $next($request);
    }
}
