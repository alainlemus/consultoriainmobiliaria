<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DocumentoRequerido;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpedienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Expediente::with([
            'contacto:id,nombre,foto',   // foto incluida para que el accessor foto_url funcione
            'tipoTramite:id,nombre',
            'etapa:id,nombre',
        ]);

        if (! $user->hasRole('super_admin')) {
            $query->where('asesor_id', $user->id);
        }

        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $result  = $query->latest()->paginate($perPage);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'total'        => $result->total(),
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
                'per_page'     => $result->perPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $exp = $this->findForUser($request, $id);

        $exp->load([
            'contacto',   // modelo completo — incluye accessors foto_url y simulador_screenshot_url
            'tipoTramite:id,nombre',
            'etapa:id,nombre',
            'documentos',
        ]);

        // Documentos ya subidos al expediente
        // Clave compuesta seccion|tipo para evitar colisiones entre secciones
        $docSubidos = $exp->documentos->map(fn ($d) => [
            ...$d->toArray(),
            'tipo_documento' => $d->tipo,
            'url'            => null,
            'tiene_archivo'  => (bool) $d->ruta_archivo,
        ])->keyBy(fn ($d) => ($d['seccion'] ?? '') . '|' . $d['tipo_documento']);

        // Checklist completo del tipo de trámite (documentos requeridos)
        $requeridos = DocumentoRequerido::where('tipo_tramite_id', $exp->tipo_tramite_id)
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get();

        // Mezclar: para cada requerido, si ya existe un doc subido se usa ese, si no se crea entrada vacía
        $checklist = $requeridos->map(function ($req) use ($docSubidos) {
            // Buscar por clave exacta seccion|tipo, con fallback a solo tipo (uploads legacy sin seccion)
            $subido = $docSubidos->get($req->seccion . '|' . $req->nombre)
                   ?? $docSubidos->get('|' . $req->nombre);
            if ($subido) {
                return array_merge($subido, ['seccion' => $req->seccion, 'orden' => $req->orden, 'obligatorio' => $req->obligatorio]);
            }
            return [
                'id'             => null,
                'tipo'           => $req->nombre,
                'tipo_documento' => $req->nombre,
                'seccion'        => $req->seccion,
                'orden'          => $req->orden,
                'obligatorio'    => $req->obligatorio,
                'descripcion'    => $req->descripcion,
                'estado'         => 'pendiente',
                'tiene_archivo'  => false,
                'url'            => null,
                'ruta_archivo'   => null,
            ];
        });

        // Documentos subidos que no están en el checklist (tipos personalizados / legacy sin sección)
        $tiposRequeridos = $requeridos->map(fn ($r) => $r->seccion . '|' . $r->nombre)->toArray();
        $extrasSubidos   = $exp->documentos
            ->filter(fn ($d) => ! in_array(($d->seccion ?? '') . '|' . $d->tipo, $tiposRequeridos))
            ->map(fn ($d) => [
                ...$d->toArray(),
                'tipo_documento' => $d->tipo,
                'seccion'        => 'otros',
                'orden'          => 99,
                'obligatorio'    => false,
                'url'            => null,
                'tiene_archivo'  => (bool) $d->ruta_archivo,
            ]);

        $data = $exp->toArray();
        $data['documentos']         = $checklist->values()->concat($extrasSubidos->values())->all();
        $data['documentos_requeridos_total']   = $requeridos->count();
        $data['documentos_subidos_total']      = $exp->documentos->where('ruta_archivo', '!=', null)->count();
        $data['documentos_pendientes_total']   = $checklist->filter(fn ($d) => ! $d['tiene_archivo'])->count();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contacto_id'      => ['required', 'exists:contactos,id'],
            'tipo_tramite_id'  => ['required', 'exists:tipo_tramites,id'],
            'etapa_tramite_id' => ['nullable', 'exists:etapa_tramites,id'],
            'estado'           => ['nullable', 'in:en_proceso,documentacion,autorizado,escrituracion,cerrado,cancelado'],
            'monto_credito'    => ['nullable', 'numeric', 'min:0'],
            'honorarios_monto' => ['nullable', 'numeric', 'min:0'],
            'notas_internas'   => ['nullable', 'string'],
        ]);

        $exp = Expediente::create([
            ...$data,
            'asesor_id'        => $request->user()->id,
            'estado'           => $data['estado'] ?? 'en_proceso',
            'etapa_tramite_id' => $data['etapa_tramite_id']
                ?? EtapaTramite::where('tipo_tramite_id', $data['tipo_tramite_id'])->orderBy('orden')->value('id'),
            // Si no se envía acreditado_nombre, se toma del contacto
            'acreditado_nombre' => $data['acreditado_nombre']
                ?? \App\Models\Contacto::find($data['contacto_id'])?->nombre,
        ]);

        return response()->json(['data' => $exp->load(['tipoTramite:id,nombre', 'contacto:id,nombre'])], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $exp = $this->findForUser($request, $id);

        $data = $request->validate([
            'estado'           => ['nullable', 'in:en_proceso,documentacion,autorizado,escrituracion,cerrado,cancelado'],
            'etapa_tramite_id' => ['nullable', 'exists:etapa_tramites,id'],
            'monto_credito'    => ['nullable', 'numeric', 'min:0'],
            'honorarios_monto' => ['nullable', 'numeric', 'min:0'],
            'notas_internas'   => ['nullable', 'string'],
        ]);

        $exp->update($data);

        return response()->json(['data' => $exp->fresh()]);
    }

    private function findForUser(Request $request, int $id): Expediente
    {
        $user = $request->user();
        $exp  = Expediente::findOrFail($id);

        if (! $user->hasRole('super_admin') && $exp->asesor_id !== $user->id) {
            abort(403, 'No tienes permiso para acceder a este expediente.');
        }

        return $exp;
    }
}
