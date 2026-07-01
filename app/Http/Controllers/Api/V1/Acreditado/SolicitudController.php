<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmacionContacto;
use App\Mail\NuevoContactoAdmin;
use App\Models\AcreditadoSolicitud;
use App\Models\Contacto;
use App\Models\TipoTramite;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
    // El acreditado solicita una asesoría — crea un Contacto en el CRM,
    // registra la solicitud en el historial y envía emails en cola.
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

        // Verificar si ya tiene un expediente activo
        if ($acreditado->expedientes()->whereNotIn('estado', ['cerrado', 'cancelado'])->exists()) {
            return response()->json([
                'message' => 'Ya tienes un expediente activo. Contacta a tu asesor para continuar.',
            ], 422);
        }

        // Resolver nombre del servicio
        $tipo     = ! empty($data['tipo_tramite_id']) ? TipoTramite::find($data['tipo_tramite_id']) : null;
        $servicio = $tipo?->nombre;

        // Crear o actualizar el Contacto en el CRM
        // El ContactoObserver dispara: WhatsApp en cola + NuevoProspectoCreado a admins
        $contacto = Contacto::updateOrCreate(
            ['curp' => $acreditado->curp ?? null, 'email' => $acreditado->email],
            [
                'nombre'                => $acreditado->name,
                'telefono'              => $acreditado->telefono ?? '',
                'email'                 => $acreditado->email,
                'curp'                  => $acreditado->curp,
                'nss'                   => $acreditado->nss,
                'servicio'              => $servicio ?? 'FOVISSSTE',
                'mensaje'               => $data['mensaje'] ?? 'Solicitud desde la app del acreditado.',
                'origen'                => 'app_acreditado',
                'estado_prospecto'      => 'nuevo',
                'estado_uso_credito'    => $data['estado'] ?? null,
                'municipio_uso_credito' => $data['municipio'] ?? null,
            ]
        );

        // Vincular el Contacto al Acreditado (una sola vez)
        if (! $acreditado->contacto_id) {
            $acreditado->update(['contacto_id' => $contacto->id]);
        }

        // Registrar la solicitud en el historial
        AcreditadoSolicitud::create([
            'acreditado_id'   => $acreditado->id,
            'contacto_id'     => $contacto->id,
            'tipo_tramite_id' => $data['tipo_tramite_id'] ?? null,
            'servicio'        => $servicio,
            'mensaje'         => $data['mensaje'] ?? null,
            'municipio'       => $data['municipio'] ?? null,
            'estado'          => $data['estado'] ?? null,
            'estado_solicitud' => 'pendiente',
        ]);

        // ── Emails en cola ────────────────────────────────────────────────────
        // 1. Confirmación al acreditado
        if ($acreditado->email) {
            Mail::to($acreditado->email)
                ->queue(new ConfirmacionContacto($contacto));
        }

        // 2. Notificación a cada super_admin con email configurado
        User::role('super_admin')
            ->whereNotNull('email')
            ->get()
            ->each(fn (User $admin) => Mail::to($admin->email)
                ->queue(new NuevoContactoAdmin($contacto))
            );

        return response()->json([
            'message'      => 'Tu solicitud fue recibida. Un asesor te contactará pronto.',
            'contacto_id'  => $contacto->id,
        ], 201);
    }
}
