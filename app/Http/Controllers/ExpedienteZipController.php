<?php

namespace App\Http\Controllers;

use App\Models\Expediente;
use App\Services\CarpetaExpedienteService;
use Illuminate\Http\Request;

class ExpedienteZipController extends Controller
{
    /**
     * Genera y descarga un ZIP con la estructura de carpetas del expediente.
     *
     * Estructura del ZIP:
     *   NOMBRE_ACREDITADO/
     *     ACREDITADA/
     *       curp.pdf
     *       ine.pdf
     *       ...
     *     VENDEDOR/
     *       ine.pdf
     *       ...
     *     VIVIENDA/
     *       escritura.pdf
     *       ...
     *     SOFOM/
     *       ACREDITADA/
     *         curp.pdf
     *       VENDEDOR/
     *         cuenta.pdf
     *       VIVIENDA/
     *         avaluo.pdf
     *     CATASTRO/
     *       ...
     *     NOTARIA/
     *       ...
     */
    public function descargar(Request $request, int $expedienteId)
    {
        $expediente = Expediente::findOrFail($expedienteId);

        // Autorización: solo el asesor dueño o super_admin
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super_admin') ||
            $user?->hasRole('admin') ||
            $expediente->asesor_id === $user?->id ||
            $user?->can('ViewAny:Expediente'),
            403
        );

        $zip = app(CarpetaExpedienteService::class)->crearZip($expediente);

        if (! $zip) {
            abort(404, 'Este expediente no tiene documentos para descargar.');
        }

        return $this->enviarZip($zip);
    }

    /**
     * Descarga con enlace firmado (el que llega por correo al administrador desde la app).
     */
    public function descargarFirmado(Request $request, int $expedienteId)
    {
        $expediente = Expediente::findOrFail($expedienteId);
        $zip = app(CarpetaExpedienteService::class)->crearZip($expediente);

        abort_unless($zip, 404, 'Este expediente no tiene documentos para descargar.');

        return $this->enviarZip($zip);
    }

    private function enviarZip(array $zip)
    {
        return response()->download($zip['ruta'], $zip['nombre'], [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $zip['nombre'] . '"',
        ])->deleteFileAfterSend(true);
    }
}
