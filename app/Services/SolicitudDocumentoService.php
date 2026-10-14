<?php

namespace App\Services;

use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use App\Notifications\DocumentoSolicitado;

/**
 * Un administrador pide al acreditado un documento que faltaba (aunque ya estén
 * validados los demás). Queda como documento pendiente con la nota y se avisa por push.
 * Lo usan la API de la app y el panel.
 */
class SolicitudDocumentoService
{
    public function solicitar(Expediente $exp, User $admin, string $nota): DocumentoExpediente
    {
        // Varias solicitudes en el mismo expediente: "Documento solicitado", "(2)", "(3)"…
        $existentes = $exp->documentos()->whereNotNull('solicitado_at')->count();
        $nombre     = 'Documento solicitado' . ($existentes > 0 ? ' (' . ($existentes + 1) . ')' : '');

        $doc = DocumentoExpediente::create([
            'expediente_id'  => $exp->id,
            'tipo'           => $nombre,
            'nombre'         => $nombre,
            'seccion'        => 'acreditado',
            'categoria'      => 'acreditada',
            'estado'         => 'pendiente',
            'solicitado_at'  => now(),
            'solicitado_por' => $admin->id,
            'solicitud_nota' => $nota,
        ]);

        $exp->acreditadoRegistrado?->notify(new DocumentoSolicitado($doc, $exp));

        // El asesor también se entera (si no es quien lo pidió)
        if ($exp->asesor && $exp->asesor->id !== $admin->id) {
            $exp->asesor->notify(new DocumentoSolicitado($doc, $exp));
        }

        return $doc;
    }
}
