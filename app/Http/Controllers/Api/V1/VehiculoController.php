<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoCarga;
use App\Models\VehiculoFoto;
use App\Models\VehiculoReserva;
use App\Services\ImagenService;
use App\Services\VehiculoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Préstamo de vehículos desde la app: el asesor ve los vehículos, solicita,
 * hace los checklists de salida y entrega, y pasa las llaves con código.
 * La autorización la da el super admin en el panel.
 */
class VehiculoController extends Controller
{
    private const FOTO = ['image', 'mimes:jpg,jpeg,png,webp,heic', 'max:10240'];

    public function __construct(private VehiculoService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $vehiculos = Vehiculo::with(['tipo', 'llavesUsuario'])
            ->where('estado', '!=', Vehiculo::BAJA)
            ->whereHas('tipo', fn ($q) => $q->where('activo', true))
            ->orderBy('vehiculo_tipo_id')->orderBy('nombre')->get();

        return response()->json([
            'data' => $vehiculos->map(fn (Vehiculo $v) => $this->service->vehiculoPayload($v, $user))->values(),
            'meta' => ['licencia' => $this->licencia($user)],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->vehiculoPayload(Vehiculo::findOrFail($id), $request->user())]);
    }

    public function ocupacion(Request $request, int $id): JsonResponse
    {
        $d = $request->validate(['fecha' => ['required', 'date']]);
        $dia = Carbon::parse($d['fecha'], VehiculoService::TZ)->startOfDay();
        $rows = VehiculoReserva::with('asesor')
            ->where('vehiculo_id', Vehiculo::findOrFail($id)->id)
            ->whereIn('estado', [...VehiculoReserva::OCUPAN, VehiculoReserva::EN_ESPERA])
            ->seCruzaCon($dia->copy()->utc(), $dia->copy()->endOfDay()->utc())
            ->orderBy('inicio')->get();

        return response()->json(['data' => $rows->map(fn (VehiculoReserva $r) => [
            'id' => $r->id,
            'inicio' => $r->inicio->setTimezone(VehiculoService::TZ)->toIso8601String(),
            'fin' => $r->fin->setTimezone(VehiculoService::TZ)->toIso8601String(),
            'estado' => $r->estado,
            'asesor' => ['id' => $r->asesor->id, 'nombre' => $r->asesor->name],
        ])->values()]);
    }

    public function reservas(Request $request): JsonResponse
    {
        $user = $request->user();
        $rows = VehiculoReserva::where('asesor_id', $user->id)->latest('inicio')->limit(50)->get();

        return response()->json(['data' => $rows->map(fn ($r) => $this->service->reservaPayload($r, $user))->values()]);
    }

    public function reserva(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->reservaPayload($this->miReserva($request, $id), $request->user())]);
    }

    public function solicitar(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('asesor'), 403, 'Solo los asesores solicitan vehículos.');

        $d = $request->validate([
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
            'tipo_id' => ['nullable', 'required_without:vehiculo_id', 'integer', 'exists:vehiculo_tipos,id'],
            'todo_el_dia' => ['required', 'boolean'],
            'fecha' => ['nullable', 'required_if:todo_el_dia,true,1', 'date'],
            'inicio' => ['nullable', 'required_if:todo_el_dia,false,0', 'date'],
            'fin' => ['nullable', 'required_if:todo_el_dia,false,0', 'date'],
            'resguardo_casa' => ['sometimes', 'boolean'],
            'motivo' => ['required', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
            'cita_id' => ['nullable', 'integer', 'exists:citas,id'],
            'contacto_id' => ['nullable', 'integer', 'exists:contactos,id'],
            'expediente_id' => ['nullable', 'integer', 'exists:expedientes,id'],
        ], [
            'tipo_id.required_without' => 'Elige un vehículo o el tipo de vehículo.',
            'motivo.required' => 'Escribe para qué necesitas el vehículo.',
        ]);

        // Solo puede ligar citas, prospectos y expedientes propios.
        abort_if(! empty($d['cita_id']) && (int) Cita::find($d['cita_id'])?->asesor_id !== $user->id, 403);
        abort_if(! empty($d['contacto_id']) && (int) Contacto::find($d['contacto_id'])?->asesor_id !== $user->id, 403);
        abort_if(! empty($d['expediente_id']) && (int) Expediente::find($d['expediente_id'])?->asesor_id !== $user->id, 403);

        $reserva = $this->service->solicitar([...$d, 'todo_el_dia' => $request->boolean('todo_el_dia'), 'resguardo_casa' => $request->boolean('resguardo_casa')], $user);
        $mensaje = $reserva->estado === VehiculoReserva::EN_ESPERA
            ? 'Ese horario ya lo pidió otro asesor. Quedaste en espera y te avisaremos si se libera.'
            : 'Solicitud enviada. Te avisaremos cuando la autoricen.';

        return response()->json(['message' => $mensaje, 'data' => $this->service->reservaPayload($reserva, $user)], 201);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $d = $request->validate(['motivo' => ['nullable', 'string', 'max:255']]);
        $r = $this->service->cancelar($this->miReserva($request, $id), $request->user(), $d['motivo'] ?? null);

        return response()->json(['data' => $this->service->reservaPayload($r, $request->user())]);
    }

    public function salida(Request $request, int $id): JsonResponse
    {
        $d = $this->validarChecklist($request) + $request->validate(['llaves_codigo' => ['nullable', 'string', 'size:4']]);
        $r = $this->service->registrarSalida($this->miReserva($request, $id), $request->user(), $d, $this->fotos($request), $request->file('fotos_danos', []));

        return response()->json(['data' => $this->service->reservaPayload($r, $request->user())]);
    }

    public function entrega(Request $request, int $id): JsonResponse
    {
        $d = $this->validarChecklist($request) + $request->validate(['llaves_destino' => ['required', 'in:oficina,conmigo']]);
        $r = $this->service->registrarEntrega($this->miReserva($request, $id), $request->user(), $d, $this->fotos($request), $request->file('fotos_danos', []));

        return response()->json(['data' => $this->service->reservaPayload($r, $request->user())]);
    }

    public function cambiarVehiculo(Request $request, int $id): JsonResponse
    {
        $d = $request->validate(['vehiculo_id' => ['required', 'integer']]);
        $r = $this->service->cambiarVehiculo($this->miReserva($request, $id), $request->user(), (int) $d['vehiculo_id']);

        return response()->json(['data' => $this->service->reservaPayload($r, $request->user())]);
    }

    public function resguardo(Request $request, int $id): JsonResponse
    {
        $d = $request->validate([
            'km' => ['required', 'integer', 'min:0', 'max:9999999'],
            'gasolina' => ['required', 'integer', 'between:0,4'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'fotos' => ['required', 'array'],
            'fotos.*' => self::FOTO,
        ], ['km.required' => 'Escribe el kilometraje del tablero.', 'fotos.required' => 'Faltan las fotos.']);
        unset($d['fotos']);
        $r = $this->service->registrarResguardo($this->miReserva($request, $id), $request->user(), $d, $this->fotos($request));

        return response()->json(['data' => $this->service->reservaPayload($r, $request->user())]);
    }

    public function retomar(Request $request, int $id): JsonResponse
    {
        $d = $request->validate([
            'km' => ['required', 'integer', 'min:0', 'max:9999999'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'fotos' => ['required', 'array'],
            'fotos.*' => self::FOTO,
        ], ['km.required' => 'Escribe el kilometraje del tablero.', 'fotos.required' => 'Falta la foto del tablero.']);
        unset($d['fotos']);
        $r = $this->service->retomar($this->miReserva($request, $id), $request->user(), $d, $this->fotos($request));

        return response()->json(['data' => $this->service->reservaPayload($r, $request->user())]);
    }

    public function carga(Request $request, int $id): JsonResponse
    {
        $d = $request->validate([
            'km' => ['required', 'integer', 'min:0', 'max:9999999'],
            'litros' => ['required', 'numeric', 'min:0.1', 'max:999'],
            'monto' => ['required', 'numeric', 'min:1', 'max:99999'],
            'pago' => ['required', 'in:tarjeta_empresa,efectivo_empresa,reembolso'],
            'fotos' => ['required', 'array'],
            'fotos.*' => self::FOTO,
        ], [
            'km.required' => 'Escribe el kilometraje del tablero.', 'litros.required' => 'Escribe los litros.',
            'monto.required' => 'Escribe el monto del ticket.', 'pago.required' => 'Elige cómo se pagó.', 'fotos.required' => 'Faltan las fotos del ticket y del tablero.',
        ]);
        unset($d['fotos']);
        $reserva = $this->miReserva($request, $id);
        $this->service->registrarCarga($reserva, $request->user(), $d, $this->fotos($request));

        return response()->json(['data' => $this->service->reservaPayload($reserva->fresh(), $request->user())], 201);
    }

    public function reembolsos(Request $request): JsonResponse
    {
        $cargas = VehiculoCarga::where('user_id', $request->user()->id)->where('pago', 'reembolso')->latest()->limit(100)->get();

        return response()->json(['data' => $cargas->map(fn ($c) => $this->service->cargaPayload($c))->values()]);
    }

    public function codigoLlaves(Request $request, int $id): JsonResponse
    {
        return response()->json(['data' => $this->service->generarCodigo(Vehiculo::findOrFail($id), $request->user())]);
    }

    public function recibirLlaves(Request $request, int $id): JsonResponse
    {
        $d = $request->validate(['codigo' => ['required', 'string', 'size:4']]);
        $v = $this->service->recibirLlaves(Vehiculo::findOrFail($id), $request->user(), $d['codigo']);

        return response()->json(['data' => $this->service->vehiculoPayload($v, $request->user())]);
    }

    public function llavesOficina(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'fotos.llaves' => ['required', ...self::FOTO],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ], ['fotos.llaves.required' => 'Toma una foto de las llaves donde las dejas.']);

        $v = $this->service->dejarLlavesEnOficina(Vehiculo::findOrFail($id), $request->user(), $request->file('fotos.llaves'),
            $request->filled('lat') ? (float) $request->input('lat') : null, $request->filled('lng') ? (float) $request->input('lng') : null);

        return response()->json(['data' => $this->service->vehiculoPayload($v, $request->user())]);
    }

    // ── Licencia de manejo (en el perfil) ────────────────────────────────────

    public function verLicencia(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->licencia($request->user(), conDetalle: true)]);
    }

    public function subirLicencia(Request $request): JsonResponse
    {
        $d = $request->validate([
            'numero' => ['nullable', 'string', 'max:40'],
            'vence' => ['required', 'date'],
            'foto' => ['required', ...self::FOTO],
        ], ['vence.required' => 'Indica la fecha de vencimiento de tu licencia.', 'foto.required' => 'Toma una foto de tu licencia.']);

        $user = $request->user();
        $ruta = (new ImagenService)->comprimirYGuardar($request->file('foto'), "licencias/{$user->id}", 'local');
        if ($user->licencia_foto && $user->licencia_foto !== $ruta) {
            Storage::disk('local')->delete($user->licencia_foto);
        }
        $user->update(['licencia_numero' => $d['numero'] ?? null, 'licencia_vence' => $d['vence'], 'licencia_foto' => $ruta]);

        return response()->json(['data' => $this->licencia($user->fresh(), conDetalle: true)]);
    }

    // ── Archivos (URL firmada, sin auth) ─────────────────────────────────────

    public function verFoto(Request $request, int $fotoId)
    {
        $foto = VehiculoFoto::findOrFail($fotoId);
        $ruta = ($request->boolean('thumb') && $foto->ruta_thumb) ? $foto->ruta_thumb : $foto->ruta;
        abort_unless(Storage::disk('local')->exists($ruta), 404, 'Foto no encontrada.');

        return response()->file(Storage::disk('local')->path($ruta), ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=3600']);
    }

    public function verLicenciaFoto(int $userId)
    {
        $user = User::findOrFail($userId);
        abort_unless($user->licencia_foto && Storage::disk('local')->exists($user->licencia_foto), 404);

        return response()->file(Storage::disk('local')->path($user->licencia_foto), ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=3600']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validarChecklist(Request $request): array
    {
        $d = $request->validate([
            'km' => ['required', 'integer', 'min:0', 'max:9999999'],
            'gasolina' => ['required', 'integer', 'between:0,4'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notas_danos' => ['nullable', 'string', 'max:1000'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['string', 'max:100'],
            'fotos' => ['required', 'array'],
            'fotos.*' => self::FOTO,
            'fotos_danos' => ['nullable', 'array', 'max:6'],
            'fotos_danos.*' => self::FOTO,
        ], [
            'km.required' => 'Escribe el kilometraje del tablero.',
            'gasolina.required' => 'Marca el nivel de gasolina.',
            'fotos.required' => 'Faltan las fotos del vehículo.',
        ]);
        unset($d['fotos'], $d['fotos_danos']);

        return $d;
    }

    /** @return array<string, UploadedFile> */
    private function fotos(Request $request): array
    {
        return array_filter((array) $request->file('fotos', []), fn ($f) => $f instanceof UploadedFile);
    }

    private function miReserva(Request $request, int $id): VehiculoReserva
    {
        $r = VehiculoReserva::findOrFail($id);
        abort_unless((int) $r->asesor_id === $request->user()->id || $request->user()->hasRole('super_admin'), 403);

        return $r;
    }

    private function licencia(User $user, bool $conDetalle = false): array
    {
        $base = [
            'tiene' => (bool) $user->licencia_vence,
            'vigente' => $user->licenciaVigente(),
            'vence' => $user->licencia_vence?->toDateString(),
        ];

        return $conDetalle ? $base + [
            'numero' => $user->licencia_numero,
            'foto_url' => $user->licencia_foto ? URL::signedRoute('api.user.licencia', ['userId' => $user->id], now()->addHour()->startOfHour()) : null,
        ] : $base;
    }
}
