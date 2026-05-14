<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ContratosController extends Controller
{
    private function getExpediente(int $id, bool $soloAdmin = false): Expediente
    {
        $user = auth()->user();

        if ($soloAdmin) {
            abort_unless($user?->hasRole('super_admin'), 403);
        } else {
            abort_unless($user?->hasAnyRole(['super_admin', 'asesor']), 403);
        }

        $expediente = Expediente::with(['asesor', 'tipoTramite', 'contacto'])
            ->findOrFail($id);

        // Asesores solo pueden ver sus propios expedientes
        if ($user->hasRole('asesor') && $expediente->asesor_id !== $user->id) {
            abort(403);
        }

        return $expediente;
    }

    public function prestacionServicios(int $expediente): Response
    {
        $exp = $this->getExpediente($expediente);

        $pdf = Pdf::loadView('contratos.prestacion_servicios', ['expediente' => $exp])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = "contrato-servicios-{$exp->folio}.pdf";

        return request()->boolean('preview')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    public function convenioHonorarios(int $expediente): Response
    {
        // Restringido exclusivamente a super_admin
        $exp = $this->getExpediente($expediente, soloAdmin: true);

        $pdf = Pdf::loadView('contratos.convenio_honorarios', ['expediente' => $exp])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = "convenio-honorarios-{$exp->folio}.pdf";

        return request()->boolean('preview')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }
}
