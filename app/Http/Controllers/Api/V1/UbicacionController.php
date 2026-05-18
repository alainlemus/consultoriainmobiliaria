<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    /**
     * POST /api/v1/ubicaciones
     * Registra una visita GPS del asesor.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contacto_id' => ['nullable', 'exists:contactos,id'],
            'latitud'     => ['required', 'numeric', 'between:-90,90'],
            'longitud'    => ['required', 'numeric', 'between:-180,180'],
            'tipo'        => ['nullable', 'in:visita_cliente,propiedad'],
            'notas'       => ['nullable', 'string'],
            'visitado_en' => ['nullable', 'date'],
        ]);

        $ubicacion = Ubicacion::create([
            ...$data,
            'user_id'    => $request->user()->id,
            'tipo'       => $data['tipo']       ?? 'visita_cliente',
            'visitado_en'=> $data['visitado_en'] ?? now(),
        ]);

        return response()->json(['data' => $ubicacion], 201);
    }

    /**
     * GET /api/v1/ubicaciones/mapa
     * Devuelve puntos para el mapa (prospectos no cerrados del asesor).
     */
    public function mapa(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Ubicacion::with('contacto:id,nombre,apellido_paterno,estado_prospecto')
            ->select(['id', 'contacto_id', 'latitud', 'longitud', 'tipo', 'notas', 'visitado_en']);

        if (! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        }

        // Últimas 500 visitas
        $puntos = $query->latest('visitado_en')->limit(500)->get();

        return response()->json(['data' => $puntos]);
    }
}
