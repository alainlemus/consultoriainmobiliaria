<?php

namespace App\Services;

use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;

/**
 * Extrae texto de PDFs usando dos métodos:
 *
 *  1. Texto digital (smalot/pdfparser) → rápido, gratis, sin API
 *  2. OCR Google Vision → para PDFs escaneados (imágenes)
 *
 * Documentos soportados:
 *   CURP, SAT, Talón nómina, Acta nacimiento, INE,
 *   Avalúo (ARQUIMVAL), Escritura, Predial, Cuenta bancaria,
 *   SAR, AFORE, T_FOVISSSTE (talón con número de crédito)
 */
class PdfExtractorService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Extracción de texto
    // ─────────────────────────────────────────────────────────────────────────

    public function extractText(string $rutaAbsoluta): ?string
    {
        $texto = $this->extractTextDigital($rutaAbsoluta);
        if ($texto && mb_strlen(trim($texto)) > 20) {
            return $texto;
        }
        return $this->extractTextOcr($rutaAbsoluta);
    }

    private function extractTextDigital(string $rutaAbsoluta): ?string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) return null;
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $text   = $parser->parseFile($rutaAbsoluta)->getText();
            return mb_strlen(trim($text)) > 10 ? $text : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractTextOcr(string $rutaAbsoluta): ?string
    {
        $credentialsPath = env('GOOGLE_VISION_CREDENTIALS');
        if (! $credentialsPath || ! file_exists($credentialsPath)) return null;
        if (! class_exists(\Imagick::class)) return null;

        try {
            foreach (['/opt/homebrew/bin/gs', '/usr/local/bin/gs', '/usr/bin/gs'] as $gsPath) {
                if (file_exists($gsPath)) {
                    putenv('PATH=' . dirname($gsPath) . ':' . getenv('PATH'));
                    break;
                }
            }

            $client     = new ImageAnnotatorClient(['credentials' => $credentialsPath]);
            $textoTotal = '';
            $imagick    = new \Imagick();
            $imagick->setResolution(200, 200);
            $imagick->readImage($rutaAbsoluta);
            $totalPaginas = min($imagick->getNumberImages(), 3);

            for ($i = 0; $i < $totalPaginas; $i++) {
                $imagick->setIteratorIndex($i);
                $imagick->setImageFormat('png');
                $imagick->setImageCompressionQuality(95);
                $imagick->transformImageColorspace(\Imagick::COLORSPACE_GRAY);

                $response = $client->batchAnnotateImages(
                    (new BatchAnnotateImagesRequest())->setRequests([
                        (new AnnotateImageRequest())
                            ->setImage((new Image())->setContent($imagick->getImageBlob()))
                            ->setFeatures([(new Feature())->setType(Type::DOCUMENT_TEXT_DETECTION)])
                    ])
                );

                $responses = $response->getResponses();
                if (count($responses) > 0 && ($ann = $responses[0]->getFullTextAnnotation())) {
                    $textoTotal .= $ann->getText() . "\n";
                }
            }

            $imagick->clear();
            $client->close();
            return mb_strlen(trim($textoTotal)) > 10 ? $textoTotal : null;

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Google Vision OCR error: ' . $e->getMessage(), [
                'archivo' => basename($rutaAbsoluta),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Punto de entrada principal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{tipo: string|null, datos: array, ocr_usado: bool}
     */
    public function extraer(string $rutaAbsoluta, string $nombreArchivo): array
    {
        $textoDigital = $this->extractTextDigital($rutaAbsoluta);
        $ocrUsado     = false;

        if ($textoDigital && mb_strlen(trim($textoDigital)) > 20) {
            $text = $textoDigital;
        } else {
            $text     = $this->extractTextOcr($rutaAbsoluta);
            $ocrUsado = $text !== null;
        }

        if (! $text) {
            return ['tipo' => null, 'datos' => [], 'ocr_usado' => false];
        }

        $u = strtoupper($nombreArchivo);

        // ── Detección por nombre de archivo primero, luego por contenido ──────

        if (str_contains($u, 'CURP')) {
            return ['tipo' => 'curp', 'datos' => $this->extraerCurp($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'SAT') || str_contains($text, 'CÉDULA DE IDENTIFICACIÓN FISCAL') || str_contains($text, 'CONSTANCIA DE SITUACIÓN FISCAL')) {
            return ['tipo' => 'sat', 'datos' => $this->extraerSat($text), 'ocr_usado' => $ocrUsado];
        }

        // T_FOVISSSTE / talones con número de crédito FOVISSSTE
        if (preg_match('/T[_\s]26/i', $u) || (str_contains($text, 'SUELDO') && preg_match('/\d{1,2}-\d{2}-\d{5}-\d{2}-\d{3}-\d{3}-\d{3}/', $text))) {
            return ['tipo' => 'talon_fovissste', 'datos' => $this->extraerTalonFovissste($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'TALON') || str_contains($u, 'TALÓN') || (str_contains($text, 'SUELDO') && str_contains($text, 'CURP'))) {
            return ['tipo' => 'talon', 'datos' => $this->extraerTalon($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'ACTA') || str_contains($text, 'Acta de Nacimiento') || str_contains($text, 'Datos de la Persona Registrada')) {
            return ['tipo' => 'acta_nacimiento', 'datos' => $this->extraerActaNacimiento($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'INE') || str_contains($text, 'INSTITUTO NACIONAL ELECTORAL') || str_contains($text, 'CREDENCIAL PARA VOTAR')) {
            return ['tipo' => 'ine', 'datos' => $this->extraerIne($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'SAR') || str_contains($text, 'FOLIO POR-') || (str_contains($text, 'RCV ISSSTE') && str_contains($text, 'Apellido Paterno'))) {
            return ['tipo' => 'sar', 'datos' => $this->extraerSar($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'AFORE') || str_contains($text, 'Afore Comisión') || str_contains($text, 'Ahorro para el Retiro')) {
            return ['tipo' => 'afore', 'datos' => $this->extraerAfore($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($text, 'ARQUIMVAL') || str_contains($text, 'Avalúo de Inmueble') || str_contains($text, 'Clave Unica de Vivienda') || str_contains($u, 'AVALU') || str_contains($u, 'PREAVALU') || str_contains($u, 'PRE-AVALU')) {
            return ['tipo' => 'avaluo', 'datos' => $this->extraerAvaluo($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'ESCRITURA') || str_contains($text, 'ESCRITURA PÚBLICA') || str_contains($text, 'NOTARIO PÚBLICO')) {
            return ['tipo' => 'escritura', 'datos' => $this->extraerEscritura($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'PREDIAL') || str_contains($text, 'IMPUESTO PREDIAL') || str_contains($text, 'TESORERIA MUNICIPAL')) {
            return ['tipo' => 'predial', 'datos' => $this->extraerPredial($text), 'ocr_usado' => $ocrUsado];
        }

        if (str_contains($u, 'CUENTA') || str_contains($u, 'CLABE') || str_contains($text, 'Cuenta Nómina') || str_contains($text, 'CLABE') || str_contains($text, 'Número de Cliente')) {
            return ['tipo' => 'cuenta_bancaria', 'datos' => $this->extraerCuentaBancaria($text), 'ocr_usado' => $ocrUsado];
        }

        return ['tipo' => 'desconocido', 'datos' => [], 'ocr_usado' => $ocrUsado];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Extractores por tipo de documento
    // ─────────────────────────────────────────────────────────────────────────

    private function extraerCurp(string $text): array
    {
        $datos = [];
        if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)\b/', $text, $m)) {
            $datos['curp'] = $m[1];
            $curp = $m[1];
            $anio = (int)substr($curp, 4, 2);
            $mes  = substr($curp, 6, 2);
            $dia  = substr($curp, 8, 2);
            $datos['fecha_nacimiento'] = ($anio >= 24 ? '19' : '20') . str_pad($anio, 2, '0', STR_PAD_LEFT) . "-{$mes}-{$dia}";
        }
        if (preg_match('/(?:^|\n)Nombre\s*\n([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,60})(?:\n|Entidad)/m', $text, $m)) {
            $datos['nombre'] = trim($m[1]);
        }
        return array_filter($datos);
    }

    private function extraerSat(string $text): array
    {
        $datos = [];
        if (preg_match('/RFC[:\s]+([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/i', $text, $m)) {
            $datos['rfc'] = strtoupper(trim($m[1]));
        } elseif (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/', $text, $m)) {
            $datos['rfc'] = $m[1];
        }
        if (preg_match('/CURP[:\s]+([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)/i', $text, $m)) {
            $datos['curp'] = strtoupper(trim($m[1]));
        }
        // Nombre en formato SAT: "Nombre(s) ANGELICA PrimerApellido CRUZ SegundoApellido CORTEZ"
        if (preg_match('/Nombre\(s\)[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s]{2,40})\n.*?PrimerApellido[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ]{2,30})\n.*?SegundoApellido[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ]{2,30})/is', $text, $m)) {
            $datos['nombre'] = trim($m[3]) . ' ' . trim($m[2]) . ' ' . trim($m[1]);
        }
        return array_filter($datos);
    }

    private function extraerTalon(string $text): array
    {
        $datos = [];
        if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)\b/', $text, $m)) {
            $datos['curp'] = $m[1];
        }
        // RFC: diferente al CURP
        if (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/', $text, $m)) {
            if (! isset($datos['curp']) || $m[1] !== $datos['curp']) {
                $datos['rfc'] = $m[1];
            }
        }
        // Sueldo base
        if (preg_match('/\b120\s+SUELDO\s+([\d,]+\.\d{2})/i', $text, $m)) {
            $datos['salario_mensual'] = str_replace(',', '', $m[1]);
        } elseif (preg_match('/SUELDO\s+([\d,]+\.\d{2})/', $text, $m)) {
            $datos['salario_mensual'] = str_replace(',', '', $m[1]);
        }
        // Nombre: primera línea ALL CAPS de 2-4 palabras antes de un número de empleado
        if (preg_match('/^([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,60})\s+[\d\-]{5}/m', $text, $m)) {
            $nombre = trim($m[1]);
            if (substr_count($nombre, ' ') >= 1 && substr_count($nombre, ' ') <= 4) {
                $datos['nombre'] = $nombre;
            }
        }
        // Municipio: aparece junto a CURP y RFC en primera línea del talón
        if (preg_match('/[A-Z]{4}\d{6}[HM].{8}\s+[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}\s+([A-ZÁÉÍÓÚÜÑ]{3,30})\s+DEL?\s+\d/i', $text, $m)) {
            $datos['acreditado_municipio'] = trim($m[1]);
        }
        return array_filter($datos);
    }

    private function extraerTalonFovissste(string $text): array
    {
        $datos = $this->extraerTalon($text);
        // Número de crédito FOVISSSTE: formato "1-02-08264-01-802-046-111"
        if (preg_match('/\b(\d{1,2}-\d{2}-\d{5}-\d{2}-\d{3}-\d{3}-\d{3})\b/', $text, $m)) {
            $datos['acreditado_numero_credito'] = $m[1];
        }
        // NSS: número de 11 dígitos que aparece al inicio
        if (preg_match('/^(\d{11})\b/m', $text, $m)) {
            $datos['nss'] = $m[1];
        }
        return array_filter($datos);
    }

    private function extraerActaNacimiento(string $text): array
    {
        $datos = [];
        if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)\b/', $text, $m)) {
            $datos['curp'] = $m[1];
        }
        if (preg_match('/Datos de la Persona Registrada\s+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,70})/i', $text, $m)) {
            $datos['nombre'] = trim($m[1]);
        }
        if (preg_match('/Fecha de Nacimiento[:\s]+(\d{2}\/\d{2}\/\d{4})/i', $text, $m)) {
            [$d, $mo, $y] = explode('/', $m[1]);
            $datos['fecha_nacimiento'] = "{$y}-{$mo}-{$d}";
        }
        return array_filter($datos);
    }

    private function extraerIne(string $text): array
    {
        $datos = [];
        if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)\b/', $text, $m)) {
            $datos['curp'] = $m[1];
        }
        // Nombre: aparece como APELLIDO1 APELLIDO2 NOMBRE antes de DOMICILIO
        if (preg_match('/([A-ZÁÉÍÓÚÜÑ]{2,30})\s*\n([A-ZÁÉÍÓÚÜÑ]{2,30})\s*\n([A-ZÁÉÍÓÚÜÑ\s]{2,40})\s*\n\s*DOMICILIO/u', $text, $m)) {
            $datos['nombre'] = trim($m[3]) . ' ' . trim($m[1]) . ' ' . trim($m[2]);
        } elseif (preg_match('/(?:NOMBRE|NAME)[:\s\/]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,60})/i', $text, $m)) {
            $datos['nombre'] = trim($m[1]);
        }
        // Domicilio completo — línea inmediata después de DOMICILIO
        if (preg_match('/DOMICILIO\s*\n([^\n]{10,120})/i', $text, $m)) {
            $domicilioRaw = trim($m[1]);
            $datos['acreditado_domicilio'] = $domicilioRaw;
            // Intentar extraer CP de 5 dígitos del domicilio
            if (preg_match('/\b(\d{5})\b/', $domicilioRaw, $cp)) {
                $datos['acreditado_cp'] = $cp[1];
            }
        }
        // Municipio y estado del domicilio del INE (segunda línea del domicilio)
        if (preg_match('/DOMICILIO\s*\n[^\n]+\n([^\n]{5,80})/i', $text, $m)) {
            $linea2 = trim($m[1]);
            // Buscar patrón "MUNICIPIO, ESTADO"
            if (preg_match('/([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s]{3,30}),\s*([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s]{3,30})/i', $linea2, $mm)) {
                $datos['acreditado_municipio'] = trim($mm[1]);
                $datos['acreditado_estado']    = trim($mm[2]);
            }
        }
        // Fecha de nacimiento
        if (preg_match('/FECHA\s+DE\s+NACIMIENTO[:\s]+(\d{2}[\/\-]\d{2}[\/\-]\d{4})/i', $text, $m)) {
            [$d, $mo, $y] = preg_split('/[\/\-]/', $m[1]);
            $datos['fecha_nacimiento'] = "{$y}-{$mo}-{$d}";
        }
        return array_filter($datos);
    }

    private function extraerSar(string $text): array
    {
        $datos = [];
        // Nombre completo desde campos separados
        if (preg_match('/Nombre\(s\)\s+(\S+).*?Apellido\s+Paterno\s+(\S+).*?Apellido\s+Materno\s+(\S+)/is', $text, $m)) {
            $datos['nombre'] = trim($m[2]) . ' ' . trim($m[3]) . ' ' . trim($m[1]);
        }
        if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)\b/', $text, $m)) {
            $datos['curp'] = $m[1];
        }
        if (preg_match('/RFC\s+([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/i', $text, $m)) {
            $datos['rfc'] = strtoupper(trim($m[1]));
        }
        // Subcuenta vivienda: RCV ISSSTE es el saldo de vivienda para trabajadores ISSSTE
        if (preg_match('/RCV\s+ISSSTE\s+([\d,]+(?:\.\d{2})?)/i', $text, $m)) {
            $saldo = str_replace(',', '', $m[1]);
            if ((float)$saldo > 0) {
                $datos['subcuenta_vivienda'] = $saldo;
            }
        }
        // NSS
        if (preg_match('/NSS\s+(\d{10,11})/i', $text, $m)) {
            $datos['nss'] = trim($m[1]);
        }
        return array_filter($datos);
    }

    private function extraerAfore(string $text): array
    {
        $datos = [];
        if (preg_match('/\b([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)\b/', $text, $m)) {
            $datos['curp'] = $m[1];
        }
        if (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/', $text, $m)) {
            if (! isset($datos['curp']) || $m[1] !== $datos['curp']) {
                $datos['rfc'] = $m[1];
            }
        }
        // Nombre: aparece antes de RFC/CURP en una línea separada
        $lineas = array_filter(array_map('trim', explode("\n", $text)));
        foreach ($lineas as $linea) {
            if (preg_match('/^[A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{8,60}$/', $linea) && substr_count($linea, ' ') >= 1 && substr_count($linea, ' ') <= 4) {
                $datos['nombre'] = $linea;
                break;
            }
        }
        // Subcuenta vivienda: buscar RCV ISSSTE o Fondo Vivienda en AFORE
        if (preg_match('/(?:RCV\s+ISSSTE|FONDO\s+DE\s+LA\s+VIVIENDA)[^\d]*([\d,]+\.\d{2})/i', $text, $m)) {
            $saldo = str_replace(',', '', $m[1]);
            if ((float)$saldo > 0) {
                $datos['subcuenta_vivienda'] = $saldo;
            }
        }
        // Si no encontró vivienda, el saldo total del AFORE (CP) como referencia
        if (empty($datos['subcuenta_vivienda'])) {
            if (preg_match('/\bCP\b[^\d]*([\d,]{5,}(?:\.\d{2})?)/i', $text, $m)) {
                $saldo = str_replace(',', '', $m[1]);
                if ((float)$saldo > 1000) {
                    $datos['subcuenta_vivienda'] = $saldo;
                }
            }
        }
        return array_filter($datos);
    }

    private function extraerAvaluo(string $text): array
    {
        $datos = [];

        // CUV
        if (preg_match('/Clave Unica de Vivienda[:\s]+(\d{16})/i', $text, $m)) {
            $datos['cuv'] = $m[1];
        }

        // ── Dirección del acreditado desde sección 8.2 ──────────────────────
        // Formato: "Dirección: SOL No. 18 Int , Col.  20 DE NOVIEMBRE , TEMPOAL, VERACRUZ, CP  92063"
        $patronDir = '/Direcci\x{00F3}n[:\s]+(.+?),\s*Col\.?\s+(.+?),\s*([^,\n]+?),\s*([^,\n]+?),\s*CP\s+(\d{5})/u';
        if (preg_match($patronDir, $text, $m)) {
            $datos['acreditado_domicilio'] = trim($m[1]);
            $datos['acreditado_colonia']   = trim($m[2]);
            $datos['acreditado_municipio'] = trim($m[3]);
            $datos['acreditado_estado']    = trim($m[4]);
            $datos['acreditado_cp']        = trim($m[5]);
        }

        // RFC del acreditado (solicitante — sección 8.2)
        if (preg_match('/8\.2\s+SOLICITANTE.*?RFC:\s*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/is', $text, $m)) {
            $datos['acreditado_rfc'] = strtoupper(trim($m[1]));
        } elseif (preg_match('/8\.2.*?RFC:\s*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/is', $text, $m)) {
            $datos['acreditado_rfc'] = strtoupper(trim($m[1]));
        }
        // CURP del acreditado
        if (preg_match('/8\.2\s+SOLICITANTE.*?CURP:\s*([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)/is', $text, $m)) {
            $datos['curp'] = strtoupper(trim($m[1]));
        } elseif (preg_match('/8\.2.*?CURP:\s*([A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d)/is', $text, $m)) {
            $datos['curp'] = strtoupper(trim($m[1]));
        }

        // RFC del propietario/vendedor — sección 8.3 PROPIETARIO
        if (preg_match('/8\.3\s+PROPIETARIO.*?RFC\s*:?\s*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/is', $text, $m)) {
            $rfcVendedor   = strtoupper(trim($m[1]));
            $rfcAcreditado = $datos['acreditado_rfc'] ?? '';
            if ($rfcVendedor !== $rfcAcreditado) {
                $datos['vendedor_rfc'] = $rfcVendedor;
            }
        }

        // Nombre del solicitante (acreditado)
        if (preg_match('/(?:SOLICITANTE DEL AVALUO|Nombre del Solicitante|8\.2[^N]*Nombre)[^:\n]*[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,70})/i', $text, $m)) {
            $datos['acreditado_nombre'] = trim($m[1]);
        }

        // Nombre del propietario (vendedor)
        if (preg_match('/(?:Nombre del Propietario|8\.3[^N]*Nombre)[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,70})/i', $text, $m)) {
            $datos['vendedor_nombre'] = trim($m[1]);
        }

        // Dirección de la vivienda (datos generales)
        if (preg_match('/Calle[:\s]+([^\n]+?)\s+No\.\s*Exterior[:\s]+([^\s\n]+)/is', $text, $m)) {
            $datos['vivienda_calle']  = trim($m[1]);
            $datos['vivienda_numero'] = trim($m[2]);
        } elseif (preg_match('/8\.1.*?Calle[:\s]+([A-ZÁÉÍÓÚÜÑ][^\n]{5,80})/is', $text, $m)) {
            $datos['vivienda_calle'] = trim($m[1]);
        } elseif (preg_match('/Calle y numero[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ0-9\s\-\.]+?)(?:\s+No\.?\s*(\S+))?(?:\n|$)/i', $text, $m)) {
            $datos['vivienda_calle'] = trim($m[1]);
            if (! empty($m[2])) $datos['vivienda_numero'] = trim($m[2]);
        }

        // Número exterior específico (8.1 ANEXOS)
        if (preg_match('/No\.\s*Exterior[:\s]+([^\s\n]+)/i', $text, $m)) {
            $datos['vivienda_numero'] = trim($m[1]);
        }

        // Colonia
        if (preg_match('/Colonia\s+o\s+Fracc[^:]*:[:\s]*([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ0-9\s]+?)(?:\n|Entidad|C\.P\.|$)/i', $text, $m)) {
            $datos['vivienda_colonia'] = trim($m[1]);
        } elseif (preg_match('/Colonia[^:]*[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ0-9\s\-]+?)(?:\n|C\.?P\.?|$)/i', $text, $m)) {
            $datos['vivienda_colonia'] = trim($m[1]);
        }

        // CP
        if (preg_match('/C\.?P\.?[:\s]+(\d{5})/i', $text, $m)) {
            $datos['vivienda_cp'] = $m[1];
        }

        // Municipio
        if (preg_match('/(?:Delegación o municipio|Municipio)[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s]+?)(?:\n|Entidad|$)/i', $text, $m)) {
            $datos['vivienda_municipio'] = trim($m[1]);
        }

        // Estado
        if (preg_match('/Entidad\s+Fed(?:erativ[a])?[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s]+?)(?:\n|C\.P\.|$)/i', $text, $m)) {
            $datos['vivienda_estado'] = trim($m[1]);
        }

        // Superficie construida — buscar en sección de características (mínimo 20m², máximo 2000m²)
        if (preg_match('/3\.\d+[^S]{0,300}Superficie\s+(?:de\s+)?Construcci[oó]n[^\d]*([\d,\.]+)/is', $text, $m)) {
            $sup = (float) str_replace(',', '', $m[1]);
            if ($sup >= 20 && $sup <= 2000) {
                $datos['vivienda_superficie'] = (string) $sup;
            }
        } elseif (preg_match('/(?:Sup(?:erficie)?\.?\s*Construida?|Total\s+Construcci[oó]n)[^\d]*([\d,\.]+)\s*(?:m2|M2|m²)?/i', $text, $m)) {
            $sup = (float) str_replace(',', '', $m[1]);
            if ($sup >= 20 && $sup <= 2000) {
                $datos['vivienda_superficie'] = (string) $sup;
            }
        }

        // Valor comercial del avalúo → monto total estimado
        if (preg_match('/Valor\s+(?:Comercial|de\s+Mercado|del\s+Inmueble)[^\d\$]*([\d,\.]+)/i', $text, $m)) {
            $valor = str_replace(',', '', $m[1]);
            if ((float)$valor > 10000) {
                $datos['monto_total_estimado'] = $valor;
            }
        }

        return array_filter($datos);
    }

    private function extraerEscritura(string $text): array
    {
        $datos = [];
        if (preg_match('/(?:ESCRITURA|INSTRUMENTO)\s+(?:PÚBLICA\s+)?(?:NÚMERO|No\.?)[^\d]*(\d{1,6})/i', $text, $m)) {
            $datos['escritura_numero'] = $m[1];
        }
        if (preg_match('/(?:OTORGANTE|VENDEDOR|TRANSMITENTE)[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,70})/i', $text, $m)) {
            $datos['vendedor_nombre'] = trim($m[1]);
        }
        if (preg_match('/(?:SUPERFICIE|ÁREA)[:\s]+([\d,\.]+)\s*(?:M2|M²|metros)/i', $text, $m)) {
            $datos['vivienda_superficie'] = str_replace(',', '', $m[1]);
        }
        return array_filter($datos);
    }

    private function extraerPredial(string $text): array
    {
        $datos = [];
        if (preg_match('/(?:CLAVE CATASTRAL|FOLIO)[:\s]*([\d\s]+)/i', $text, $m)) {
            $datos['numero_cuenta_predial'] = trim(preg_replace('/\s+/', '', $m[1]));
        }
        if (preg_match('/NOMBRE DEL CONTRIBUYENTE\s+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑ\s]{5,70})/i', $text, $m)) {
            $datos['vendedor_nombre'] = trim($m[1]);
        }
        if (preg_match('/COLONIA\s+([A-ZÁÉÍÓÚÜÑ0-9][^\n]{5,80})/i', $text, $m)) {
            $datos['vivienda_colonia'] = trim($m[1]);
        }
        if (preg_match('/H\.\s*AYUNTAMIENTO\s*DE[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s]+)/i', $text, $m)) {
            $datos['vivienda_municipio'] = trim($m[1]);
        }
        return array_filter($datos);
    }

    private function extraerCuentaBancaria(string $text): array
    {
        $datos = [];
        // CLABE interbancaria (18 dígitos)
        if (preg_match('/\b(\d{18})\b/', $text, $m)) {
            $datos['vendedor_clabe'] = $m[1];
        } elseif (preg_match('/(?:CLABE|CUENTA)[:\s]*([\d]{10,18})/i', $text, $m)) {
            $datos['vendedor_clabe'] = trim($m[1]);
        }
        // Nombre del titular
        if (preg_match('/(?:Cliente|Titular\s+Garantizado|Titular)[:\s]+([A-ZÁÉÍÓÚÜÑ][A-ZÁÉÍÓÚÜÑa-záéíóúüñ\s]{5,70})/i', $text, $m)) {
            // Evitar capturar "Fecha de nacimiento" u otras líneas
            $nombre = trim($m[1]);
            if (! preg_match('/fecha|nacimiento|operaci/i', $nombre)) {
                $datos['vendedor_nombre'] = $nombre;
            }
        }
        // RFC
        if (preg_match('/RFC[:\s]+([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/i', $text, $m)) {
            $datos['vendedor_rfc'] = strtoupper(trim($m[1]));
        }
        // Banco
        if (preg_match('/(BANCOPPEL|BBVA|BANAMEX|SANTANDER|BANORTE|HSBC|INBURSA|SCOTIABANK|CITIBANAMEX|AZTECA|COPPEL|BANBAJIO)/i', $text, $m)) {
            $datos['vendedor_banco'] = strtoupper($m[1]);
        }
        return array_filter($datos);
    }
}
