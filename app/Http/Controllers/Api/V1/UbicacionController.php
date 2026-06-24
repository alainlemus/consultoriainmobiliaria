<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use App\Models\UbicacionFoto;
use App\Models\User;
use App\Notifications\VisitaRegistrada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class UbicacionController extends Controller
{
    const DISK = 'local';

    /**
     * POST /api/v1/ubicaciones
     * Registra una visita GPS del asesor.
     * Para escuelas, latitud/longitud son opcionales (el asesor puede no estar ahí).
     */
    public function store(Request $request): JsonResponse
    {
        $esEscuela = ($request->input('tipo') === 'escuela');

        $data = $request->validate([
            'contacto_id'  => ['nullable', 'exists:contactos,id'],
            'latitud'      => [$esEscuela ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitud'     => [$esEscuela ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'tipo'         => ['nullable', 'in:visita_cliente,propiedad,escuela'],
            'nombre_lugar' => ['nullable', 'string', 'max:200'],
            'direccion'    => ['nullable', 'string', 'max:500'],
            'notas'        => ['nullable', 'string'],
            'municipio'    => ['nullable', 'string', 'max:100'],
            'estado'       => ['nullable', 'string', 'max:100'],
            'visitado_en'  => ['nullable', 'date'],
        ]);

        $geocodificado = ($data['latitud'] && $data['longitud'])
            ? $this->geocodificar(
                $data['latitud'],
                $data['longitud'],
                $data['municipio'] ?? null,
                $data['estado']    ?? null
              )
            : ['municipio' => $data['municipio'] ?? null, 'estado' => $data['estado'] ?? null];

        $ubicacion = Ubicacion::create([
            ...$data,
            'user_id'     => $request->user()->id,
            'tipo'        => $data['tipo']        ?? 'visita_cliente',
            'semaforo'    => $esEscuela ? 'amarillo' : 'amarillo',
            'visitado_en' => $data['visitado_en'] ?? now(),
            ...$geocodificado,
        ]);

        // Notificar a super_admin
        $ubicacion->loadMissing(['user', 'contacto']);
        User::role('super_admin')->get()->each(
            fn ($admin) => $admin->notify(new VisitaRegistrada($ubicacion))
        );

        return response()->json(['data' => $ubicacion], 201);
    }

    /**
     * PATCH /api/v1/ubicaciones/{id}/semaforo
     * Actualiza el estado del semáforo de una escuela.
     */
    public function actualizarSemaforo(Request $request, int $id): JsonResponse
    {
        $ubicacion = Ubicacion::findOrFail($id);

        // Solo el asesor dueño o super_admin puede cambiar el semáforo
        $user = $request->user();
        if (! $user->hasRole('super_admin') && $ubicacion->user_id !== $user->id) {
            abort(403, 'No tienes permiso para modificar esta escuela.');
        }

        if ($ubicacion->tipo !== 'escuela') {
            abort(422, 'El semáforo solo aplica a registros de tipo escuela.');
        }

        $data = $request->validate([
            'semaforo'       => ['required', 'in:verde,amarillo,rojo'],
            'semaforo_notas' => ['nullable', 'string', 'max:500'],
        ]);

        $ubicacion->update($data);

        return response()->json(['data' => $ubicacion->fresh()]);
    }

    /**
     * GET /api/v1/escuelas
     * Lista de escuelas para el buscador en el formulario de prospecto.
     * Incluye semáforo y conteo de maestros vinculados.
     */
    public function escuelas(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Ubicacion::where('tipo', 'escuela')
            ->withCount('contactosEscuela');

        // Asesor: ve solo sus escuelas + las de todo el equipo para poder vincular
        // (no restringimos por user_id aquí — el asesor puede vincular a una escuela
        //  que registró otro compañero)

        if ($q = $request->input('q')) {
            $query->where(function ($b) use ($q) {
                $b->where('nombre_lugar', 'like', "%{$q}%")
                  ->orWhere('municipio',  'like', "%{$q}%")
                  ->orWhere('estado',     'like', "%{$q}%")
                  ->orWhere('direccion',  'like', "%{$q}%");
            });
        }

        $escuelas = $query->orderBy('nombre_lugar')->limit(50)->get()
            ->map(fn (Ubicacion $e) => [
                'id'               => $e->id,
                'nombre_lugar'     => $e->nombre_lugar,
                'direccion'        => $e->direccion,
                'municipio'        => $e->municipio,
                'estado'           => $e->estado,
                'latitud'          => $e->latitud,
                'longitud'         => $e->longitud,
                'semaforo'         => $e->semaforo,
                'semaforo_notas'   => $e->semaforo_notas,
                'total_maestros'   => $e->contactos_escuela_count,
            ]);

        return response()->json(['data' => $escuelas]);
    }

    /**
     * POST /api/v1/ubicaciones/{id}/fotos
     * Sube una o varias fotos para una visita registrada.
     * Acepta multipart: fotos[] (múltiples archivos).
     */
    public function subirFotos(Request $request, int $id): JsonResponse
    {
        $ubicacion = Ubicacion::findOrFail($id);

        // Solo el asesor dueño o super_admin puede subir fotos
        $user = $request->user();
        if (! $user->hasRole('super_admin') && $ubicacion->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'fotos'   => ['required', 'array', 'min:1', 'max:10'],
            'fotos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $guardadas = [];

        foreach ($request->file('fotos') as $archivo) {
            $ruta = $archivo->store("ubicaciones/{$id}/fotos", self::DISK);

            $foto = UbicacionFoto::create([
                'ubicacion_id' => $id,
                'ruta'         => $ruta,
                'mime'         => $archivo->getMimeType(),
            ]);

            $guardadas[] = [
                'id'  => $foto->id,
                'url' => route('api.ubicacion.foto', ['fotoId' => $foto->id]),
            ];
        }

        return response()->json(['data' => $guardadas], 201);
    }

    /**
     * GET /api/ubicaciones/fotos/{fotoId}?signature=...
     * Sirve el binario de la foto — acceso via URL firmada (1h), sin auth.
     */
    public function verFoto(Request $request, int $fotoId)
    {
        $foto = UbicacionFoto::findOrFail($fotoId);

        if (! Storage::disk(self::DISK)->exists($foto->ruta)) {
            abort(404, 'Foto no encontrada.');
        }

        return response()->file(
            Storage::disk(self::DISK)->path($foto->ruta),
            ['Content-Type' => $foto->mime ?? 'image/jpeg']
        );
    }

    /**
     * GET /api/v1/ubicaciones/mapa
     * Devuelve puntos para el mapa con sus fotos (URLs firmadas 1h).
     * Incluye semáforo para escuelas y conteo de maestros.
     */
    public function mapa(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Ubicacion::with([
            'contacto:id,nombre,estado_prospecto,foto',
            'fotos:id,ubicacion_id',
        ])
        ->withCount('contactosEscuela')
        ->select(['id', 'user_id', 'contacto_id', 'latitud', 'longitud', 'tipo',
                  'semaforo', 'semaforo_notas', 'nombre_lugar', 'direccion',
                  'notas', 'municipio', 'estado', 'visitado_en']);

        if (! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        } elseif ($asesorId = $request->input('asesor_id')) {
            $query->where('user_id', (int) $asesorId);
        }

        $puntos = $query->latest('visitado_en')->limit(500)->get()
            ->map(function (Ubicacion $u) {
                return [
                    'id'             => $u->id,
                    'latitud'        => $u->latitud,
                    'longitud'       => $u->longitud,
                    'tipo'           => $u->tipo,
                    'semaforo'       => $u->semaforo,
                    'semaforo_notas' => $u->semaforo_notas,
                    'total_maestros' => $u->contactos_escuela_count,
                    'nombre_lugar'   => $u->nombre_lugar,
                    'direccion'      => $u->direccion,
                    'notas'          => $u->notas,
                    'municipio'      => $u->municipio,
                    'estado'         => $u->estado,
                    'visitado_en'    => $u->visitado_en,
                    'contacto_id'    => $u->contacto_id,
                    'contacto'       => $u->contacto?->nombre,
                    'contacto_foto_url' => $u->contacto?->foto_url,
                    'fotos'          => $u->fotos->map(fn ($f) => [
                        'id'  => $f->id,
                        'url' => \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $f->id], now()->addHour()),
                    ]),
                ];
            });

        return response()->json(['data' => $puntos]);
    }

    /**
     * Obtiene municipio y estado via Nominatim si no vienen en el request.
     */
    private function geocodificar(float $lat, float $lng, ?string $municipio, ?string $estado): array
    {
        if ($municipio && $estado) {
            return ['municipio' => $municipio, 'estado' => $estado];
        }

        try {
            $resp = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'ConsultoriaInmobiliaria/1.0'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat'             => $lat,
                    'lon'             => $lng,
                    'format'          => 'json',
                    'accept-language' => 'es',
                    'zoom'            => 10,
                ]);

            if ($resp->successful()) {
                $addr = $resp->json('address', []);
                return [
                    'municipio' => $municipio ?? ($addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['county'] ?? null),
                    'estado'    => $estado    ?? ($addr['state'] ?? null),
                ];
            }
        } catch (\Throwable) {
            // Si falla el geocoding, no bloquear el registro
        }

        return ['municipio' => $municipio, 'estado' => $estado];
    }
}

