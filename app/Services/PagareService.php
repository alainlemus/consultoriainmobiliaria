<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use RuntimeException;
use ZipArchive;

/**
 * Pagaré que acompaña al contrato de prestación de servicios.
 *
 * El formato (texto, acreedor, intereses, jurisdicción) es el documento Word que entregó
 * el cliente (resources/documentos/formato-pagare.docx). Se llena con los datos del
 * acreditado y la fecha del día; el importe y el vencimiento quedan en blanco para
 * capturarse al firmar.
 */
class PagareService
{
    /**
     * @return array{dia: string, mes: string, anio: string, nombre: string, domicilio: string, rfc: string}
     */
    public function datos(?string $nombre, ?string $domicilio, ?string $rfc, ?Carbon $fecha = null): array
    {
        $fecha ??= now();
        $fecha  = $fecha->copy()->locale('es');

        return [
            'dia'       => (string) $fecha->day,
            'mes'       => mb_strtoupper($fecha->translatedFormat('F')),
            'anio'      => (string) $fecha->year,
            'nombre'    => mb_strtoupper(trim((string) $nombre)),
            'domicilio' => mb_strtoupper(trim((string) $domicilio)),
            'rfc'       => mb_strtoupper(trim((string) $rfc)),
        ];
    }

    /**
     * Arma el domicilio de un expediente con los campos que haya capturados.
     */
    public function domicilioDe(object $registro): string
    {
        return collect([
            $registro->acreditado_domicilio ?? null,
            ($registro->acreditado_colonia ?? null) ? 'COL. ' . $registro->acreditado_colonia : null,
            ($registro->acreditado_cp ?? null) ? 'C.P. ' . $registro->acreditado_cp : null,
            $registro->acreditado_municipio ?? null,
            $registro->acreditado_estado ?? null,
        ])->filter()->implode(', ');
    }

    /**
     * PDF del pagaré (hoja carta) con la vista contratos.pagare.
     *
     * @param  array<string, string>  $d  resultado de datos()
     */
    public function pdf(array $d): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('contratos.pagare', ['d' => $d])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    /**
     * Genera el .docx a partir del formato original y devuelve la ruta temporal.
     *
     * @param  array<string, string>  $d  resultado de datos()
     */
    public function docx(array $d): string
    {
        $plantilla = resource_path('documentos/formato-pagare.docx');
        $destino   = sys_get_temp_dir() . '/pagare_' . uniqid() . '.docx';

        if (! is_file($plantilla) || ! copy($plantilla, $destino)) {
            throw new RuntimeException('No se encontró el formato de pagaré.');
        }

        $zip = new ZipArchive();
        if ($zip->open($destino) !== true) {
            throw new RuntimeException('No se pudo abrir el formato de pagaré.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $xml = $this->llenar($xml, $d);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $destino;
    }

    /** Sustituye las rayas del formato por los datos; si un dato falta, deja la raya. */
    private function llenar(string $xml, array $d): string
    {
        $xmlEsc = fn (string $v) => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        // CIUDAD DE PACHUCA DE SOTO, HIDALGO, A __ DE ______ DE 2026.
        $xml = preg_replace_callback(
            '/(A )_+( DE )_+( DE )\d{4}(\.)/u',
            fn ($m) => $m[1] . $xmlEsc($d['dia']) . $m[2] . $xmlEsc($d['mes']) . $m[3] . $xmlEsc($d['anio']) . $m[4],
            $xml,
            1,
        );

        // YO, C. ____, CON DOMICILIO EN ____, Y RFC: ____,
        return preg_replace_callback(
            '/(YO, C\. )(_+)(, CON DOMICILIO EN )(_+)(, Y RFC: )(_+)(,)/u',
            fn ($m) => $m[1] . ($d['nombre'] !== '' ? $xmlEsc($d['nombre']) : $m[2])
                . $m[3] . ($d['domicilio'] !== '' ? $xmlEsc($d['domicilio']) : $m[4])
                . $m[5] . ($d['rfc'] !== '' ? $xmlEsc($d['rfc']) : $m[6]) . $m[7],
            $xml,
            1,
        );
    }
}
