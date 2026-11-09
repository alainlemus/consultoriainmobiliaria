<?php

namespace App\Services;

use App\Models\Expediente;
use App\Models\User;
use App\Notifications\AvisoTramiteAcreditado;
use App\Notifications\SeguimientoActualizado;
use App\Support\SeguimientoExpediente as S;

/**
 * Mueve el seguimiento del expediente y avisa al acreditado (push + bandeja)
 * y al asesor. Solo avanza; retrocede únicamente a «Validación de documentos»
 * cuando se rechaza un documento ya validado antes de llegar a valuación.
 */
class SeguimientoExpedienteService
{
    public function avanzar(Expediente $exp, string $paso, ?User $por = null): bool
    {
        if (! S::esValido($paso)) return false;
        if ($exp->seguimiento === $paso) return false;
        if (S::orden($paso) < S::orden((string) $exp->seguimiento)) return false;

        return $this->mover($exp, $paso, $por);
    }

    /** Documento rechazado: si ya había pasado la validación (y no llegó a valuación), regresa. */
    public function regresarAValidacion(Expediente $exp): bool
    {
        if (! in_array($exp->seguimiento, [S::TRAMITES_EN_GESTION, S::TRAMITES_GESTIONADOS], true)) return false;

        return $this->mover($exp, S::VALIDACION_DOCUMENTOS, null, 'Se rechazó un documento. Revisa la nota y vuelve a subirlo desde la app.');
    }

    /** Tras validar un documento: si ya está todo validado, pasa a «Trámites en gestión». */
    public function recalcularTrasValidacion(Expediente $exp, ?User $por = null): void
    {
        if ($exp->seguimiento === S::VALIDACION_DOCUMENTOS && $exp->estaCompleto()) {
            $this->avanzar($exp, S::TRAMITES_EN_GESTION, $por);
        }
    }

    /** Tras cerrar un encargo: si todos los encargos del expediente terminaron, «Trámites gestionados». */
    public function recalcularTrasEncargos(Expediente $exp, ?User $por = null): void
    {
        if ($exp->seguimiento !== S::TRAMITES_EN_GESTION) return;

        $encargos = $exp->encargosGestor()->get();
        $activos  = $encargos->whereNotIn('estado', ['terminado', 'cancelado']);
        if ($encargos->where('estado', 'terminado')->isNotEmpty() && $activos->isEmpty()) {
            $this->avanzar($exp, S::TRAMITES_GESTIONADOS, $por);
        }
    }

    /**
     * Paso efectivo: si todo está validado pero el seguimiento no avanzó (p. ej.
     * validaciones hechas fuera del servicio), se muestra «Trámites en gestión».
     */
    public static function pasoEfectivo(Expediente $exp): string
    {
        $paso = (string) ($exp->seguimiento ?: S::VALIDACION_DOCUMENTOS);
        if ($paso === S::VALIDACION_DOCUMENTOS && $exp->estaCompleto()) {
            return S::TRAMITES_EN_GESTION;
        }
        return $paso;
    }

    /** Lo que ven la app del acreditado y la del asesor. */
    public static function payload(Expediente $exp): array
    {
        $paso = self::pasoEfectivo($exp);

        return [
            'paso'    => $paso,
            'nombre'  => S::nombre($paso),
            'orden'   => S::orden($paso),
            'total'   => S::total(),
            'mensaje' => S::mensaje($paso),
            'fecha'   => $exp->seguimiento_at?->toIso8601String(),
            'pasos'   => collect(S::PASOS)->map(fn ($p, $k) => ['paso' => $k, 'nombre' => $p['nombre'], 'orden' => S::orden($k)])->values()->all(),
        ];
    }

    private function mover(Expediente $exp, string $paso, ?User $por, ?string $mensajeAcreditado = null): bool
    {
        $anterior = (string) $exp->seguimiento;

        Expediente::withoutEvents(fn () => $exp->forceFill(['seguimiento' => $paso, 'seguimiento_at' => now()])->save());

        $titulo  = S::nombre($paso);
        $mensaje = $mensajeAcreditado ?? S::mensaje($paso);

        $exp->loadMissing(['acreditadoRegistrado', 'asesor']);
        $exp->acreditadoRegistrado?->notify(new AvisoTramiteAcreditado($exp, "📋 {$titulo}", $mensaje, 'tramite'));
        $exp->asesor?->notify(new SeguimientoActualizado($exp, S::nombre($anterior), $titulo));

        return true;
    }
}
