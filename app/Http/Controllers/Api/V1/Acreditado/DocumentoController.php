<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use App\Models\DocumentoExpediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class DocumentoController extends Controller
{
    private const DISK = 'local';

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/expediente/documentos
    // Lista todos los documentos del expediente del acreditado
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $acreditado = $request->user();
        $expediente = $acreditado->expedientes()->latest()->first();

        if (! $expediente) {
            return response()->json(['data' => []]);
        }

        $documentos = $expediente->documentos()
            ->orderBy('seccion')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($d) => $this->documentoPayload($d));

        return response()->json(['data' => $documentos]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/expediente/documentos
    // El acreditado sube un documento propio al expediente
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $acreditado = $request->user();
        $expediente = $acreditado->expedientes()->latest()->first();

        if (! $expediente) {
            return response()->json(['message' => 'No tienes un expediente activo.'], 422);
        }

        $request->validate([
            'archivo'        => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,heic,webp'],
            'tipo_documento' => ['required', 'string', 'max:150'],
            'notas'          => ['nullable', 'string', 'max:500'],
        ]);

        $archivo  = $request->file('archivo');
        $mimeReal = mime_content_type($archivo->getRealPath()) ?: '';
        $heicMimes = ['image/heic', 'image/heif', 'image/x-heic', 'image/x-heif'];
        $esHeic   = in_array(strtolower($mimeReal), $heicMimes);

        $nombreBase = Str::slug($request->tipo_documento) . '_acreditado_' . time();

        if ($esHeic && class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick($archivo->getRealPath());
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality(90);
                $contenido = $im->getImageBlob();
                $im->clear();
                $ext  = 'jpg';
                $ruta = "expedientes/{$expediente->id}/docs/acreditada/{$nombreBase}.{$ext}";
                Storage::disk(self::DISK)->put($ruta, $contenido);
            } catch (\Throwable) {
                $ext  = $archivo->getClientOriginalExtension() ?: 'heic';
                $ruta = "expedientes/{$expediente->id}/docs/acreditada/{$nombreBase}.{$ext}";
                Storage::disk(self::DISK)->putFileAs("expedientes/{$expediente->id}/docs/acreditada", $archivo, "{$nombreBase}.{$ext}");
            }
        } else {
            $ext  = $archivo->getClientOriginalExtension();
            $ruta = "expedientes/{$expediente->id}/docs/acreditada/{$nombreBase}.{$ext}";
            Storage::disk(self::DISK)->putFileAs("expedientes/{$expediente->id}/docs/acreditada", $archivo, "{$nombreBase}.{$ext}");
        }

        $doc = DocumentoExpediente::updateOrCreate(
            [
                'expediente_id' => $expediente->id,
                'tipo'          => $request->tipo_documento,
                'seccion'       => 'acreditado',
                'categoria'     => 'acreditada',
            ],
            [
                'nombre'               => $request->tipo_documento,
                'estado'               => 'recibido',
                'notas'                => $request->input('notas'),
                'ruta_archivo'         => $ruta,
                'subido_por_acreditado'=> true,
            ]
        );

        // Notificar al asesor
        if ($expediente->asesor) {
            $expediente->asesor->notify(new \App\Notifications\DocumentoSubido($expediente, $doc));
        }

        return response()->json([
            'message'   => 'Documento subido correctamente.',
            'documento' => $this->documentoPayload($doc),
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/expediente/documentos/{id}/ver
    // Genera URL firmada para ver un documento
    // ─────────────────────────────────────────────────────────────────────────
    public function ver(Request $request, int $documentoId): JsonResponse
    {
        $acreditado = $request->user();
        $expediente = $acreditado->expedientes()->latest()->first();

        if (! $expediente) {
            abort(403);
        }

        $doc = $expediente->documentos()->findOrFail($documentoId);

        if (! $doc->ruta_archivo) {
            return response()->json(['message' => 'Este documento no tiene archivo adjunto.'], 404);
        }

        $url = URL::temporarySignedRoute(
            'api.documentos.descargar',
            now()->addMinutes(30),
            ['expedienteId' => $expediente->id, 'documentoId' => $doc->id]
        );

        return response()->json(['url' => $url, 'expira_en' => 30]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    private function documentoPayload(DocumentoExpediente $doc): array
    {
        return [
            'id'                    => $doc->id,
            'nombre'                => $doc->nombre,
            'seccion'               => $doc->seccion,
            'categoria'             => $doc->categoria,
            'estado'                => $doc->estado,
            'tiene_archivo'         => (bool) $doc->ruta_archivo,
            'subido_por_acreditado' => (bool) $doc->subido_por_acreditado,
            'notas'                 => $doc->notas,
        ];
    }
}
