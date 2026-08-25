<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContratoGenerado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
