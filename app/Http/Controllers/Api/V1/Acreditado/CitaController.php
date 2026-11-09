<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Cobertura;
use App\Services\CitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Citas desde la app del acreditado: agenda con su asesor, ve las suyas y puede cancelar. */
class CitaController extends Controller
{
    public function __construct(private CitaService $service) {}

    public function oficinas(): JsonResponse
    {
        return response()->json(['data' => $this->service->oficinas()]);
    }

    public function disponibilidad(Request $request): JsonResponse
    {
        $d = $request->validate([
            'cobertura_id' => ['required', 'integer', 'exists:coberturas,id'],
            'fecha'        => ['required', 'date'],
            'tipo'         => ['nullable', 'in:oficina,domicilio'],
        ]);
        $oficina = Cobertura::conCitas()->findOrFail($d['cobertura_id']);

        return response()->json(['data' => $this->service->disponibilidad($oficina, Carbon::parse($d['fecha']), $d['tipo'] ?? 'oficina')]);
    }

    public function index(Request $request): JsonResponse
    {
        $citas = Cita::where('acreditado_id', $request->user()->id)->with(['cobertura', 'asesor'])->orderByDesc('inicia_at')->limit(50)->get();

        return response()->json(['data' => $citas->map(fn (Cita $c) => CitaService::payload($c))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $acreditado = $request->user();
        $d = $request->validate([
            'cobertura_id' => ['required', 'integer'],
            'tipo'         => ['required', 'in:oficina,domicilio'],
            'inicia_at'    => ['required', 'date'],
            'direccion'    => ['nullable', 'string', 'max:255'],
            'notas'        => ['nullable', 'string', 'max:500'],
        ]);

        $expediente = $acreditado->expedientes()->latest()->first();

        $cita = $this->service->solicitar([
            ...$d,
            'contacto_id'   => $acreditado->contacto_id,
            'expediente_id' => $expediente?->id,
            'acreditado_id' => $acreditado->id,
            'asesor_id'     => $acreditado->asesor_id ?? $expediente?->asesor_id,
            'nombre'        => $acreditado->name,
            'telefono'      => $acreditado->telefono,
        ], $acreditado);

        return response()->json(['message' => 'Cita solicitada. Te avisaremos cuando quede confirmada.', 'data' => CitaService::payload($cita)], 201);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $cita = Cita::where('acreditado_id', $request->user()->id)->findOrFail($id);
        $d = $request->validate(['motivo' => ['nullable', 'string', 'max:255']]);

        return response()->json(['data' => CitaService::payload($this->service->cancelar($cita, $d['motivo'] ?? null, $request->user()))]);
    }
}
