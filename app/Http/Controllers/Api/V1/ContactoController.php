<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactoController extends Controller
{
    /**
     * GET /api/v1/contactos
     * Asesor: solo sus propios contactos. super_admin: todos.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Contacto::query()->with('asesor:id,name');

        if (! $user->hasRole('super_admin')) {
            $query->where('asesor_id', $user->id);
        }

        // Excluir prospectos que ya pasaron a expediente (igual que el CRM)
        $query->whereNotIn('estado_prospecto', ['convertido', 'descartado'])
              ->whereDoesntHave('expedientes');

        if ($q = $request->input('q')) {
            $query->where(function ($b) use ($q) {
                $b->where('nombre',    'like', "%{$q}%")
                  ->orWhere('email',   'like', "%{$q}%")
                  ->orWhere('telefono','like', "%{$q}%");
            });
        }

        if ($estado = $request->input('estado')) {
            $query->where('estado_prospecto', $estado);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $result  = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'total'        => $result->total(),
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
                'per_page'     => $result->perPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/contactos
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'                  => ['required', 'string', 'max:200'],
            'telefono'                => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:150'],
            'servicio'                => ['nullable', 'in:FOVISSSTE,INFONAVIT,AVALUO,ESCRITURACION,ASESORIA_PERSONALIZADA,OTRO'],
            'estado_prospecto'        => ['nullable', 'in:nuevo,contactado,precalificado,en_tramite,cerrado,no_interesado'],
            'notas'                   => ['nullable', 'string'],
            'origen'                  => ['nullable', 'string', 'max:80'],
            'curp'                    => ['nullable', 'string', 'max:18'],
            'nss'                     => ['nullable', 'string', 'max:15'],
            // Escuela vinculada (maestros FOVISSSTE)
            'escuela_id'              => ['nullable', 'exists:ubicaciones,id'],
            // Precalificación (FOVISSSTE / INFONAVIT)
            'estado_uso_credito'      => ['nullable', 'string', 'max:100'],
            'municipio_uso_credito'   => ['nullable', 'string', 'max:100'],
            'estado_residencia'       => ['nullable', 'string', 'max:100'],
            'regimen_pensionario'     => ['nullable', 'string', 'max:80'],
            'tiene_discapacidad'      => ['nullable', 'boolean'],
        ]);

        $contacto = Contacto::create([
            ...$data,
            'asesor_id'        => $request->user()->id,
            'estado_prospecto' => $data['estado_prospecto'] ?? 'nuevo',
            'origen'           => 'app_movil',
        ]);

        return response()->json(['data' => $contacto], 201);
    }

    /**
     * GET /api/v1/contactos/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $contacto = $this->findForUser($request, $id);

        // Incluir el expediente más reciente para que la app sepa si ya está en trámite
        $expediente = $contacto->expedientes()
            ->whereNotIn('estado', ['cancelado'])
            ->latest()
            ->select(['id', 'folio', 'estado', 'tipo_tramite_id', 'created_at'])
            ->first();

        // Incluir escuela vinculada con su semáforo
        $contacto->load(['escuela:id,nombre_lugar,municipio,estado,semaforo,semaforo_notas,latitud,longitud']);

        $data = $contacto->toArray();
        $data['expediente_activo'] = $expediente;

        return response()->json(['data' => $data]);
    }

    /**
     * PUT /api/v1/contactos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contacto = $this->findForUser($request, $id);

        $data = $request->validate([
            'nombre'                  => ['sometimes', 'string', 'max:200'],
            'telefono'                => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:150'],
            'servicio'                => ['nullable', 'in:FOVISSSTE,INFONAVIT,AVALUO,ESCRITURACION,ASESORIA_PERSONALIZADA,OTRO'],
            'estado_prospecto'        => ['nullable', 'in:nuevo,contactado,precalificado,en_tramite,cerrado,no_interesado'],
            'notas'                   => ['nullable', 'string'],
            'curp'                    => ['nullable', 'string', 'max:18'],
            'nss'                     => ['nullable', 'string', 'max:15'],
            // Escuela vinculada (maestros FOVISSSTE)
            'escuela_id'              => ['nullable', 'exists:ubicaciones,id'],
            // Precalificación (FOVISSSTE / INFONAVIT)
            'estado_uso_credito'      => ['nullable', 'string', 'max:100'],
            'municipio_uso_credito'   => ['nullable', 'string', 'max:100'],
            'estado_residencia'       => ['nullable', 'string', 'max:100'],
            'regimen_pensionario'     => ['nullable', 'string', 'max:80'],
            'tiene_discapacidad'      => ['nullable', 'boolean'],
        ]);

        $contacto->update($data);

        return response()->json(['data' => $contacto->fresh()]);
    }

    /**
     * POST /api/v1/contactos/{id}/foto
     * Sube o reemplaza la foto de perfil del prospecto.
     */
    public function uploadFoto(Request $request, int $id): JsonResponse
    {
        $contacto = $this->findForUser($request, $id);

        $request->validate([
            'foto' => ['required', 'image', 'max:5120'], // máx 5 MB
        ]);

        // Eliminar foto anterior si existe
        if ($contacto->foto && Storage::disk('public')->exists($contacto->foto)) {
            Storage::disk('public')->delete($contacto->foto);
        }

        $path = $request->file('foto')->store("contactos/fotos", 'public');

        $contacto->update(['foto' => $path]);

        return response()->json(['data' => $contacto->fresh()]);
    }

    /**
     * POST /api/v1/contactos/{id}/simulador-screenshot
     * Sube o reemplaza la captura del simulador FOVISSSTE.
     */
    public function uploadSimuladorScreenshot(Request $request, int $id): JsonResponse
    {
        $contacto = $this->findForUser($request, $id);

        $request->validate([
            'screenshot' => ['required', 'image', 'max:10240'], // máx 10 MB
        ]);

        // Eliminar captura anterior si existe
        if ($contacto->simulador_screenshot && Storage::disk('public')->exists($contacto->simulador_screenshot)) {
            Storage::disk('public')->delete($contacto->simulador_screenshot);
        }

        $path = $request->file('screenshot')->store('contactos/simulador', 'public');

        $contacto->update([
            'simulador_screenshot' => $path,
            'estado_prospecto'     => 'precalificado',
        ]);

        return response()->json(['data' => $contacto->fresh()]);
    }

    // ── Helper privado ────────────────────────────────────────────────────────

    private function findForUser(Request $request, int $id): Contacto
    {
        $user = $request->user();

        $contacto = Contacto::with(['asesor:id,name'])->findOrFail($id);

        if (! $user->hasRole('super_admin') && $contacto->asesor_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este prospecto.');
        }

        return $contacto;
    }
}
