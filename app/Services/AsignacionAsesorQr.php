<?php

namespace App\Services;

use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Models\Contacto;
use App\Models\User;
use App\Notifications\AcreditadoRegistradoConCodigo;
use App\Notifications\ConflictoAsesorProspecto;

/**
 * Liga acreditados y prospectos al asesor de un QR impreso.
 *
 * Regla: primer toque gana. Un prospecto que ya tenía otro asesor no se pisa:
 * se marca el conflicto (asesor_conflicto_id) y se avisa a los super_admin
 * para que lo resuelvan desde el panel.
 */
class AsignacionAsesorQr
{
    /**
     * Liga la cuenta del acreditado al asesor del QR (al registrarse o después,
     * desde Perfil), aplica al prospecto si ya existe y avisa al asesor.
     */
    public function ligarAcreditado(Acreditado $acreditado, CodigoQr $qr, string $origen = 'codigo_manual'): void
    {
        $acreditado->forceFill([
            'asesor_id'     => $qr->asesor_id,
            'codigo_qr_id'  => $qr->id,
            'asesor_origen' => $origen,
        ])->save();

        if ($acreditado->contacto) {
            $this->aplicarAContacto($acreditado->contacto, $acreditado);
        }

        $qr->asesor?->notify(new AcreditadoRegistradoConCodigo($acreditado->fresh(['contacto', 'codigoQr'])));
    }

    /**
     * Datos de asesor que debe llevar un prospecto NUEVO creado por este acreditado.
     * @return array<string, mixed>
     */
    public function datosParaContactoNuevo(Acreditado $acreditado): array
    {
        if (! $acreditado->asesor_id) return [];

        return [
            'asesor_id'    => $acreditado->asesor_id,
            'codigo_qr_id' => $acreditado->codigo_qr_id,
        ];
    }

    /**
     * Aplica el asesor del acreditado a un prospecto que YA existía.
     * Sin asesor: se asigna. Con otro asesor: se marca conflicto y se avisa.
     */
    public function aplicarAContacto(Contacto $contacto, Acreditado $acreditado): void
    {
        if (! $acreditado->asesor_id) return;

        if (! $contacto->asesor_id) {
            $contacto->update([
                'asesor_id'    => $acreditado->asesor_id,
                'codigo_qr_id' => $acreditado->codigo_qr_id,
            ]);
            return;
        }

        if ((int) $contacto->asesor_id === (int) $acreditado->asesor_id) return;

        // Ya tenía otro asesor: no pisar, marcar conflicto una sola vez.
        if ((int) $contacto->asesor_conflicto_id === (int) $acreditado->asesor_id) return;

        $nombreAsesorQr = $acreditado->asesor?->name ?? 'otro asesor';
        $codigo         = $acreditado->codigoQr?->codigo ?? '—';
        $nota           = "⚠ Se registró en la app con el código QR {$codigo} de {$nombreAsesorQr}, pero ya tenía asesor asignado. Revisar a quién corresponde.";

        $contacto->update([
            'asesor_conflicto_id'    => $acreditado->asesor_id,
            'conflicto_detectado_at' => now(),
            'notas'                  => trim(($contacto->notas ? $contacto->notas . "\n\n" : '') . $nota),
        ]);

        User::role('super_admin')->get()
            ->each(fn (User $admin) => $admin->notify(new ConflictoAsesorProspecto($contacto->fresh(['asesor', 'asesorConflicto']))));
    }

    /**
     * Resuelve el conflicto desde el panel: 'mantener' deja al asesor actual,
     * 'cambiar' pasa el prospecto (y la cuenta del acreditado) al asesor del QR.
     */
    public function resolverConflicto(Contacto $contacto, string $decision): void
    {
        $asesorQr = $contacto->asesor_conflicto_id;

        if ($decision === 'cambiar' && $asesorQr) {
            $contacto->update(['asesor_id' => $asesorQr]);
        } elseif ($asesorQr) {
            // Mantener: la cuenta del acreditado se alinea con el asesor que sí lo atiende.
            Acreditado::where('contacto_id', $contacto->id)->where('asesor_id', $asesorQr)
                ->update(['asesor_id' => $contacto->asesor_id]);
        }

        $contacto->update(['asesor_conflicto_id' => null, 'conflicto_detectado_at' => null]);
    }
}
