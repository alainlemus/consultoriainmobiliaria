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
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contacto_id'  => ['nullable', 'exists:contactos,id'],
            'latitud'      => ['required', 'numeric', 'between:-90,90'],
            'longitud'     => ['required', 'numeric', 'between:-180,180'],
            'tipo'         => ['nullable', 'in:visita_cliente,propiedad,escuela'],
            'nombre_lugar' => ['nullable', 'string', 'max:200'],
            'direccion'    => ['nullable', 'string', 'max:500'],
            'notas'        => ['nullable', 'string'],
            'municipio'    => ['nullable', 'string', 'max:100'],
            'estado'       => ['nullable', 'string', 'max:100'],
            'visitado_en'  => ['nullable', 'date'],
        ]);

        $ubicacion = Ubicacion::create([
            ...$data,
            'user_id'     => $request->user()->id,
            'tipo'        => $data['tipo']        ?? 'visita_cliente',
            'visitado_en' => $data['visitado_en'] ?? now(),
            // Reverse geocoding si no vienen municipio/estado
            ...$this->geocodificar(
                $data['latitud'],
                $data['longitud'],
                $data['municipio'] ?? null,
                $data['estado']    ?? null
            ),
        ]);

        // Notificar a super_admin
        $ubicacion->loadMissing(['user', 'contacto']);
        User::role('super_admin')->get()->each(
            fn ($admin) => $admin->notify(new VisitaRegistrada($ubicacion))
        );

        return response()->json(['data' => $ubicacion], 201);
    }

    /**
     * Obtiene municipio y estado via Nominatim si no vienen en el request.
     */
    private function geocodificar(float $lat, float $lng, ?string $municipio, ?string $estado): array
    {
        // Si ya vienen ambos del cliente, no consultar
        if ($municipio && $estado) {
            return ['municipio' => $municipio, 'estado' => $estado];
        }

        try {
            $resp = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'ConsultoriaInmobiliaria/1.0'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat'            => $lat,
                    'lon'            => $lng,
                    'format'         => 'json',
                    'accept-language' => 'es',
                    'zoom'           => 10,
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
     */
    public function mapa(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Ubicacion::with([
            'contacto:id,nombre,estado_prospecto',
            'fotos:id,ubicacion_id',
        ])->select(['id', 'user_id', 'contacto_id', 'latitud', 'longitud', 'tipo', 'nombre_lugar', 'direccion', 'notas', 'municipio', 'estado', 'visitado_en']);

        if (! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        }

        $puntos = $query->latest('visitado_en')->limit(500)->get()
            ->map(function (Ubicacion $u) {
                return [
                    'id'           => $u->id,
                    'latitud'      => $u->latitud,
                    'longitud'     => $u->longitud,
                    'tipo'         => $u->tipo,
                    'nombre_lugar' => $u->nombre_lugar,
                    'direccion'    => $u->direccion,
                    'notas'        => $u->notas,
                    'municipio'    => $u->municipio,
                    'estado'       => $u->estado,
                    'visitado_en'  => $u->visitado_en,
                    'contacto_id'  => $u->contacto_id,
                    'contacto'     => $u->contacto?->nombre,
                     'fotos'       => $u->fotos->map(fn ($f) => [
                         'id'  => $f->id,
                         // URL firmada válida 1 hora — Image en RN puede cargarla sin headers
                         'url' => \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $f->id], now()->addHour()),
                     ]),
                ];
            });

        return response()->json(['data' => $puntos]);
    }
}
