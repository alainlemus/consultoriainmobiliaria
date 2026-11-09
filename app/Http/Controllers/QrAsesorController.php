<?php

namespace App\Http\Controllers;

use App\Models\CodigoQr;
use App\Models\EscaneoQr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR por asesor para publicidad impresa.
 *
 *  - GET /a/{codigo}: registra el escaneo y manda al formulario de prospecto
 *    (/qr/{codigo}) con el asesor ya puesto. Si el código no existe o está
 *    pausado, va a la página pública de descarga.
 *  - Descargas (panel, autenticado): PNG y SVG listos para imprenta y un PDF
 *    con el código en texto grande por si no pueden escanear.
 */
class QrAsesorController extends Controller
{
    public function redirigir(Request $request, string $codigo): RedirectResponse
    {
        $qr = CodigoQr::buscarActivo($codigo);

        if (! $qr) {
            return redirect()->route('descargar.app');
        }

        EscaneoQr::create([
            'codigo_qr_id' => $qr->id,
            'plataforma'   => EscaneoQr::plataformaDesde($request->userAgent()),
            'ip'           => $request->ip(),
            'user_agent'   => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        // El prospecto se captura en el navegador, en el momento del escaneo,
        // con el código del asesor ya puesto: funciona igual en iPhone, Android
        // y computadora, y no depende de que instale la app.
        return redirect()->route('qr.formulario', ['codigo' => $qr->codigo]);
    }

    public function png(CodigoQr $codigoQr)
    {
        $qr = QrCode::format('png')
            ->size(1200)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($codigoQr->url);

        return response($qr, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $this->nombreArchivo($codigoQr, 'png') . '"',
        ]);
    }

    /** Vectorial: para lonas y cualquier tamaño sin perder definición. */
    public function svg(CodigoQr $codigoQr)
    {
        $qr = QrCode::format('svg')
            ->size(600)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($codigoQr->url);

        return response($qr, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="' . $this->nombreArchivo($codigoQr, 'svg') . '"',
        ]);
    }

    public function pdf(CodigoQr $codigoQr)
    {
        $qrBase64 = base64_encode(
            QrCode::format('png')
                ->size(600)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($codigoQr->url)
        );

        $pdf = Pdf::loadView('qr.asesor-pdf', [
            'qrBase64' => $qrBase64,
            'codigo'   => $codigoQr,
            'url'      => preg_replace('#^https?://#', '', $codigoQr->url),
        ])->setPaper('letter', 'portrait');

        return $pdf->download($this->nombreArchivo($codigoQr, 'pdf'));
    }

    private function nombreArchivo(CodigoQr $qr, string $ext): string
    {
        $asesor = str($qr->asesor?->name ?? 'asesor')->slug();

        return "qr-{$asesor}-{$qr->medio}-{$qr->codigo}.{$ext}";
    }
}
