<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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
    // Historial de cambios del expediente
    // ─────────────────────────────────────────────────────────────────────────
    public function seguimiento(Request $request): JsonResponse
    {
        $acreditado = $request->user();
        $expediente = $acreditado->expedientes()->latest()->first();

        if (! $expediente) {
            return response()->json(['data' => []]);
        }

        $seguimientos = $expediente->seguimientos()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($s) => [
                'tipo'        => $s->tipo,
                'descripcion' => $s->descripcion,
                'fecha'       => $s->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json(['data' => $seguimientos]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function expedientePayload($expediente): array
    {
        $etapaOrden = $expediente->etapa?->orden ?? 0;

        // Guía del paso actual para el acreditado
        $guia = match (true) {
            $etapaOrden === 1 => 'Tu expediente fue creado. Tu asesor revisará tus documentos.',
            $etapaOrden === 2 => 'Tus documentos están siendo revisados. Sube cualquier documento pendiente.',
            $etapaOrden === 3 => 'Tu trámite está en proceso ante el municipio (catastro y avalúo).',
            $etapaOrden === 4 => 'El avalúo de tu vivienda fue realizado. Espera confirmación de SOFOM.',
            $etapaOrden === 5 => 'Tu expediente está en notaría. Se está gestionando el certificado de libertad de gravamen.',
            $etapaOrden === 6 => 'Tu firma ante notario está próxima. Tu asesor te contactará para coordinarla.',
            $etapaOrden >= 7 => '¡Felicidades! Tu crédito fue aprobado. El pago llegará en los próximos días hábiles.',
            default           => 'Tu asesor está revisando tu caso.',
        };

        return [
            'id'          => $expediente->id,
            'folio'       => $expediente->folio,
            'estado'      => $expediente->estado,
            'etapa' => [
                'orden'  => $etapaOrden,
                'nombre' => $expediente->etapa?->nombre ?? 'Sin etapa',
                'total'  => $expediente->tipoTramite
                    ? \App\Models\EtapaTramite::where('tipo_tramite_id', $expediente->tipo_tramite_id)->count()
                    : 7,
            ],
            'tipo_tramite'   => $expediente->tipoTramite?->nombre,
            'fecha_apertura' => $expediente->fecha_apertura?->format('d/m/Y'),
            'fecha_firma'    => $expediente->fecha_firma?->format('d/m/Y'),
            'fecha_esperada_pago' => $expediente->fecha_esperada_pago?->format('d/m/Y'),
            'guia_paso_actual'   => $guia,
            'asesor' => $expediente->asesor ? [
                'name'     => $expediente->asesor->name,
                'telefono' => $expediente->asesor->telefono,
                'email'    => $expediente->asesor->email,
            ] : null,
            'documentos_pendientes' => $expediente->documentos()
                ->where('estado', 'pendiente')
                ->whereIn('seccion', ['acreditado'])
                ->count(),
        ];
    }
}
