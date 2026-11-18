<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;
use App\Models\User;
use App\Support\EncargoGestorPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * /v1/admin/encargos-gestor: el super_admin ve desde la app lo que hacen todos los
 * gestores. Solo lectura; enviar, corregir y cancelar siguen en el panel.
 */
class EncargoGestorController extends Controller
{
    /** GET ?filtro=abiertos|vencidos|terminados|todos &gestor_id= &expediente_id= */
    public function index(Request $request): JsonResponse
    {
        $this->soloSuperAdmin($request);

        $q = EncargoGestor::query()
            ->with(['expediente:id,folio,acreditado_nombre,acreditado_municipio,acreditado_estado,tipo_tramite_id', 'expediente.tipoTramite:id,nombre', 'gestor:id,name'])
            ->withCount(['documentos', 'documentosSubidos'])
            ->when($request->integer('gestor_id'), fn (Builder $q, int $id) => $q->where('gestor_id', $id))
            ->when($request->integer('expediente_id'), fn (Builder $q, int $id) => $q->where('expediente_id', $id))
            ->latest('updated_at');

        match ($request->input('filtro', $request->filled('expediente_id') ? 'todos' : 'abiertos')) {
            'vencidos' => $this->vencidos($q),
            'terminados' => $q->whereIn('estado', [EncargoGestor::TERMINADO, EncargoGestor::CANCELADO]),
            'todos' => null,
            default => $q->activos(),
        };

        return response()->json([
            'data' => $q->get()->map(fn (EncargoGestor $e) => $this->fila($e)),
            'meta' => [
                'vencidos' => $this->vencidos(EncargoGestor::query())->count(),
                'gestores' => User::role('gestor')->orderBy('name')->get(['id', 'name'])
                    ->map(fn (User $u) => ['id' => $u->id, 'nombre' => $u->name])->values(),
            ],
        ]);
    }

    /** GET /{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->soloSuperAdmin($request);
        $e = EncargoGestor::with('gestor:id,name')->findOrFail($id);

        return response()->json(['data' => $this->fila($e) + EncargoGestorPayload::detalle($e)]);
    }

    /** GET /{id}/documentos/{documentoId}/url — URL firmada (5 min) */
    public function urlDocumento(Request $request, int $id, int $documentoId): JsonResponse
    {
        $this->soloSuperAdmin($request);
        $e = EncargoGestor::findOrFail($id);

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

    private function fila(EncargoGestor $e): array
    {
        return EncargoGestorPayload::resumen($e) + [
            'gestor' => $e->gestor ? ['id' => $e->gestor->id, 'nombre' => $e->gestor->name] : null,
            'vencido' => EncargoGestorPayload::vencido($e),
            'updated_at' => $e->updated_at?->toIso8601String(),
        ];
    }

    private function vencidos(Builder $q): Builder
    {
        return $q->activos()->whereNotNull('fecha_entrega')->whereDate('fecha_entrega', '<', today());
    }

    private function soloSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403, 'Solo la administración puede ver esto.');
    }
}
