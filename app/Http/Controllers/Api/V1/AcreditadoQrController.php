<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Acreditado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /v1/acreditados-qr — cuentas de la app ligadas a este asesor por su
 * código QR, con en qué paso van: sin solicitud, con solicitud o con expediente.
 * El asesor ve las suyas; el super_admin todas (o filtra por asesor_id).
 */
class AcreditadoQrController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Acreditado::query()
            ->with(['codigoQr:id,codigo', 'contacto:id,estado_prospecto,contactado_at', 'asesor:id,name'])
            ->withCount('expedientes')
            ->whereNotNull('asesor_id')
            ->where('activo', true);

        if (! $user->hasRole('super_admin')) {
            $query->where('asesor_id', $user->id);
        } elseif ($asesorId = $request->input('asesor_id')) {
            $query->where('asesor_id', (int) $asesorId);
        }

        $items = $query->latest()->limit(200)->get()->map(function (Acreditado $a) {
            $paso = $a->expedientes_count > 0 ? 'expediente' : ($a->contacto_id ? 'solicitud' : 'sin_solicitud');

            return [
                'id'               => $a->id,
                'nombre'           => $a->name,
                'telefono'         => $a->telefono,
                'email'            => $a->email,
                'codigo'           => $a->codigoQr?->codigo,
                'asesor'           => $a->asesor?->name,
                'paso'             => $paso,
                'estado_prospecto' => $a->contacto?->estado_prospecto,
                'contacto_id'      => $a->contacto_id,
                'registrado_en'    => $a->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'total'          => $items->count(),
                'sin_solicitud'  => $items->where('paso', 'sin_solicitud')->count(),
                'con_solicitud'  => $items->where('paso', 'solicitud')->count(),
                'con_expediente' => $items->where('paso', 'expediente')->count(),
            ],
        ]);
    }
}
