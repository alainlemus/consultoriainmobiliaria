<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpedienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Expediente::with([
            'contacto:id,nombre,email,telefono,estado_prospecto',
            'tipoTramite:id,nombre',
            'etapa:id,nombre',
        ]);

        if (! $user->hasRole('super_admin')) {
            $query->where('asesor_id', $user->id);
        }

        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
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

    public function show(Request $request, int $id): JsonResponse
    {
        $exp = $this->findForUser($request, $id);

        $exp->load([
            'contacto:id,nombre,email,telefono,estado_prospecto',
            'tipoTramite:id,nombre',
            'etapa:id,nombre',
            'documentos',
        ]);

        return response()->json(['data' => $exp]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contacto_id'      => ['required', 'exists:contactos,id'],
            'tipo_tramite_id'  => ['required', 'exists:tipo_tramites,id'],
            'etapa_tramite_id' => ['nullable', 'exists:etapa_tramites,id'],
            'estado'           => ['nullable', 'in:en_proceso,documentacion,autorizado,escrituracion,cerrado,cancelado'],
            'monto_credito'    => ['nullable', 'numeric', 'min:0'],
            'honorarios_monto' => ['nullable', 'numeric', 'min:0'],
            'notas_internas'   => ['nullable', 'string'],
        ]);

        $exp = Expediente::create([
            ...$data,
            'asesor_id'        => $request->user()->id,
            'estado'           => $data['estado'] ?? 'en_proceso',
            'etapa_tramite_id' => $data['etapa_tramite_id']
                ?? EtapaTramite::where('tipo_tramite_id', $data['tipo_tramite_id'])->orderBy('orden')->value('id'),
            // Si no se envía acreditado_nombre, se toma del contacto
            'acreditado_nombre' => $data['acreditado_nombre']
                ?? \App\Models\Contacto::find($data['contacto_id'])?->nombre,
        ]);

        return response()->json(['data' => $exp->load(['tipoTramite:id,nombre', 'contacto:id,nombre'])], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $exp = $this->findForUser($request, $id);

        $data = $request->validate([
            'estado'           => ['nullable', 'in:en_proceso,documentacion,autorizado,escrituracion,cerrado,cancelado'],
            'etapa_tramite_id' => ['nullable', 'exists:etapa_tramites,id'],
            'monto_credito'    => ['nullable', 'numeric', 'min:0'],
            'honorarios_monto' => ['nullable', 'numeric', 'min:0'],
            'notas_internas'   => ['nullable', 'string'],
        ]);

        $exp->update($data);

        return response()->json(['data' => $exp->fresh()]);
    }

    private function findForUser(Request $request, int $id): Expediente
    {
        $user = $request->user();
        $exp  = Expediente::findOrFail($id);

        if (! $user->hasRole('super_admin') && $exp->asesor_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este expediente.');
        }

        return $exp;
    }
}
