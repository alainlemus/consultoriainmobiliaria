<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Cobertura;
use App\Models\ContratoGenerado;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContratoGeneradoWordController extends Controller
{
    /**
     * GET /admin/contratos-generados/{contratoGenerado}/word
     *
     * Genera un .docx nativo y editable del Contrato de Prestación de
     * Servicios Profesionales, con el mismo texto legal configurable
     * (Configuracion: contrato_intro / contrato_declaraciones_* /
     * contrato_clausulas) que usa el PDF que sube la app — pero a partir
     * de los datos ya capturados en ContratoGenerado, no de un Expediente.
     */
    public function export(ContratoGenerado $contratoGenerado): StreamedResponse
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $siteName       = strtoupper(Configuracion::get('site_name') ?? 'CONSULTORÍA INMOBILIARIA');
        $firmaPrestador = strtoupper(Configuracion::get('firma_prestador', 'C. JOSE ANTONIO SOLIS SANTUARIO'));
        $firmaJuridico  = strtoupper(Configuracion::get('firma_juridico', 'LIC. LUZ ANGÉLICA PÉREZ MEJÍA'));
        $domicilio      = Cobertura::first()?->detalle ?? 'Huejutla de Reyes, Hidalgo';
        $ciudad         = $contratoGenerado->ciudad ?: 'Huejutla de Reyes';
        $fecha          = now()->locale('es')->isoFormat('D [días del mes de] MMMM [del año] YYYY');

        $acreditado = strtoupper($contratoGenerado->acreditado_nombre ?: '________________________');
        $domAcred   = strtoupper($contratoGenerado->acreditado_domicilio ?: '________________________');
        $curp       = strtoupper($contratoGenerado->acreditado_curp ?: '__________________________');
        $rfc        = strtoupper($contratoGenerado->acreditado_rfc ?: '________________________');
        $nss        = $contratoGenerado->acreditado_nss ?: '________________________';
        $tipoTramite = strtoupper($contratoGenerado->tipo_tramite ?: 'CRÉDITO');
        $solidario   = strtoupper($contratoGenerado->solidario_nombre ?: '________________________');

        $monto    = $contratoGenerado->monto_credito
            ? '$' . number_format((float) $contratoGenerado->monto_credito, 2) . ' MXN'
            : '________________________';
        $pctHon   = $contratoGenerado->honorarios_porcentaje
            ? $contratoGenerado->honorarios_porcentaje . '%'
            : '10%';
        $montoHon = $contratoGenerado->honorarios_monto
            ? '$' . number_format((float) $contratoGenerado->honorarios_monto, 2) . ' MXN'
            : '________________________';

        $vars = [
            '{ciudad}'           => strtoupper($ciudad),
            '{fecha}'            => strtoupper($fecha),
            '{domicilio}'        => strtoupper($domicilio),
            '{acreditado}'       => $acreditado,
            '{dom_acreditado}'   => $domAcred,
            '{tipo_tramite}'     => $tipoTramite,
            '{curp}'             => $curp,
            '{rfc}'              => $rfc,
            '{nss}'              => $nss,
            '{folio}'            => $contratoGenerado->folio,
            '{monto_credito}'    => $monto,
            '{pct_honorarios}'   => $pctHon,
            '{monto_honorarios}' => $montoHon,
            '{site_name}'        => $siteName,
        ];

        $replace = fn (string $text) => strtr($text, $vars);

        $intro          = $replace(Configuracion::get('contrato_intro', ''));
        $declPrestador  = $replace(Configuracion::get('contrato_declaraciones_prestador', ''));
        $declInteresado = $replace(Configuracion::get('contrato_declaraciones_interesado', ''));
        $clausulas      = $replace(Configuracion::get('contrato_clausulas', ''));

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginTop'    => 1100,
            'marginBottom' => 1100,
            'marginLeft'   => 1200,
            'marginRight'  => 1200,
        ]);

        $justify = ['alignment' => Jc::BOTH, 'spaceAfter' => 160];
        $bold    = ['bold' => true];

        $section->addText($siteName, ['bold' => true, 'size' => 12, 'color' => '9B2335'], ['alignment' => Jc::CENTER]);
        $section->addText(
            'CONTRATO DE PRESTACIÓN DE SERVICIOS PROFESIONALES Y FINANCIAMIENTO DE GASTOS',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]
        );
        $section->addText("Expediente/Folio: {$contratoGenerado->folio}", ['size' => 9, 'italic' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

        $this->addParagraphs($section, $intro, $justify);

        $section->addText('DECLARACIONES', $bold + ['size' => 11, 'color' => '9B2335'], ['spaceBefore' => 160, 'spaceAfter' => 120]);

        $section->addText('POR PARTE DE "EL PRESTADOR":', $bold, ['spaceAfter' => 80]);
        $this->addParagraphs($section, $declPrestador, $justify);

        $section->addText('DECLARA EL INTERESADO:', $bold, ['spaceAfter' => 80]);
        $this->addParagraphs($section, $declInteresado, $justify);

        $section->addText('CLÁUSULAS', $bold + ['size' => 11, 'color' => '9B2335'], ['spaceBefore' => 160, 'spaceAfter' => 120]);
        $section->addText(
            'AMBAS PARTES SE COMPROMETEN A SOMETERSE AL TENOR DE LAS SIGUIENTES CLÁUSULAS SIN QUE EXISTAN VICIOS DE CONSENTIMIENTO:',
            [],
            $justify
        );
        $this->addParagraphs($section, $clausulas, $justify);

        $section->addText(
            "EN LA CIUDAD DE " . strtoupper($ciudad) . ", A LOS " . strtoupper($fecha) . ", HABIENDO LEÍDO Y COMPRENDIDO EL CONTENIDO DEL PRESENTE CONTRATO, LAS PARTES LO SUSCRIBEN EN SEÑAL DE CONFORMIDAD.",
            [],
            ['alignment' => Jc::BOTH, 'spaceBefore' => 200, 'spaceAfter' => 400]
        );

        $this->addFirmas($section, [
            ['FIRMA DE "EL PRESTADOR"', "{$firmaPrestador}\n{$siteName}"],
            ['FIRMA DEL "INTERESADO"', "C. {$acreditado}\nRFC: {$rfc}  CURP: {$curp}"],
        ]);

        $section->addTextBreak(2);

        $this->addFirmas($section, [
            ['FIRMA POR PARTE DEL JURÍDICO', $firmaJuridico],
            ['FIRMA DEL "OBLIGADO SOLIDARIO"', "C. {$solidario}"],
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'contrato_') . '.docx';
        $phpWord->save($tmpFile, 'Word2007');

        $filename = 'contrato-' . ($contratoGenerado->folio ?: $contratoGenerado->id) . '.docx';

        return response()->streamDownload(function () use ($tmpFile) {
            readfile($tmpFile);
            unlink($tmpFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /** Convierte un bloque de texto con saltos de línea en párrafos independientes. */
    private function addParagraphs(\PhpOffice\PhpWord\Element\Section $section, string $texto, array $style): void
    {
        $texto = trim($texto);
        if ($texto === '') {
            return;
        }

        foreach (preg_split('/\R/', $texto) as $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $section->addText($linea, [], $style);
        }
    }

    /** Tabla de dos columnas con líneas de firma. */
    private function addFirmas(\PhpOffice\PhpWord\Element\Section $section, array $firmas): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMarginTop' => 100]);
        $table->addRow();

        foreach ($firmas as [$titulo, $detalle]) {
            $cell = $table->addCell(4500);
            $cell->addText(str_repeat('_', 32), [], ['alignment' => Jc::CENTER, 'spaceBefore' => 600]);
            $cell->addText($titulo, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
            foreach (explode("\n", $detalle) as $linea) {
                $cell->addText($linea, ['size' => 9], ['alignment' => Jc::CENTER]);
            }
        }
    }
}
