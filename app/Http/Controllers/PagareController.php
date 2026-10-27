<?php

namespace App\Http\Controllers;

use App\Models\ContratoGenerado;
use App\Models\Expediente;
use App\Services\PagareService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pagaré del contrato (formato Word del cliente) en PDF o Word, con los datos del
 * acreditado del expediente o del contrato generado desde la app.
 */
class PagareController extends Controller
{
    public function __construct(private PagareService $pagare) {}

    /** GET /admin/pagare/expediente/{expediente}?formato=word|pdf&preview=1 */
    public function expediente(int $expediente): Response|BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless($user?->hasAnyRole(['super_admin', 'asesor']), 403);

        $exp = Expediente::findOrFail($expediente);
        abort_if($user->hasRole('asesor') && $exp->asesor_id !== $user->id, 403);

        $datos = $this->pagare->datos(
            $exp->acreditado_nombre,
            $this->pagare->domicilioDe($exp),
            $exp->acreditado_rfc,
        );

        return $this->responder($datos, "pagare-{$exp->folio}");
    }

    /** GET /admin/pagare/contrato-generado/{contrato}?formato=word|pdf&preview=1 */
    public function contratoGenerado(ContratoGenerado $contrato): Response|BinaryFileResponse
    {
        abort_unless(auth()->user()?->hasRole('super_admin') || auth()->user()?->can('View:ContratoGenerado'), 403);

        $datos = $this->pagare->datos(
            $contrato->acreditado_nombre,
            $contrato->acreditado_domicilio,
            $contrato->acreditado_rfc,
            $contrato->created_at,
        );

        return $this->responder($datos, 'pagare-' . ($contrato->folio ?: $contrato->id));
    }

    private function responder(array $datos, string $nombre): Response|BinaryFileResponse
    {
        if (request('formato') === 'word') {
            return response()->download($this->pagare->docx($datos), "{$nombre}.docx")->deleteFileAfterSend(true);
        }

        $pdf = $this->pagare->pdf($datos);

        return request()->boolean('preview') ? $pdf->stream("{$nombre}.pdf") : $pdf->download("{$nombre}.pdf");
    }
}
