<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Bandeja de notificaciones de la app, para cualquier usuario (asesor, gestor,
 * super admin) y para el acreditado. Solo lectura: se marcan como leídas,
 * nunca se borran.
 */
class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $pagina = $user->notifications()->latest()->paginate(30);

        return response()->json([
            'data' => collect($pagina->items())->map(fn (DatabaseNotification $n) => $this->serializar($n))->values(),
            'meta' => [
                'pagina'    => $pagina->currentPage(),
                'ultima'    => $pagina->lastPage(),
                'total'     => $pagina->total(),
                'no_leidas' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function noLeidas(Request $request): JsonResponse
    {
        return response()->json(['no_leidas' => $request->user()->unreadNotifications()->count()]);
    }

    public function leer(Request $request, string $id): JsonResponse
    {
        $n = $request->user()->notifications()->findOrFail($id);
        if (! $n->read_at) $n->markAsRead();

        return response()->json(['data' => $this->serializar($n->fresh())]);
    }

    public function leerTodas(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['no_leidas' => 0]);
    }

    private function serializar(DatabaseNotification $n): array
    {
        $d = $n->data ?? [];

        return [
            'id'     => $n->id,
            'titulo' => $this->texto($d['title'] ?? ''),
            'cuerpo' => $this->texto($d['body'] ?? ''),
            'tipo'   => class_basename($n->type),
            'color'  => $d['color'] ?? 'info',
            'leida'  => (bool) $n->read_at,
            'fecha'  => $n->created_at?->toIso8601String(),
            'ruta'   => $this->ruta($d),
        ];
    }

    /** Quita el markdown (**negritas**) que usa el panel. */
    private function texto(string $s): string
    {
        return trim(preg_replace('/\*\*(.*?)\*\*/', '$1', $s));
    }

    /**
     * A qué pantalla de la app lleva. Las notificaciones nuevas traen 'app';
     * las anteriores se deducen de la liga del panel que llevan en su acción.
     */
    private function ruta(array $d): ?array
    {
        if (! empty($d['app']) && is_array($d['app'])) return $d['app'];

        $url = $d['actions'][0]['url'] ?? '';
        if (preg_match('#/admin/expedientes/(\d+)#', $url, $m)) return ['screen' => 'expedientes', 'id' => $m[1]];
        if (preg_match('#/admin/contactos/(\d+)#', $url, $m))   return ['screen' => 'prospectos', 'id' => $m[1]];
        if (str_contains($url, '/admin/acreditados'))           return ['screen' => 'registros_qr'];
        if (preg_match('#/admin/gestor#', $url))                  return ['screen' => 'gestor_encargo'];

        return null;
    }
}
