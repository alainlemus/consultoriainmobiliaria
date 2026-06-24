<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use App\Models\TipoTramite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolicitudController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/servicios
    // Lista los tipos de trámite disponibles para que el acreditado elija
    // ─────────────────────────────────────────────────────────────────────────
    public function servicios(): JsonResponse
    {
        $servicios = TipoTramite::where('activo', true)
            ->orderBy('orden')
            ->get()
            ->map(fn ($t) => [
                'id'     => $t->id,
                'nombre' => $t->nombre,
            ]);

        return response()->json(['data' => $servicios]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/solicitudes
    // El acreditado solicita una asesoría — crea un Contacto en el CRM
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $acreditado = $request->user();

        $data = $request->validate([
            'tipo_tramite_id' => ['nullable', 'integer', 'exists:tipo_tramites,id'],
            'mensaje'         => ['nullable', 'string', 'max:1000'],
            'municipio'       => ['nullable', 'string', 'max:100'],
            'estado'          => ['nullable', 'string', 'max:100'],
        ]);

        // Verificar si ya tiene un contacto o expediente activo
        if ($acreditado->expedientes()->whereNotIn('estado', ['cerrado', 'cancelado'])->exists()) {
            return response()->json([
                'message' => 'Ya tienes un expediente activo. Contacta a tu asesor para continuar.',
            ], 422);
        }

        // Obtener el nombre del servicio solicitado
        $servicio = null;
        if (! empty($data['tipo_tramite_id'])) {
            $tipo = TipoTramite::find($data['tipo_tramite_id']);
            $servicio = $tipo?->nombre;
        }

        // Crear o actualizar el contacto en el CRM
        $contacto = Contacto::updateOrCreate(
            ['curp' => $acreditado->curp ?? null, 'email' => $acreditado->email],
            [
                'nombre'          => $acreditado->name,
                'telefono'        => $acreditado->telefono ?? '',
                'email'           => $acreditado->email,
                'curp'            => $acreditado->curp,
                'nss'             => $acreditado->nss,
                'servicio'        => $servicio ?? 'FOVISSSTE',
                'mensaje'         => $data['mensaje'] ?? 'Solicitud desde la app del acreditado.',
                'origen'          => 'app_acreditado',
                'estado_prospecto'=> 'nuevo',
                'estado_uso_credito'  => $data['estado'] ?? null,
                'municipio_uso_credito' => $data['municipio'] ?? null,
            ]
        );

        // Vincular el contacto al acreditado
        if (! $acreditado->contacto_id) {
            $acreditado->update(['contacto_id' => $contacto->id]);
        }

        // Notificar a todos los super_admin de la nueva solicitud
        \App\Models\User::role('super_admin')->get()->each(
            fn ($admin) => $admin->notify(new \App\Notifications\NuevoMensajeContacto($contacto))
        );

        return response()->json([
            'message'  => 'Tu solicitud fue recibida. Un asesor te contactará pronto.',
            'contacto_id' => $contacto->id,
        ], 201);
    }
}
