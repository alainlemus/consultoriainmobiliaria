<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ContratosController extends Controller
{
    private function getExpediente(int $id): Expediente
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['super_admin', 'asesor']),
            403
        );

        $expediente = Expediente::with(['asesor', 'tipoTramite', 'contacto'])
            ->findOrFail($id);

        // Asesores solo pueden ver sus propios expedientes
        if (auth()->user()->hasRole('asesor') && $expediente->asesor_id !== auth()->id()) {
            abort(403);
        }

        return $expediente;
    }

    public function cartaMandato(int $expediente): Response
    {
        $exp = $this->getExpediente($expediente);

        $pdf = Pdf::loadView('contratos.carta_mandato', ['expediente' => $exp])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = "carta-mandato-{$exp->folio}.pdf";

        return $pdf->download($filename);
    }

    public function prestacionServicios(int $expediente): Response
    {
        $exp = $this->getExpediente($expediente);

        $pdf = Pdf::loadView('contratos.prestacion_servicios', ['expediente' => $exp])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = "contrato-servicios-{$exp->folio}.pdf";

        return $pdf->download($filename);
    }

    public function convenioHonorarios(int $expediente): Response
    {
        $exp = $this->getExpediente($expediente);

        $pdf = Pdf::loadView('contratos.convenio_honorarios', ['expediente' => $exp])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = "convenio-honorarios-{$exp->folio}.pdf";

        return $pdf->download($filename);
    }
}
