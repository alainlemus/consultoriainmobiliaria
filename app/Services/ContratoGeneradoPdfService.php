<?php

namespace App\Services;

use App\Models\Cobertura;
use App\Models\Configuracion;
use App\Models\ContratoGenerado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Regenera el PDF de un ContratoGenerado con el mismo texto, orden y diseño
 * que la app móvil (app-consultoriainmobiliaria/src/contratos/prestacionServicios.ts),
 * a partir de los datos guardados y la plantilla vigente en `configuraciones`.
 */
class ContratoGeneradoPdfService
{
    private const BLANCO = '________________________';

    public function regenerar(ContratoGenerado $contrato, string $papel = 'carta'): void
    {
        $datos = $this->datos($contrato, $papel);
        $pdf = Pdf::loadView('contratos.generado_app', $datos)
            ->setPaper($papel === 'oficio' ? [0, 0, 612, 936] : 'letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font   = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_script(function (int $n, int $total, $cv) use ($font, $datos) {
            $y = $cv->get_height() - 22;
            $cv->line(48, $y - 6, $cv->get_width() - 48, $y - 6, [0.83, 0.69, 0.22], 0.8);
            $cv->text(48, $y, "{$datos['siteName']} • Documento generado el {$datos['generado']}", $font, 7.5, [0.59, 0.46, 0.06]);
            $cv->text($cv->get_width() - 48 - 130, $y, "{$datos['folio']} • Hoja {$n} de {$total}", $font, 7.5, [0.61, 0.14, 0.21]);
        });

        $carpeta = "contratos-generados/{$contrato->id}";
        $nombre  = 'pdf_' . now()->format('YmdHis') . '.pdf';
        Storage::disk('local')->put("{$carpeta}/{$nombre}", $pdf->output());

        if ($contrato->pdf_path) {
            Storage::disk('local')->delete($contrato->pdf_path);
        }
        $contrato->update(['pdf_path' => "{$carpeta}/{$nombre}"]);
    }

    private function datos(ContratoGenerado $c, string $papel): array
    {
        $cfg = fn (string $k, string $d = '') => Configuracion::get($k, $d) ?? $d;

        $siteName = strtoupper($cfg('site_name', 'Consultoría Inmobiliaria') ?: 'Consultoría Inmobiliaria');
        $sinTitulo = fn (string $n) => preg_replace('/^\s*(LIC\.?|C\.)\s+/iu', '', $n);
        $firmaPrestador = strtoupper($sinTitulo($cfg('firma_prestador', 'JOSE ANTONIO SOLIS SANTUARIO')));
        $firmaJuridico  = strtoupper($sinTitulo($cfg('firma_juridico', 'LUZ ANGÉLICA PÉREZ MEJÍA')));

        $ciudad = strtoupper($c->ciudad ?: 'Huejutla de Reyes');
        $fecha  = strtoupper(($c->created_at ?? now())->locale('es')->isoFormat('D [DÍAS DEL MES DE] MMMM [DEL AÑO] YYYY'));
        $blanco = self::BLANCO;
        $moneda = fn ($n) => $n !== null && $n !== '' ? '$' . number_format((float) $n, 2) . ' MXN' : $blanco;

        $acreditado = strtoupper($c->acreditado_nombre ?: $blanco);
        $curp       = strtoupper($c->acreditado_curp ?: $blanco);
        $rfc        = strtoupper($c->acreditado_rfc ?: $blanco);
        $solidario  = strtoupper($c->solidario_nombre ?: $blanco);

        $vars = [
            '{ciudad}'             => $ciudad,
            '{fecha}'              => $fecha,
            '{domicilio}'          => strtoupper(Cobertura::first()?->detalle ?: 'Huejutla de Reyes, Hidalgo'),
            '{domicilio_juridico}' => strtoupper($cfg('domicilio_juridico')),
            '{firma_prestador}'    => strtoupper($cfg('firma_prestador')),
            '{firma_juridico}'     => strtoupper($cfg('firma_juridico')),
            '{acreditado}'         => $acreditado,
            '{dom_acreditado}'     => strtoupper($c->acreditado_domicilio ?: $blanco),
            '{tipo_tramite}'       => strtoupper($c->tipo_tramite ?: 'CRÉDITO'),
            '{curp}'               => $curp,
            '{rfc}'                => $rfc,
            '{nss}'                => strtoupper($c->acreditado_nss ?: $blanco),
            '{clave_elector}'      => strtoupper($c->acreditado_clave_elector ?: $blanco),
            '{folio}'              => (string) $c->folio,
            '{monto_credito}'      => $moneda($c->monto_credito),
            '{pct_honorarios}'     => $c->honorarios_porcentaje !== null ? (float) $c->honorarios_porcentaje . '%' : '10%',
            '{monto_honorarios}'   => $moneda($c->honorarios_monto),
            '{obligado_solidario}' => $solidario,
            '{site_name}'          => $siteName,
        ];
        $replace = fn (string $t) => strtr($t, $vars);
        $lineas  = fn (string $t) => array_values(array_filter(array_map('trim', preg_split('/\R/', $replace($t)))));

        // La plantilla trae mes/año fijos tras {fecha}; la app los descarta para no duplicar la fecha.
        $intro = preg_replace(
            '/\{fecha\}\s*DÍAS\s+DEL\s+MES\s+DE\s+\S+\s+DEL\s+AÑO\s+\d{4}/iu',
            '{fecha}',
            $cfg('contrato_intro')
        );

        $esp = $papel === 'oficio'
            ? ['fs' => 9.5, 'lh' => 1.6, 'pb' => 12, 'h2t' => 18, 'h2b' => 10, 'cierre' => 22, 'firmaTop' => 40]
            : ['fs' => 8.5, 'lh' => 1.45, 'pb' => 7, 'h2t' => 14, 'h2b' => 8, 'cierre' => 14, 'firmaTop' => 28];

        return [
            ...$esp,
            'margenSup' => 18, 'margenInf' => 58,
            'siteName' => $siteName, 'folio' => $c->folio, 'generado' => now()->format('d/m/Y'),
            'intro' => $replace($intro),
            'declPrestador'  => $lineas($cfg('contrato_declaraciones_prestador')),
            'declInteresado' => $lineas($cfg('contrato_declaraciones_interesado')),
            'clausulas'      => $lineas($cfg('contrato_clausulas')),
            'ciudad' => $ciudad, 'fecha' => $fecha, 'acreditado' => $acreditado,
            'rfc' => $rfc, 'curp' => $curp, 'solidario' => $solidario,
            'firmaPrestador' => $firmaPrestador, 'firmaJuridico' => $firmaJuridico,
        ];
    }
}
