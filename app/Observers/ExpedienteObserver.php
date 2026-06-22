<?php

namespace App\Observers;

use App\Models\Expediente;
use App\Notifications\PagoExpedienteRecibido;
use App\Support\DiasHabiles;
use Illuminate\Support\Facades\Storage;

/**
 * Observer de Expediente.
 *
 * Calcula automáticamente las fechas derivadas del proceso FOVISSSTE
 * para que el asesor no tenga que contarlas manualmente:
 *
 * - fecha_limite_firma       = clg_fecha_solicitud + 30 días hábiles (paso 19)
 * - fecha_esperada_pago      = fecha_envio_guarda_valores + 20 días hábiles (paso 20)
 *
 * También:
 * - Genera la contraseña del portal FOVISSSTE desde el CURP (paso 4-J)
 * - Notifica al asesor y cierra el expediente cuando pago_recibido = true (paso 20)
 */
class ExpedienteObserver
{
    public function saving(Expediente $exp): void
    {
        $this->calcularFechaLimiteFirma($exp);
        $this->calcularFechaEsperadaPago($exp);
        $this->cerrarAlRecibirPago($exp);
    }

    public function saved(Expediente $exp): void
    {
        $this->notificarPagoRecibido($exp);
    }

    // ── Borrar archivos físicos al eliminar el expediente ────────────────────

    public function deleted(Expediente $exp): void
    {
        // Borrar cada archivo vinculado en documentos_expediente
        $exp->documentos()->each(function ($doc) {
            if ($doc->ruta_archivo && Storage::disk('local')->exists($doc->ruta_archivo)) {
                Storage::disk('local')->delete($doc->ruta_archivo);
            }
        });

        // Borrar la carpeta completa del expediente en disco
        $carpeta = "expedientes/{$exp->id}";
        if (Storage::disk('local')->exists($carpeta)) {
            Storage::disk('local')->deleteDirectory($carpeta);
        }
    }

    // ── fecha_limite_firma: clg_fecha_solicitud + 30 días hábiles ────────────

    private function calcularFechaLimiteFirma(Expediente $exp): void
    {
        if (
            $exp->clg_fecha_solicitud &&
            $exp->isDirty(['clg_fecha_solicitud', 'clg_solicitado']) &&
            ! $exp->fecha_firma
        ) {
            $exp->fecha_limite_firma = DiasHabiles::agregar(
                \Carbon\Carbon::parse($exp->clg_fecha_solicitud),
                30
            )->toDateString();
        }
    }

    // ── fecha_esperada_pago: fecha_envio_guarda_valores + 20 días hábiles ────

    private function calcularFechaEsperadaPago(Expediente $exp): void
    {
        if (
            $exp->fecha_envio_guarda_valores &&
            $exp->isDirty('fecha_envio_guarda_valores') &&
            ! $exp->pago_recibido
        ) {
            $exp->fecha_esperada_pago = DiasHabiles::agregar(
                \Carbon\Carbon::parse($exp->fecha_envio_guarda_valores),
                20
            )->toDateString();
        }
    }

    // ── Cerrar expediente al marcar pago_recibido ─────────────────────────────

    private function cerrarAlRecibirPago(Expediente $exp): void
    {
        if ($exp->isDirty('pago_recibido') && $exp->pago_recibido) {
            // Auto-rellenar fecha si no se ingresó
            if (empty($exp->fecha_pago_recibido)) {
                $exp->fecha_pago_recibido = now()->toDateString();
            }
            // Cambiar estado a cerrado automáticamente
            if ($exp->estado !== 'cerrado') {
                $exp->estado = 'cerrado';
            }
        }
    }

    // ── Notificar al asesor (después de guardar para tener el id) ─────────────

    private function notificarPagoRecibido(Expediente $exp): void
    {
        // Solo si acaba de marcar pago_recibido = true en este save
        if ($exp->wasChanged('pago_recibido') && $exp->pago_recibido) {
            $asesor = $exp->asesor;
            if ($asesor) {
                $asesor->notify(new PagoExpedienteRecibido($exp));
            }
        }
    }
}
