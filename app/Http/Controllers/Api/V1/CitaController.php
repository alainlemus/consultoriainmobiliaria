<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Cobertura;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Services\CitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Citas desde la app del staff. El asesor agenda para sus prospectos o
 * expedientes y ve las suyas; el super admin ve todas (su agenda) y las
 * confirma, reagenda, cancela o marca como realizadas.
 */
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
        $user  = $request->user();
        $desde = $request->filled('desde') ? Carbon::parse($request->input('desde'), CitaService::TZ)->startOfDay()->utc() : now()->subDays(7);
        $hasta = $request->filled('hasta') ? Carbon::parse($request->input('hasta'), CitaService::TZ)->endOfDay()->utc()   : now()->addDays(60);

        $query = Cita::query()->with(['cobertura', 'asesor', 'expediente'])
            ->whereBetween('inicia_at', [$desde, $hasta])
            ->orderBy('inicia_at');

        if (! $user->hasRole('super_admin')) {
            $query->where('asesor_id', $user->id);
        } elseif ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $citas = $query->get();

        return response()->json([
            'data' => $citas->map(fn (Cita $c) => CitaService::payload($c))->values(),
            'meta' => [
                'total'       => $citas->count(),
                'solicitadas' => $citas->where('estado', Cita::SOLICITADA)->count(),
                'hoy'         => $citas->filter(fn ($c) => $c->estaActiva() && $c->inicia_at->setTimezone(CitaService::TZ)->isToday())->count(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => CitaService::payload($this->cita($request, $id))]);
    }

    /** El asesor agenda para un prospecto o expediente suyo (el super admin para cualquiera). */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $d = $request->validate([
            'cobertura_id'  => ['required', 'integer'],
            'tipo'          => ['required', 'in:oficina,domicilio'],
            'inicia_at'     => ['required', 'date'],
            'contacto_id'   => ['nullable', 'integer', 'exists:contactos,id'],
            'expediente_id' => ['nullable', 'integer', 'exists:expedientes,id'],
            'direccion'     => ['nullable', 'string', 'max:255'],
            'notas'         => ['nullable', 'string', 'max:500'],
        ]);

        $contacto   = ! empty($d['contacto_id'])   ? Contacto::findOrFail($d['contacto_id'])     : null;
        $expediente = ! empty($d['expediente_id']) ? Expediente::findOrFail($d['expediente_id']) : null;
        abort_unless($contacto || $expediente, 422, 'Indica el prospecto o el expediente de la cita.');

        if (! $user->hasRole('super_admin')) {
            abort_if($contacto && (int) $contacto->asesor_id !== $user->id, 403);
            abort_if($expediente && (int) $expediente->asesor_id !== $user->id, 403);
        }

        $cita = $this->service->solicitar([
            ...$d,
            'contacto_id'   => $contacto?->id ?? $expediente?->contacto_id,
            'expediente_id' => $expediente?->id,
            'acreditado_id' => $expediente?->acreditado_id ?? Contacto::find($contacto?->id)?->acreditado?->id ?? null,
            'asesor_id'     => $expediente?->asesor_id ?? $contacto?->asesor_id ?? ($user->hasRole('asesor') ? $user->id : null),
            'nombre'        => $contacto?->nombre ?? $expediente?->acreditado_nombre ?? 'Cliente',
            'telefono'      => $contacto?->telefono ?? $expediente?->acreditado_telefono,
        ], $user);

        return response()->json(['message' => 'Cita solicitada. La administración la confirmará.', 'data' => CitaService::payload($cita)], 201);
    }

    public function confirmar(Request $request, int $id): JsonResponse
    {
        $this->soloAdmin($request);
        return response()->json(['data' => CitaService::payload($this->service->confirmar(Cita::findOrFail($id), $request->user()))]);
    }

    public function reagendar(Request $request, int $id): JsonResponse
    {
        $this->soloAdmin($request);
        $d = $request->validate(['inicia_at' => ['required', 'date']]);
        return response()->json(['data' => CitaService::payload($this->service->reagendar(Cita::findOrFail($id), $d['inicia_at'], $request->user()))]);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $cita = $this->cita($request, $id);
        $d = $request->validate(['motivo' => ['nullable', 'string', 'max:255']]);
        return response()->json(['data' => CitaService::payload($this->service->cancelar($cita, $d['motivo'] ?? null, $request->user()))]);
    }

    public function realizada(Request $request, int $id): JsonResponse
    {
        $this->soloAdmin($request);
        $d = $request->validate(['asistio' => ['required', 'boolean']]);
        return response()->json(['data' => CitaService::payload($this->service->marcar(Cita::findOrFail($id), (bool) $d['asistio']))]);
    }

    private function cita(Request $request, int $id): Cita
    {
        $cita = Cita::findOrFail($id);
        $user = $request->user();
        abort_unless($user->hasRole('super_admin') || (int) $cita->asesor_id === $user->id, 403);

        return $cita;
    }

    private function soloAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('super_admin'), 403, 'Solo la administración confirma o mueve citas.');
    }
}
