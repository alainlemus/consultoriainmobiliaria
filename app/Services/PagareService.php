<?php

namespace App\Services;

use App\Models\ContratoGenerado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use RuntimeException;
use ZipArchive;

/**
 * Pagaré que acompaña al contrato de prestación de servicios.
 *
 * El formato es el que entregó el cliente (deudor, aval y testigos). Se llena con los
 * datos del acreditado y del obligado solidario (aval), el lugar y la fecha del contrato,
 * y el acreedor con su domicilio de pago. El importe, el vencimiento, el % de intereses
 * y los testigos quedan en blanco para capturarse al firmar.
 *
 * El Word sale de resources/documentos/formato-pagare.docx, que trae marcas {campo}
 * donde va cada dato; si el dato falta, la marca se cambia por una raya.
 */
class PagareService
{
    public const ACREEDOR = 'C. JOSE ANTONIO SOLIS SANTUARIO';
    public const DOMICILIO_PAGO = 'AVENIDA REVOLUCIÓN 1014 DESPACHO 55 PLAZA LAS MARIALUISAS, COLONIA PERIODISTAS, C.P. 42060, PACHUCA DE SOTO, HIDALGO';

    /** Largo de la raya en el Word por campo, cuando el dato falta. */
    private const RAYAS = [
        'lugar'          => 25,
        'nombre'         => 40,
        'domicilio'      => 50,
        'identificacion' => 39,
        'aval_nombre'    => 40,
        'aval_domicilio' => 50,
    ];

    /**
     * @return array{lugar: string, fecha: string, nombre: string, domicilio: string, identificacion: string,
     *               aval_nombre: string, aval_domicilio: string, acreedor: string, domicilio_pago: string}
     */
    public function datos(
        ?string $nombre,
        ?string $domicilio,
        ?string $claveElector = null,
        ?string $avalNombre = null,
        ?string $avalDomicilio = null,
        ?string $lugar = null,
        ?Carbon $fecha = null,
    ): array {
        $fecha ??= now();
        $fecha  = $fecha->copy()->locale('es');
        $txt    = fn (?string $v) => mb_strtoupper(trim((string) $v));
        $clave  = $txt($claveElector);

        return [
            'lugar'          => $txt($lugar),
            'fecha'          => mb_strtoupper($fecha->translatedFormat('j \d\e F \d\e Y')),
            'nombre'         => $txt($nombre),
            'domicilio'      => $txt($domicilio),
            'identificacion' => $clave !== '' ? "INE, CLAVE DE ELECTOR {$clave}" : '',
            'aval_nombre'    => $txt($avalNombre),
            'aval_domicilio' => $txt($avalDomicilio),
            'acreedor'       => self::ACREEDOR,
            'domicilio_pago' => self::DOMICILIO_PAGO,
        ];
    }

    /** Datos del pagaré de un contrato generado en la app: lugar y fecha en que se firmó. */
    public function datosDeContrato(ContratoGenerado $c): array
    {
        return $this->datos(
            $c->acreditado_nombre,
            $c->acreditado_domicilio,
            $c->acreditado_clave_elector,
            $c->solidario_nombre,
            $c->solidario_domicilio,
            $c->ciudad,
            $c->created_at,
        );
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

    /** Sustituye las marcas {campo} del formato por los datos; si un dato falta, deja la raya. */
    private function llenar(string $xml, array $d): string
    {
        $xmlEsc = fn (string $v) => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return preg_replace_callback('/\{([a-z_]+)\}/', function ($m) use ($d, $xmlEsc) {
            $valor = $d[$m[1]] ?? '';

            return $valor !== '' ? $xmlEsc($valor) : str_repeat('_', self::RAYAS[$m[1]] ?? 30);
        }, $xml);
    }
}
