<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Models\AnuncioFoto;
use App\Models\User;
use App\Services\ImagenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AnuncioController extends Controller
{
    const DISK = 'local';

    /**
     * GET /api/v1/anuncios/mapa
     * Devuelve todos los anuncios para el mapa (todos los asesores ven todos).
     * Esto es intencional: el objetivo es ver dónde hay anuncios para no duplicar.
     */
    public function mapa(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Anuncio::with(['user:id,name', 'fotos:id,anuncio_id'])
            ->select([
                'id', 'user_id', 'latitud', 'longitud',
                'tipo', 'estado', 'descripcion', 'direccion',
                'colonia', 'municipio', 'estado_geo', 'colocado_en',
            ]);

        // Todos los asesores ven todos los anuncios para evitar duplicados de territorio
        // (a diferencia del mapa de visitas que es privado)
        // Solo el super_admin puede ver los retirados; asesores solo ven activos
        if (! $user->hasRole('super_admin')) {
            $query->where('estado', 'activo');
        }

        $anuncios = $query->latest('colocado_en')->limit(1000)->get()
            ->map(fn (Anuncio $a) => [
                'id'          => $a->id,
                'latitud'     => $a->latitud,
                'longitud'    => $a->longitud,
                'tipo'        => $a->tipo,
                'estado'      => $a->estado,
                'descripcion' => $a->descripcion,
                'direccion'   => $a->direccion,
                'colonia'     => $a->colonia,
                'municipio'   => $a->municipio,
                'estado_geo'  => $a->estado_geo,
                'colocado_en' => $a->colocado_en?->format('Y-m-d'),
                'asesor'      => $a->user?->name,
                'asesor_id'   => $a->user_id,
                'es_mio'      => $a->user_id === $user->id,
                'fotos'       => $a->fotos->map(fn ($f) => [
                    'id'  => $f->id,
                    // Expiración redondeada a la hora: mismas URLs en requests repetidos
                    // dentro de la misma hora, para que la app pueda cachear las imágenes.
                    'url' => \URL::signedRoute('api.anuncio.foto', ['fotoId' => $f->id], now()->addHour()->startOfHour()),
                ]),
            ]);

        return response()->json(['data' => $anuncios]);
    }

    /**
     * POST /api/v1/anuncios
     * Registra un anuncio colocado por el asesor.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitud'     => ['required', 'numeric', 'between:-90,90'],
            'longitud'    => ['required', 'numeric', 'between:-180,180'],
            'tipo'        => ['nullable', 'in:lona,hoja_tienda,hoja_poste,volante,otro'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'direccion'   => ['nullable', 'string', 'max:500'],
            'colonia'     => ['nullable', 'string', 'max:150'],
            'municipio'   => ['nullable', 'string', 'max:100'],
            'estado_geo'  => ['nullable', 'string', 'max:100'],
            'colocado_en' => ['nullable', 'date'],
        ]);

        // Reverse geocoding si no vienen municipio/estado
        if (empty($data['municipio']) || empty($data['estado_geo'])) {
            $geo = $this->geocodificar($data['latitud'], $data['longitud']);
            $data['municipio']  = $data['municipio']  ?? $geo['municipio'];
            $data['estado_geo'] = $data['estado_geo'] ?? $geo['estado'];
        }

        $anuncio = Anuncio::create([
            ...$data,
            'user_id'     => $request->user()->id,
            'tipo'        => $data['tipo']        ?? 'hoja_poste',
            'colocado_en' => $data['colocado_en'] ?? now()->toDateString(),
        ]);

        return response()->json(['data' => $anuncio], 201);
    }

    /**
     * PATCH /api/v1/anuncios/{id}/estado
     * Marca un anuncio como retirado (o lo reactiva).
     */
    public function actualizarEstado(Request $request, int $id): JsonResponse
    {
        $anuncio = Anuncio::findOrFail($id);
        $user    = $request->user();

        if (! $user->hasRole('super_admin') && $anuncio->user_id !== $user->id) {
            abort(403, 'No tienes permiso para modificar este anuncio.');
        }

        $data = $request->validate([
            'estado' => ['required', 'in:activo,retirado'],
        ]);

        $anuncio->update($data);
        return response()->json(['data' => $anuncio->fresh()]);
    }

    /**
     * POST /api/v1/anuncios/{id}/fotos
     * Sube fotos del anuncio (máx 5, para documentar su colocación).
     */
    public function subirFotos(Request $request, int $id): JsonResponse
    {
        $anuncio = Anuncio::findOrFail($id);
        $user    = $request->user();

        if (! $user->hasRole('super_admin') && $anuncio->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'fotos'   => ['required', 'array', 'min:1', 'max:5'],
            'fotos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $guardadas = [];
        $servicio  = new ImagenService();

        foreach ($request->file('fotos') as $archivo) {
            $ruta = $servicio->comprimirYGuardar(
                $archivo,
                "anuncios/{$id}/fotos",
                self::DISK
            );
            $rutaThumb = $servicio->generarThumbnail($ruta, self::DISK);

            $foto = AnuncioFoto::create([
                'anuncio_id' => $id,
                'ruta'       => $ruta,
                'ruta_thumb' => $rutaThumb,
                'mime'       => 'image/jpeg',
            ]);
            $guardadas[] = [
                'id'  => $foto->id,
                'url' => \URL::signedRoute('api.anuncio.foto', ['fotoId' => $foto->id], now()->addHour()),
            ];
        }

        return response()->json(['data' => $guardadas], 201);
    }

    /**
     * GET /api/anuncios/fotos/{fotoId}?signature=...
     * Sirve el binario de la foto — acceso via URL firmada (1h), sin auth.
     */
    public function verFoto(Request $request, int $fotoId)
    {
        $foto = AnuncioFoto::findOrFail($fotoId);

        $ruta = ($request->boolean('thumb') && $foto->ruta_thumb)
            ? $foto->ruta_thumb
            : $foto->ruta;

        if (! Storage::disk(self::DISK)->exists($ruta)) {
            abort(404, 'Foto no encontrada.');
        }

        return response()->file(
            Storage::disk(self::DISK)->path($ruta),
            [
                'Content-Type'  => $foto->mime ?? 'image/jpeg',
                // La URL firmada es estable dentro de la misma hora (ver generadores),
                // así que el navegador puede reutilizar la copia cacheada.
                'Cache-Control' => 'public, max-age=3600',
            ]
        );
    }

    private function geocodificar(float $lat, float $lng): array
    {
        try {
            $resp = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'ConsultoriaInmobiliaria/1.0'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat'             => $lat,
                    'lon'             => $lng,
                    'format'          => 'json',
                    'accept-language' => 'es',
                    'zoom'            => 14,
                ]);
            if ($resp->successful()) {
                $addr = $resp->json('address', []);
                return [
                    'municipio' => $addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['county'] ?? null,
                    'estado'    => $addr['state'] ?? null,
                    'colonia'   => $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['quarter'] ?? null,
                ];
            }
        } catch (\Throwable) {}
        return ['municipio' => null, 'estado' => null, 'colonia' => null];
    }
}
