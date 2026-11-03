<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El gestor inicia sesión con el mismo /v1/auth/login que el asesor, pero no
 * debe usar la API del asesor (crear prospectos, expedientes, contratos…).
 * Solo se le permiten su sesión/perfil, el registro de su dispositivo para
 * notificaciones y sus propias rutas bajo /v1/gestor.
 */
class BloquearGestorEnApiAsesor
{
    private const PERMITIDAS = ['api/v1/auth/*', 'api/v1/dispositivos', 'api/v1/gestor', 'api/v1/gestor/*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasRole('gestor') && ! $request->is(...self::PERMITIDAS)) {
            return response()->json(['message' => 'Esta sección no está disponible para gestores.'], 403);
        }

        return $next($request);
    }
}
