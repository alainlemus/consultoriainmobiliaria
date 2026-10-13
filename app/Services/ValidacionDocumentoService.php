<?php

namespace App\Services;

use App\Models\DocumentoExpediente;
use App\Models\User;
use App\Notifications\DocumentoRechazado;
use App\Notifications\DocumentoValidado;
use App\Notifications\ExpedienteCompleto;

/**
 * Validar o rechazar un documento (solo administradores) y avisar al asesor.
 * Lo usan la API de la app y el panel, para que el comportamiento sea el mismo.
 */
class ValidacionDocumentoService
{
    public function validar(DocumentoExpediente $doc, User $admin): void
    {
        $yaValidado = $doc->validado_at !== null;

        $doc->update([
            'estado'         => 'recibido',
            'validado_at'    => now(),
            'validado_por'   => $admin->id,
            'motivo_rechazo' => null,
        ]);

        if ($yaValidado) {
            return; // revalidar no vuelve a avisar
        }

        $exp    = $doc->expediente;
        $asesor = $exp?->asesor;

        if (! $asesor) {
            return;
        }

        $asesor->notify(new DocumentoValidado($doc, $exp));

        // Si este era el último por validar, el expediente queda completo
        if ($exp->estaCompleto()) {
            $asesor->notify(new ExpedienteCompleto($exp));
        }
    }

    public function rechazar(DocumentoExpediente $doc, string $motivo): void
    {
        $doc->update([
            'estado'         => 'rechazado',
            'motivo_rechazo' => $motivo,
            'validado_at'    => null,
            'validado_por'   => null,
        ]);

        $exp = $doc->expediente;

        // El asesor y, si usa la app, el acreditado reciben el motivo
        $exp?->asesor?->notify(new DocumentoRechazado($doc, $exp, $motivo));
        $exp?->acreditadoRegistrado?->notify(new DocumentoRechazado($doc, $exp, $motivo));
    }
}
