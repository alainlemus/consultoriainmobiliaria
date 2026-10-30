<?php

namespace App\Http\Controllers;

use App\Models\ContratoGenerado;
use App\Models\Expediente;
use App\Services\PagareService;
use App\Services\RegresionFasesService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pagaré del contrato (formato Word del cliente) en PDF o Word, con los datos del
 * acreditado y del aval del expediente o del contrato generado desde la app; y el oficio
 * de regresión de fases del contrato generado.
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
            avalNombre: $exp->obligado_solidario_nombre,
        );

        return $this->responder($datos, "pagare-{$exp->folio}");
    }

    /** GET /admin/pagare/contrato-generado/{contrato}?formato=word|pdf&preview=1 */
    public function contratoGenerado(ContratoGenerado $contrato): Response|BinaryFileResponse
    {
        abort_unless(auth()->user()?->hasRole('super_admin') || auth()->user()?->can('View:ContratoGenerado'), 403);

        $datos = $this->pagare->datosDeContrato($contrato);

        return $this->responder($datos, 'pagare-' . ($contrato->folio ?: $contrato->id));
    }

    /** GET /admin/pagare/contrato-generado/{contrato}/regresion-fases?preview=1 */
    public function regresionFases(ContratoGenerado $contrato, RegresionFasesService $oficio): Response
    {
        abort_unless(auth()->user()?->hasRole('super_admin') || auth()->user()?->can('View:ContratoGenerado'), 403);

        $pdf    = $oficio->pdf($contrato);
        $nombre = 'regresion-fases-' . ($contrato->folio ?: $contrato->id) . '.pdf';

        return request()->boolean('preview') ? $pdf->stream($nombre) : $pdf->download($nombre);
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
