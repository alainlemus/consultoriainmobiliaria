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
        // El trámite ya no se avanza por etapas: para el acreditado el expediente
        // siempre está "en revisión". Se conservan las llaves de etapa/fechas para
        // las versiones de la app que aún las leen.
        return [
            'id'          => $expediente->id,
            'folio'       => $expediente->folio,
            'estado'      => 'en_revision',
            'etapa' => [
                'orden'  => 1,
                'nombre' => 'En revisión',
                'total'  => 1,
            ],
            'tipo_tramite'   => $expediente->tipoTramite?->nombre,
            'fecha_apertura' => $expediente->fecha_apertura?->format('d/m/Y'),
            'fecha_firma'    => null,
            'fecha_esperada_pago' => null,
            'guia_paso_actual'   => 'Tu expediente está en revisión. Tu asesor se pondrá en contacto contigo si necesita algo más.',
            'asesor' => $expediente->asesor ? [
                'name'     => $expediente->asesor->name,
                'telefono' => $expediente->asesor->telefono,
                'email'    => $expediente->asesor->email,
            ] : null,
            'documentos_pendientes' => $expediente->documentos()
                ->where('estado', 'pendiente')
                ->whereNull('ruta_archivo')
                ->whereIn('seccion', ['acreditado'])
                ->count(),
        ];
    }
}
