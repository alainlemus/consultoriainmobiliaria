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

        if ($user && rtrim($request->path(), '/') === 'admin') {
            if ($user->hasRole('asesor')) {
                return redirect('/admin/dashboard-asesor');
            }

            // Cualquier rol que no sea super_admin ni admin va a expedientes
            if (! $user->hasRole('super_admin') && ! $user->hasRole('admin')) {
                return redirect('/admin/expedientes');
            }
        }

        return $next($request);
    }
}
