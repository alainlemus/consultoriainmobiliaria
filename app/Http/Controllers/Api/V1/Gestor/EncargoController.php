<?php

namespace App\Http\Controllers\Api\V1\Gestor;

use App\Http\Controllers\Controller;
use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;
use App\Services\CuvService;
use App\Services\EncargoGestorService;
use App\Support\EncargoGestorPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * API del gestor (/v1/gestor/…). Solo ve sus encargos y, de cada expediente,
 * los documentos que la administración le compartió más los que él subió.
 */
class EncargoController extends Controller
{
    public function __construct(private EncargoGestorService $service) {}

    /** GET /gestor/encargos?estado=activos|en_revision|terminados */
    public function index(Request $request): JsonResponse
    {
        $q = $request->user()->encargosGestor()
            ->with(['expediente:id,folio,acreditado_nombre,acreditado_municipio,acreditado_estado,tipo_tramite_id', 'expediente.tipoTramite:id,nombre'])
            ->withCount(['documentos', 'documentosSubidos'])
            ->latest();

        match ($request->input('estado')) {
            'terminados' => $q->whereIn('estado', [EncargoGestor::TERMINADO, EncargoGestor::CANCELADO]),
            'todos' => null,
            default => $q->activos(),
        };

        return response()->json(['data' => $q->get()->map(fn (EncargoGestor $e) => EncargoGestorPayload::resumen($e))]);
    }

    /** GET /gestor/encargos/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $e = $this->encargo($request, $id);

        return response()->json(['data' => EncargoGestorPayload::detalle($e)]);
    }

    /** GET /gestor/encargos/{id}/documentos/{documentoId}/url — URL firmada (5 min) para descargar */
    public function urlDocumento(Request $request, int $id, int $documentoId): JsonResponse
    {
        $e = $this->encargo($request, $id);

        $permitido = $e->documentos()->whereKey($documentoId)->exists()
            || $e->documentosSubidos()->whereKey($documentoId)->exists();
        abort_unless($permitido, 404);

        $doc = DocumentoExpediente::findOrFail($documentoId);
        abort_unless($doc->ruta_archivo, 404, 'Este documento no tiene archivo.');

        $url = URL::temporarySignedRoute('api.documentos.descargar', now()->addMinutes(5), [
            'expedienteId' => $e->expediente_id,
            'documentoId' => $doc->id,
        ]);

        return response()->json(['url' => $url, 'expira_en' => 300]);
    }

    /** POST /gestor/encargos/{id}/revision  { revisiones: [{documento_id, revision: correcto|incorrecto, nota}] } */
    public function revision(Request $request, int $id): JsonResponse
    {
        $e = $this->encargo($request, $id);
        $data = $request->validate([
            'revisiones' => ['required', 'array', 'min:1'],
            'revisiones.*.documento_id' => ['required', 'integer'],
            'revisiones.*.revision' => ['required', 'in:correcto,incorrecto'],
            'revisiones.*.nota' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->enviarRevision($e, $request->user(), $data['revisiones']);

        return $this->show($request, $id);
    }

    /** POST /gestor/encargos/{id}/documentos  (multipart: archivo, tipo, notas?, reemplaza_documento_id?) */
    public function subir(Request $request, int $id): JsonResponse
    {
        $e = $this->encargo($request, $id);
        $data = $request->validate([
            'archivo' => ['required', 'file', 'max:20480', 'mimes:'.EncargoGestorService::MIMES],
            'tipo' => ['required', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:500'],
            'reemplaza_documento_id' => ['nullable', 'integer'],
        ], [
            'archivo.mimes' => 'Sube un PDF, un Word (.doc/.docx) o una imagen.',
            'archivo.max' => 'El archivo no puede pesar más de 20 MB.',
        ]);

        $doc = $this->service->subirDocumento($e, $request->user(), $request->file('archivo'), $data['tipo'], $data['notas'] ?? null, $data['reemplaza_documento_id'] ?? null);

        return response()->json(['data' => ['id' => $doc->id, 'validacion' => $doc->validacion]], 201);
    }

    /** POST /gestor/encargos/{id}/entregar — «Ya terminé» */
    public function entregar(Request $request, int $id): JsonResponse
    {
        $this->service->entregar($this->encargo($request, $id), $request->user());

        return $this->show($request, $id);
    }

    /** POST /gestor/encargos/{id}/reabrir — olvidó algo: vuelve a en_tramite */
    public function reabrir(Request $request, int $id): JsonResponse
    {
        $this->service->reabrir($this->encargo($request, $id), $request->user());

        return $this->show($request, $id);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * POST /v1/gestor/encargos/{id}/cuv  {cuv, archivo?}
     * El gestor que tiene el encargo en trámite registra la CUV que le entregó la unidad de valuación.
     */
    public function cuv(Request $request, int $id): JsonResponse
    {
        $encargo = $this->encargo($request, $id);
        abort_unless(in_array($encargo->estado, [EncargoGestor::EN_TRAMITE, EncargoGestor::POR_VALIDAR, EncargoGestor::TERMINADO], true), 422, 'Este encargo no está en trámite.');

        $data = $request->validate([
            'cuv' => ['required', 'regex:/^\d{16}$/'],
            'archivo' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,heic,webp'],
        ], ['cuv.regex' => 'La CUV son 16 dígitos.']);

        $exp = app(CuvService::class)->registrar($encargo->expediente, $data['cuv'], $request->file('archivo'), $request->user());

        return response()->json([
            'message' => 'CUV registrada. Avisamos al acreditado, al asesor y a la administración.',
            'cuv_info' => CuvService::payload($exp),
        ]);
    }

    private function encargo(Request $request, int $id): EncargoGestor
    {
        return $request->user()->encargosGestor()->findOrFail($id);
    }
}
