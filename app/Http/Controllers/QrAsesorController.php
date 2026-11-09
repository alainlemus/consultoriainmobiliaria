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
 *  - GET /a/{codigo}: registra el escaneo y manda a la tienda del celular
 *    (Play Store con `referrer` para la fase 2; App Store en iPhone). Si el
 *    código no existe o está pausado, va a la página pública de descarga.
 *  - Descargas (panel, autenticado): PNG y SVG listos para imprenta y un PDF
 *    con el código en texto grande por si no pueden escanear.
 */
class QrAsesorController extends Controller
{
    private const URL_PLACEHOLDER = 'https://google.com.mx';

    public function redirigir(Request $request, string $codigo): RedirectResponse
    {
        $qr = CodigoQr::buscarActivo($codigo);

        if (! $qr) {
            return redirect()->route('descargar.app');
        }

        $plataforma = EscaneoQr::plataformaDesde($request->userAgent());

        EscaneoQr::create([
            'codigo_qr_id' => $qr->id,
            'plataforma'   => $plataforma,
            'ip'           => $request->ip(),
            'user_agent'   => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        $appStore  = (string) setting('app_store_url', self::URL_PLACEHOLDER);
        $playStore = (string) setting('play_store_url', self::URL_PLACEHOLDER);

        if ($plataforma === 'android' && $this->tiendaConfigurada($playStore)) {
            // El Play Install Referrer entrega este `referrer` a la app en el
            // primer arranque (fase 2): ahí se lee codigo_asesor sin que la
            // persona teclee nada.
            $referrer = http_build_query([
                'utm_source'    => 'qr',
                'utm_medium'    => $qr->medio,
                'utm_campaign'  => $qr->codigo,
                'codigo_asesor' => $qr->codigo,
            ]);
            $separador = str_contains($playStore, '?') ? '&' : '?';

            return redirect()->away($playStore . $separador . 'referrer=' . urlencode($referrer));
        }

        if ($plataforma === 'ios' && $this->tiendaConfigurada($appStore)) {
            return redirect()->away($appStore);
        }

        // Computadora, tienda sin configurar o navegador raro: la página de
        // descarga muestra el código para que lo anoten.
        return redirect()->route('descargar.app', ['codigo' => $qr->codigo]);
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

    private function tiendaConfigurada(string $url): bool
    {
        return $url !== '' && $url !== self::URL_PLACEHOLDER;
    }

    private function nombreArchivo(CodigoQr $qr, string $ext): string
    {
        $asesor = str($qr->asesor?->name ?? 'asesor')->slug();

        return "qr-{$asesor}-{$qr->medio}-{$qr->codigo}.{$ext}";
    }
}
