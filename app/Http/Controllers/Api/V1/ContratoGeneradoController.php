<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContratoGenerado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ContratoGeneradoController extends Controller
{
    private const DISK = 'local';
    private const CAMPOS_ARCHIVO = ['pdf', 'ine_acreditado', 'ine_solidario'];

    private function serialize(ContratoGenerado $c): array
    {
        return [
            ...$c->toArray(),
            'tiene_pdf'            => (bool) $c->pdf_path,
            'tiene_ine_acreditado' => (bool) $c->ine_acreditado_path,
            'tiene_ine_solidario'  => (bool) $c->ine_solidario_path,
        ];
    }

    /**
     * GET /api/v1/contratos/generados
     *
     * Historial de contratos generados en la app, para reconstruirlo del
     * lado del cliente (p. ej. tras desinstalar/reinstalar y perder el
     * historial local en AsyncStorage). Cada asesor ve solo los suyos;
     * super_admin ve todos.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ContratoGenerado::query()->latest();
        if (! $user->hasRole('super_admin')) {
            $query->where('asesor_id', $user->id);
        }

        $contratos = $query->get()->map(fn (ContratoGenerado $c) => $this->serialize($c));

        return response()->json(['data' => $contratos]);
    }

    /**
     * GET /api/v1/contratos/generados/{id}/ver
     *
     * URLs firmadas de corta duración (5 min) para el PDF y las INEs de un
     * contrato generado, usadas por la app para reabrirlo/compartirlo sin
     * exponer las rutas reales en disco.
     */
    public function ver(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $contrato = ContratoGenerado::findOrFail($id);

        if (! $user->hasRole('super_admin') && $contrato->asesor_id !== $user->id) {
            abort(403);
        }

        $urls = [];
        foreach (self::CAMPOS_ARCHIVO as $campo) {
            if (! $contrato->{"{$campo}_path"}) {
                continue;
            }
            $urls["{$campo}_url"] = URL::temporarySignedRoute(
                'api.contratos_generados.descargar',
                now()->addMinutes(5),
                ['id' => $id, 'campo' => $campo]
            );
        }

        return response()->json([...$urls, 'expira_en' => 300]);
    }

    /**
     * POST /api/v1/contratos/generados
     *
     * Sube, en una sola petición multipart, el PDF del contrato generado en
     * la app junto con las fotos de INE del acreditado y del obligado
     * solidario capturadas durante el registro. Se guardan como historial
     * en el backend (independiente de expedientes) para verlas desde
     * Filament — la app las conserva localmente aparte.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'local_id'                 => ['required', 'string', 'max:100'],
            'folio'                    => ['nullable', 'string', 'max:100'],
            'tipo_tramite'             => ['nullable', 'string', 'max:150'],
            'ciudad'                   => ['nullable', 'string', 'max:150'],
            'acreditado_nombre'        => ['required', 'string', 'max:255'],
            'acreditado_curp'          => ['nullable', 'string', 'max:20'],
            'acreditado_rfc'           => ['nullable', 'string', 'max:20'],
            'acreditado_nss'           => ['nullable', 'string', 'max:20'],
            'acreditado_clave_elector' => ['nullable', 'string', 'max:20'],
            'acreditado_domicilio'     => ['nullable', 'string'],
            'solidario_nombre'         => ['required', 'string', 'max:255'],
            'solidario_curp'           => ['nullable', 'string', 'max:20'],
            'solidario_rfc'            => ['nullable', 'string', 'max:20'],
            'solidario_domicilio'      => ['nullable', 'string'],
            'monto_credito'            => ['nullable', 'numeric', 'min:0'],
            'honorarios_porcentaje'    => ['nullable', 'numeric', 'min:0'],
            'honorarios_monto'         => ['nullable', 'numeric', 'min:0'],
            'pdf'                      => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'ine_acreditado'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,heic,webp', 'max:8192'],
            'ine_solidario'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,heic,webp', 'max:8192'],
        ]);

        // Idempotencia: si este local_id ya se subió (reintento tras una
        // conexión inestable), devolver el registro existente sin duplicar.
        $existente = ContratoGenerado::where('local_id', $data['local_id'])->first();
        if ($existente) {
            return response()->json(['data' => $this->serialize($existente)], 200);
        }

        $contrato = ContratoGenerado::create([
            ...collect($data)->except(self::CAMPOS_ARCHIVO)->toArray(),
            'asesor_id' => $request->user()->id,
            'pdf_path'  => '',
        ]);

        $carpeta = "contratos-generados/{$contrato->id}";
        $rutas   = ['pdf_path' => null, 'ine_acreditado_path' => null, 'ine_solidario_path' => null];

        foreach (self::CAMPOS_ARCHIVO as $campo) {
            if (! $request->hasFile($campo)) {
                continue;
            }
            $archivo = $request->file($campo);
            $nombre  = $campo . '_' . now()->format('YmdHis') . '.' . $archivo->getClientOriginalExtension();
            Storage::disk(self::DISK)->putFileAs($carpeta, $archivo, $nombre);
            $rutas["{$campo}_path"] = "{$carpeta}/{$nombre}";
        }

        $contrato->update($rutas);

        return response()->json(['data' => $this->serialize($contrato->fresh())], 201);
    }

    /**
     * GET /contratos-generados/{id}/{campo}/descargar (ruta firmada)
     *
     * Sirve el archivo binario (pdf / ine_acreditado / ine_solidario).
     * Usada por Filament para mostrar el contrato y las INEs — no requiere
     * Bearer token porque la firma de la URL es la autorización.
     */
    public function descargar(int $id, string $campo)
    {
        abort_unless(in_array($campo, self::CAMPOS_ARCHIVO), 404);

        $contrato = ContratoGenerado::findOrFail($id);
        $path     = $contrato->{"{$campo}_path"};

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404, 'Archivo no encontrado.');
        }

        $fullPath = Storage::disk(self::DISK)->path($path);
        $mime     = mime_content_type($fullPath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control'       => 'private, max-age=300',
        ]);
    }
}
