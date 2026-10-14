<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpedienteController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/expediente
    // Retorna el expediente del acreditado con toda la info del proceso
    // ─────────────────────────────────────────────────────────────────────────
    public function show(Request $request): JsonResponse
    {
        $acreditado = $request->user();
        $acreditado->vincularExpedientesPendientes();

        $expediente = $acreditado->expedientes()
            ->with(['etapa', 'tipoTramite', 'asesor'])
            ->latest()
            ->first();

        if (! $expediente) {
            return response()->json([
                'data'    => null,
                'message' => 'Aún no tienes un expediente activo. Solicita una asesoría para iniciar tu trámite.',
            ]);
        }

        return response()->json([
            'data' => $this->expedientePayload($expediente),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/expediente/documentos-pendientes
    // Lista los documentos que el acreditado aún no ha entregado
    // ─────────────────────────────────────────────────────────────────────────
    public function documentosPendientes(Request $request): JsonResponse
    {
        $acreditado = $request->user();
        $acreditado->vincularExpedientesPendientes();
        $expediente = $acreditado->expedientes()->latest()->first();

        if (! $expediente) {
            return response()->json(['data' => []]);
        }

        $pendientes = $expediente->documentos()
            ->where('estado', 'pendiente')
            ->whereNull('ruta_archivo')
            ->whereIn('seccion', ['acreditado']) // solo los que son del acreditado
            ->get()
            ->map(fn ($d) => [
                'id'      => $d->id,
                'nombre'  => $d->nombre,
                'seccion' => $d->seccion,
            ]);

        return response()->json(['data' => $pendientes]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/expediente/seguimiento
    // Ya no hay historial de etapas: se deja la ruta (apps instaladas) con lista vacía.
    // ─────────────────────────────────────────────────────────────────────────
    public function seguimiento(): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function expedientePayload($expediente): array
    {
        // El trámite no se avanza por etapas. Está "en revisión" hasta que un administrador
        // valida TODOS los documentos; entonces pasa a "en proceso". Se conservan las llaves
        // de etapa/fechas para las versiones de la app que aún las leen.
        $enProceso   = $expediente->estaCompleto();
        $solicitados = $expediente->documentos()->solicitadosPendientes()->get();

        return [
            'id'          => $expediente->id,
            'folio'       => $expediente->folio,
            'estado'      => $enProceso ? 'en_proceso' : 'en_revision',
            'etapa' => [
                'orden'  => 1,
                'nombre' => $enProceso ? 'En proceso' : 'En revisión',
                'total'  => 1,
            ],
            'tipo_tramite'   => $expediente->tipoTramite?->nombre,
            'fecha_apertura' => $expediente->fecha_apertura?->format('d/m/Y'),
            'fecha_firma'    => null,
            'fecha_esperada_pago' => null,
            'documentos_solicitados' => $solicitados->map(fn ($d) => [
                'id'            => $d->id,
                'tipo'          => $d->tipo,
                'nota'          => $d->solicitud_nota,
                'solicitado_at' => $d->solicitado_at?->toIso8601String(),
            ])->values(),
            'guia_paso_actual'   => $solicitados->isNotEmpty()
                ? 'Necesitamos un documento adicional para continuar. Revisa la nota y súbelo desde Documentos.'
                : ($enProceso
                ? 'Tus documentos fueron validados y tu trámite está en proceso. No necesitas hacer nada más: solo espera, tu asesor te contactará cuando haya novedades.'
                : 'Tu expediente está en revisión. Tu asesor se pondrá en contacto contigo si necesita algo más.'),
            'asesor' => $expediente->asesor ? [
                'name'     => $expediente->asesor->name,
                'telefono' => $expediente->asesor->telefono,
                'email'    => $expediente->asesor->email,
            ] : null,
            // En proceso ya no se le pide nada: solo esperar
            'documentos_pendientes' => $enProceso ? 0 : $expediente->documentos()
                ->where('estado', 'pendiente')
                ->whereNull('ruta_archivo')
                ->whereIn('seccion', ['acreditado'])
                ->count(),
        ];
    }
}
