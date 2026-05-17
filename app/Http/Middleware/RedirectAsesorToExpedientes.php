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
            $path = rtrim($request->path(), '/');

            // Cualquier intento de acceder al panel raíz → redirigir a su dashboard
            if ($path === 'admin') {
                return redirect('/admin/dashboard-asesor');
            }
        }

        return $next($request);
    }
}
