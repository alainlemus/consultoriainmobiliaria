<?php

namespace App\Observers;

use App\Models\Expediente;
use App\Models\Comision;
use App\Models\User;
use App\Notifications\EtapaExpedienteCambiada;
use App\Notifications\ExpedienteCerrado;
use App\Notifications\NuevoExpedienteCreado;
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

    public function created(Expediente $exp): void
    {
        // Notificar a super_admins cuando se crea un expediente
        // Usamos una flag en caché de la sesión para evitar doble disparo en SQLite
        $key = 'expediente_notificado_' . $exp->id;
        if (cache()->has($key)) return;
        cache()->put($key, true, now()->addSeconds(5));

        User::role('super_admin')->get()->each(
            fn ($admin) => $admin->notify(new NuevoExpedienteCreado($exp))
        );
    }

    public function updated(Expediente $exp): void
    {
        $this->generarComisionAlCerrar($exp);
        $this->notificarAlCerrar($exp);
        $this->notificarCambioEtapa($exp);
        $this->notificarPagoRecibido($exp);
    }

    public function saved(Expediente $exp): void
    {
        // saved se llama tanto en create como en update.
        // La lógica de notificaciones/comisiones solo aplica en updates.
        // Para creates, el observer created() ya lo maneja.
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

    private function generarComisionAlCerrar(Expediente $exp): void
    {
        // Solo cuando acaba de cambiar a "cerrado" y tiene honorarios
        if (! $exp->wasChanged('estado') || $exp->estado !== 'cerrado') {
            return;
        }

        if (empty($exp->honorarios_monto) || $exp->honorarios_monto <= 0) {
            return;
        }

        // No duplicar si ya existe una comisión para este expediente
        if ($exp->comision()->exists()) {
            return;
        }

        Comision::create([
            'expediente_id'       => $exp->id,
            'asesor_id'           => $exp->asesor_id,
            'monto_base'          => $exp->monto_total_estimado ?? $exp->honorarios_monto,
            'porcentaje_comision' => $exp->honorarios_porcentaje ?? 0,
            'monto_comision'      => $exp->honorarios_monto,
            'estado'              => 'pendiente',
            'fecha_generacion'    => now()->toDateString(),
        ]);
    }

    private function notificarAlCerrar(Expediente $exp): void
    {
        if (
            $exp->wasChanged('estado') &&
            $exp->estado === 'cerrado' &&
            $exp->getOriginal('estado') !== 'cerrado' &&
            ! empty($exp->honorarios_monto) &&
            $exp->honorarios_monto > 0
        ) {
            // Flag anti-duplicado: evitar doble disparo en SQLite tests
            $key = 'cierre_notificado_' . $exp->id;
            if (cache()->has($key)) return;
            cache()->put($key, true, now()->addSeconds(5));

            $asesor = $exp->asesor;
            if ($asesor) {
                $asesor->notify(new ExpedienteCerrado($exp));
            }
        }
    }

    private function notificarCambioEtapa(Expediente $exp): void
    {
        if ($exp->wasChanged('etapa_tramite_id') && $exp->etapa_tramite_id) {
            $etapaAnterior = \App\Models\EtapaTramite::find($exp->getOriginal('etapa_tramite_id'))?->nombre ?? '—';
            $etapaNueva    = $exp->etapa?->nombre ?? '—';

            $asesor = $exp->asesor;
            if ($asesor) {
                $asesor->notify(new EtapaExpedienteCambiada($exp, $etapaAnterior, $etapaNueva));
            }

            // El acreditado (si ya vinculó su cuenta de la app) también se
            // entera del avance — solo push, ver EtapaExpedienteCambiada::via()
            if ($exp->acreditadoRegistrado) {
                $exp->acreditadoRegistrado->notify(new EtapaExpedienteCambiada($exp, $etapaAnterior, $etapaNueva));
            }
        }
    }

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
