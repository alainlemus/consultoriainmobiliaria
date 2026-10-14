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
    /**
     * @param string|null $para 'acreditado' o 'asesor'. Sin indicar: el acreditado si usa la app
     *                          (ya está registrado en el expediente); si no, el asesor.
     */
    public function solicitar(Expediente $exp, User $admin, string $nota, ?string $para = null): DocumentoExpediente
    {
        $para = in_array($para, ['acreditado', 'asesor'], true) ? $para : ($exp->acreditadoRegistrado ? 'acreditado' : 'asesor');
        // Sin acreditado en la app solo se le puede pedir al asesor
        if ($para === 'acreditado' && ! $exp->acreditadoRegistrado) {
            $para = 'asesor';
        }

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
            'solicitado_a'   => $para,
        ]);

        if ($para === 'acreditado') {
            $exp->acreditadoRegistrado?->notify(new DocumentoSolicitado($doc, $exp));
        }

        // El asesor siempre se entera (es quien lo debe subir, o da seguimiento al acreditado)
        if ($exp->asesor && $exp->asesor->id !== $admin->id) {
            $exp->asesor->notify(new DocumentoSolicitado($doc, $exp));
        }

        return $doc;
    }
}
