<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentoController extends Controller
{
    /**
     * POST /api/v1/expedientes/{expedienteId}/documentos
     */
    public function store(Request $request, int $expedienteId): JsonResponse
    {
        $user = $request->user();
        $exp  = Expediente::findOrFail($expedienteId);

        if (! $user->hasRole('super_admin') && $exp->asesor_id !== $user->id) {
            abort(403, 'No tienes permiso para subir documentos a este expediente.');
        }

        $request->validate([
            'archivo'        => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,heic,webp'],
            'tipo_documento' => ['required', 'string', 'max:100'],
            'notas'          => ['nullable', 'string'],
        ]);

        $archivo = $request->file('archivo');
        $ext     = $archivo->getClientOriginalExtension();
        $nombre  = Str::slug($request->tipo_documento) . '_' . now()->format('YmdHis') . '.' . $ext;
        $ruta    = "expedientes/{$expedienteId}/docs/{$nombre}";

        Storage::disk('public')->putFileAs(
            "expedientes/{$expedienteId}/docs",
            $archivo,
            $nombre
        );

        $doc = DocumentoExpediente::create([
            'expediente_id' => $expedienteId,
            'tipo'          => $request->tipo_documento,
            'nombre'        => $archivo->getClientOriginalName(),
            'estado'        => 'pendiente',
            'notas'         => $request->input('notas'),
            'ruta_archivo'  => $ruta,
        ]);

        return response()->json([
            'data' => [
                ...$doc->toArray(),
                'url' => Storage::disk('public')->url($ruta),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/expedientes/{expedienteId}/documentos
     */
    public function index(Request $request, int $expedienteId): JsonResponse
    {
        $user = $request->user();
        $exp  = Expediente::findOrFail($expedienteId);

        if (! $user->hasRole('super_admin') && $exp->asesor_id !== $user->id) {
            abort(403);
        }

        $docs = DocumentoExpediente::where('expediente_id', $expedienteId)->get()
            ->map(fn ($d) => [
                ...$d->toArray(),
                'url' => $d->ruta_archivo ? Storage::disk('public')->url($d->ruta_archivo) : null,
            ]);

        return response()->json(['data' => $docs]);
    }
}
