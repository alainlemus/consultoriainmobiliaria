<?php

namespace App\Support;

use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;

/**
 * JSON de un encargo a gestor para la app: lo usan la API del gestor (sus encargos)
 * y la del super_admin (todos los encargos, solo lectura).
 */
class EncargoGestorPayload
{
    public static function vencido(EncargoGestor $e): bool
    {
        return $e->fecha_entrega !== null
            && $e->fecha_entrega->lt(today())
            && ! in_array($e->estado, [EncargoGestor::TERMINADO, EncargoGestor::CANCELADO], true);
    }

    public static function resumen(EncargoGestor $e): array
    {
        $x = $e->expediente;

        return [
            'id' => $e->id,
            'estado' => $e->estado,
            'estado_label' => $e->estadoLabel(),
            'vuelta' => $e->vuelta,
            'instrucciones' => $e->instrucciones,
            'fecha_entrega' => $e->fecha_entrega?->toDateString(),
            'enviado_at' => $e->created_at?->toIso8601String(),
            'documentos_count' => $e->documentos_count ?? $e->documentos()->count(),
            'subidos_count' => $e->documentos_subidos_count ?? $e->documentosSubidos()->count(),
            'expediente' => [
                'id' => $x->id,
                'folio' => $x->folio,
                'acreditado' => $x->acreditado_nombre,
                'tipo_tramite' => $x->tipoTramite?->nombre,
                'municipio' => trim(implode(', ', array_filter([$x->acreditado_municipio, $x->acreditado_estado]))),
            ],
        ];
    }

    /** Resumen + datos compartidos, documentos revisados, subidos e historial. */
    public static function detalle(EncargoGestor $e): array
    {
        $e->loadMissing(['expediente.tipoTramite:id,nombre', 'documentos', 'documentosSubidos', 'eventos.user:id,name', 'enviadoPor:id,name']);

        return static::resumen($e) + [
            'enviado_por' => $e->enviadoPor?->name,
            // Solo las secciones que la administración compartió (acreditado, vendedor, inmueble, crédito)
            'datos' => $e->datosExpediente(),
            'documentos' => $e->documentos->map(fn (DocumentoExpediente $d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'extension' => strtolower(pathinfo((string) $d->ruta_archivo, PATHINFO_EXTENSION)),
                'revision' => $d->pivot->revision,
                'nota' => $d->pivot->nota,
                'vuelta' => (int) $d->pivot->vuelta,
                // Lo cambió la administración en esta vuelta (para marcarlo "Nuevo")
                'nuevo' => $e->vuelta > 1 && (int) $d->pivot->vuelta === $e->vuelta,
                'revisado_at' => $d->pivot->revisado_at,
            ])->values(),
            'subidos' => $e->documentosSubidos->sortByDesc('updated_at')->map(fn (DocumentoExpediente $d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'notas' => $d->notas,
                'extension' => strtolower(pathinfo((string) $d->ruta_archivo, PATHINFO_EXTENSION)),
                'validacion' => $d->validacion,
                'motivo_rechazo' => $d->motivo_rechazo,
                'subido_at' => $d->updated_at?->toIso8601String(),
            ])->values(),
            'historial' => $e->eventos->map(fn ($ev) => [
                'tipo' => $ev->tipo,
                'detalle' => $ev->detalle,
                'por' => $ev->user?->name,
                'fecha' => $ev->created_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
