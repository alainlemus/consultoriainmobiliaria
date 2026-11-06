<?php

namespace App\Http\Controllers\Api\V1\Gestor;

use App\Http\Controllers\Controller;
use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;
use App\Services\EncargoGestorService;
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
            'todos'      => null,
            default      => $q->activos(),
        };

        return response()->json(['data' => $q->get()->map(fn (EncargoGestor $e) => $this->resumen($e))]);
    }

    /** GET /gestor/encargos/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $e = $this->encargo($request, $id);
        $e->load(['expediente.tipoTramite:id,nombre', 'documentos', 'documentosSubidos', 'eventos.user:id,name', 'enviadoPor:id,name']);
        return response()->json(['data' => $this->resumen($e) + [
            'enviado_por' => $e->enviadoPor?->name,
            // Solo las secciones que la administración compartió (acreditado, vendedor, inmueble, crédito)
            'datos'       => $e->datosExpediente(),
            'documentos' => $e->documentos->map(fn (DocumentoExpediente $d) => [
                'id'          => $d->id,
                'nombre'      => $d->nombre,
                'extension'   => strtolower(pathinfo((string) $d->ruta_archivo, PATHINFO_EXTENSION)),
                'revision'    => $d->pivot->revision,
                'nota'        => $d->pivot->nota,
                'vuelta'      => (int) $d->pivot->vuelta,
                // Lo cambió la administración en esta vuelta (para marcarlo "Nuevo")
                'nuevo'       => $e->vuelta > 1 && (int) $d->pivot->vuelta === $e->vuelta,
                'revisado_at' => $d->pivot->revisado_at,
            ])->values(),
            'subidos' => $e->documentosSubidos->sortByDesc('updated_at')->map(fn (DocumentoExpediente $d) => [
                'id'             => $d->id,
                'nombre'         => $d->nombre,
                'notas'          => $d->notas,
                'extension'      => strtolower(pathinfo((string) $d->ruta_archivo, PATHINFO_EXTENSION)),
                'validacion'     => $d->validacion,
                'motivo_rechazo' => $d->motivo_rechazo,
                'subido_at'      => $d->updated_at?->toIso8601String(),
            ])->values(),
            'historial' => $e->eventos->map(fn ($ev) => [
                'tipo'    => $ev->tipo,
                'detalle' => $ev->detalle,
                'por'     => $ev->user?->name,
                'fecha'   => $ev->created_at?->toIso8601String(),
            ])->values(),
        ]]);
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
            'documentoId'  => $doc->id,
        ]);

        return response()->json(['url' => $url, 'expira_en' => 300]);
    }

    /** POST /gestor/encargos/{id}/revision  { revisiones: [{documento_id, revision: correcto|incorrecto, nota}] } */
    public function revision(Request $request, int $id): JsonResponse
    {
        $e = $this->encargo($request, $id);
        $data = $request->validate([
            'revisiones'                => ['required', 'array', 'min:1'],
            'revisiones.*.documento_id' => ['required', 'integer'],
            'revisiones.*.revision'     => ['required', 'in:correcto,incorrecto'],
            'revisiones.*.nota'         => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->enviarRevision($e, $request->user(), $data['revisiones']);

        return $this->show($request, $id);
    }

    /** POST /gestor/encargos/{id}/documentos  (multipart: archivo, tipo, notas?, reemplaza_documento_id?) */
    public function subir(Request $request, int $id): JsonResponse
    {
        $e = $this->encargo($request, $id);
        $data = $request->validate([
            'archivo'                => ['required', 'file', 'max:20480', 'mimes:' . EncargoGestorService::MIMES],
            'tipo'                   => ['required', 'string', 'max:120'],
            'notas'                  => ['nullable', 'string', 'max:500'],
            'reemplaza_documento_id' => ['nullable', 'integer'],
        ], [
            'archivo.mimes' => 'Sube un PDF, un Word (.doc/.docx) o una imagen.',
            'archivo.max'   => 'El archivo no puede pesar más de 20 MB.',
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

    private function encargo(Request $request, int $id): EncargoGestor
    {
        return $request->user()->encargosGestor()->findOrFail($id);
    }

    private function resumen(EncargoGestor $e): array
    {
        $x = $e->expediente;

        return [
            'id'                => $e->id,
            'estado'            => $e->estado,
            'estado_label'      => $e->estadoLabel(),
            'vuelta'            => $e->vuelta,
            'instrucciones'     => $e->instrucciones,
            'fecha_entrega'     => $e->fecha_entrega?->toDateString(),
            'enviado_at'        => $e->created_at?->toIso8601String(),
            'documentos_count'  => $e->documentos_count ?? $e->documentos()->count(),
            'subidos_count'     => $e->documentos_subidos_count ?? $e->documentosSubidos()->count(),
            'expediente'        => [
                'id'          => $x->id,
                'folio'       => $x->folio,
                'acreditado'  => $x->acreditado_nombre,
                'tipo_tramite'=> $x->tipoTramite?->nombre,
                'municipio'   => trim(implode(', ', array_filter([$x->acreditado_municipio, $x->acreditado_estado]))),
            ],
        ];
    }
}
