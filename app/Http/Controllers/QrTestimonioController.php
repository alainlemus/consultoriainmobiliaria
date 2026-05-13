<?php

namespace App\Http\Controllers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class QrTestimonioController extends Controller
{
    private function url(): string
    {
        return url('/testimonio');
    }

    /** Descarga la imagen PNG del QR */
    public function imagen()
    {
        $qr = QrCode::format('png')
            ->size(400)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($this->url());

        return response($qr, 200, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-testimonio.png"',
        ]);
    }

    /** Descarga el PDF con el QR centrado, sin ocupar toda la hoja */
    public function pdf()
    {
        $qrBase64 = base64_encode(
            QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($this->url())
        );

        $url = $this->url();

        $pdf = Pdf::loadView('qr.testimonio-pdf', compact('qrBase64', 'url'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('qr-testimonio.pdf');
    }
}
