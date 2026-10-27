<?php

namespace App\Http\Middleware;

use App\Filament\Resources\ContratoGeneradoResource;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAsesorToExpedientes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && rtrim($request->path(), '/') === 'admin') {
            $inicio = static::inicio($user);

            if ($inicio !== '/admin') {
                return redirect($inicio);
            }
        }

        return $next($request);
    }

    /** Página donde aterriza cada usuario según su rol y permisos. */
    public static function inicio($user): string
    {
        if ($user?->hasRole('asesor')) {
            return '/admin/dashboard-asesor';
        }

        if ($user?->hasRole('Revisor de Rutas')) {
            return '/admin/mapa-visitas';
        }

        if (! $user || $user->hasRole('super_admin') || $user->hasRole('admin')) {
            return '/admin';
        }

        // Otros roles (capturista, Contratos, etc.): expedientes si puede verlos,
        // si no, el primer recurso del panel al que tenga acceso.
        if ($user->can('ViewAny:Expediente')) {
            return '/admin/expedientes';
        }

        if ($user->can('ViewAny:ContratoGenerado')) {
            return ContratoGeneradoResource::getUrl('index');
        }

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            if ($resource::canViewAny() && $resource::shouldRegisterNavigation()) {
                return $resource::getUrl('index');
            }
        }

        return '/admin/expedientes';
    }
}
