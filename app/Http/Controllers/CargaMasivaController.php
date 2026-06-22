<?php

namespace App\Http\Controllers;

use App\Services\CargaMasivaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Filament\Resources\ExpedienteResource;

class CargaMasivaController extends Controller
{
    public function __construct(
        private CargaMasivaService $servicio
    ) {}

    /**
     * Paso 1: recibe UN archivo, lo guarda en tmp y devuelve la ruta temporal.
     */
    public function uploadArchivo(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user || (! $user->can('Create:Expediente') && ! $user->hasRole('super_admin'))) {
            return response()->json(['error' => 'Sin permiso.'], 403);
        }

        $request->validate([
            'archivo' => 'required|file|max:102400', // 100MB
            'ruta'    => 'required|string|max:500',
        ]);

        $file    = $request->file('archivo');
        $tmpDir  = 'tmp/carga_masiva/' . session()->getId();
        $nombre  = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                   . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $rutaTmp = Storage::disk('local')->putFileAs($tmpDir, $file, $nombre);

        return response()->json([
            'ok'       => true,
            'tmp_path' => $rutaTmp,
        ]);
    }

    /**
     * Paso 2: recibe las rutas tmp + metadatos y crea el expediente.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user || (! $user->can('Create:Expediente') && ! $user->hasRole('super_admin'))) {
            return response()->json(['error' => 'Sin permiso.'], 403);
        }

        $request->validate([
            'archivos_json' => 'required|string',
            'asesor_id'     => 'nullable|integer|exists:users,id',
        ]);

        $archivosJson = json_decode($request->input('archivos_json'), true);

        if (empty($archivosJson)) {
            return response()->json(['error' => 'No se recibieron archivos.'], 422);
        }

        // Reconstruir items desde los archivos temporales
        $items = [];
        foreach ($archivosJson as $item) {
            $tmpPath      = $item['tmp_path'] ?? null;
            $rutaRelativa = $item['ruta']     ?? null;

            if (! $tmpPath) continue;

            $rutaAbsoluta = Storage::disk('local')->path($tmpPath);
            if (! file_exists($rutaAbsoluta)) continue;

            $items[] = [
                'file' => new \Illuminate\Http\UploadedFile(
                    $rutaAbsoluta,
                    basename($tmpPath),
                    null, null, true
                ),
                'ruta_relativa' => $rutaRelativa ?? basename($tmpPath),
            ];
        }

        if (empty($items)) {
            return response()->json(['error' => 'No se encontraron los archivos subidos.'], 422);
        }

        $datosBase = array_filter([
            'tipo_tramite_id' => $request->input('tipo_tramite_id'),
            'asesor_id'       => $request->input('asesor_id') ?? $user->id,
        ]);

        try {
            $resultado  = $this->servicio->crearExpedienteDesdeArchivos($items, $datosBase);
            $expediente = $resultado['expediente'];

            // Limpiar temporales
            $tmpDir = 'tmp/carga_masiva/' . session()->getId();
            Storage::disk('local')->deleteDirectory($tmpDir);

            $extraidos = $resultado['datos_extraidos'];
            unset($extraidos['_fuentes']);
            $camposRellenos = count(array_filter($extraidos));

            $msg = "Expediente {$expediente->folio} creado con {$resultado['documentos_creados']} documentos.";
            if ($camposRellenos > 0) {
                $msg .= " Se pre-rellenaron {$camposRellenos} campos automáticamente.";
            }

            return response()->json([
                'ok'           => true,
                'folio'        => $expediente->folio,
                'mensaje'      => $msg,
                'redirect_url' => ExpedienteResource::getUrl('edit', ['record' => $expediente]),
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CargaMasiva error: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar: ' . $e->getMessage()], 500);
        }
    }
}
