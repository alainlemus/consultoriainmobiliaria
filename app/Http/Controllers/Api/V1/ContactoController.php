<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactoController extends Controller
{
    /**
     * GET /api/v1/contactos
     * Asesor: solo sus propios contactos. super_admin: todos.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Contacto::query()->with('asesor:id,name');

        if (! $user->hasRole('super_admin')) {
            $query->where('asesor_id', $user->id);
        }

        if ($q = $request->input('q')) {
            $query->where(function ($b) use ($q) {
                $b->where('nombre',           'like', "%{$q}%")
                  ->orWhere('email',           'like', "%{$q}%")
                  ->orWhere('telefono',        'like', "%{$q}%");
            });
        }

        if ($estado = $request->input('estado')) {
            $query->where('estado_prospecto', $estado);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $result  = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'total'        => $result->total(),
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
                'per_page'     => $result->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/contactos
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'                  => ['required', 'string', 'max:200'],
            'telefono'                => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:150'],
            'servicio'                => ['nullable', 'in:FOVISSSTE,INFONAVIT'],
            'estado_prospecto'        => ['nullable', 'in:nuevo,contactado,precalificado,en_tramite,cerrado,no_interesado'],
            'notas'                   => ['nullable', 'string'],
            'origen'                  => ['nullable', 'string', 'max:80'],
            'tipo_credito_interes'    => ['nullable', 'string', 'max:80'],
            'monto_credito_estimado'  => ['nullable', 'numeric', 'min:0'],
            'salario_mensual'         => ['nullable', 'numeric', 'min:0'],
            'curp'                    => ['nullable', 'string', 'max:18'],
        ]);

        $contacto = Contacto::create([
            ...$data,
            'asesor_id'        => $request->user()->id,
            'estado_prospecto' => $data['estado_prospecto'] ?? 'nuevo',
            'origen'           => 'app_movil',
        ]);

        return response()->json(['data' => $contacto], 201);
    }

    /**
     * GET /api/v1/contactos/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $contacto = $this->findForUser($request, $id);

        return response()->json(['data' => $contacto]);
    }

    /**
     * PUT /api/v1/contactos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contacto = $this->findForUser($request, $id);

        $data = $request->validate([
            'nombre'                  => ['sometimes', 'string', 'max:200'],
            'telefono'                => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:150'],
            'servicio'                => ['nullable', 'in:FOVISSSTE,INFONAVIT'],
            'estado_prospecto'        => ['nullable', 'in:nuevo,contactado,precalificado,en_tramite,cerrado,no_interesado'],
            'notas'                   => ['nullable', 'string'],
            'monto_credito_estimado'  => ['nullable', 'numeric', 'min:0'],
            'salario_mensual'         => ['nullable', 'numeric', 'min:0'],
        ]);

        $contacto->update($data);

        return response()->json(['data' => $contacto->fresh()]);
    }

    // ── Helper privado ────────────────────────────────────────────────────────

    private function findForUser(Request $request, int $id): Contacto
    {
        $user = $request->user();

        $contacto = Contacto::with(['asesor:id,name'])->findOrFail($id);

        if (! $user->hasRole('super_admin') && $contacto->asesor_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este prospecto.');
        }

        return $contacto;
    }
}
