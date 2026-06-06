<?php

namespace App\Services;

use App\Models\Configuracion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para obtener y actualizar el valor de la UMA
 * (Unidad de Medida y Actualización).
 *
 * La UMA se actualiza cada 1 de febrero según lo establece el INEGI.
 * Fuentes consultadas (en orden de prioridad):
 *   1. API oficial INEGI BIE (requiere token BIE en .env → INEGI_TOKEN)
 *   2. Scraping de la página oficial INEGI
 *   3. Valor de respaldo hardcodeado (UMA vigente)
 *
 * Los valores se almacenan en la tabla `configuraciones` para ser
 * usados por el simulador y cualquier otro cálculo del CRM.
 *
 * ⚠️  IMPORTANTE — INEGI tiene APIs separadas:
 *   - API DENUE (directorio negocios): token de https://www.inegi.org.mx/app/mapa/denue/
 *   - API BISE (indicadores sociodemográficos): mismo token DENUE, funciona ✓
 *   - API BIE (banco de información económica): requiere token DISTINTO
 *     Registro en: https://www.inegi.org.mx/app/api/indicadores/
 *
 *   El indicador UMA (ID 539358) vive en BIE, NO en BISE.
 *   Con un token DENUE el endpoint BIE devuelve ErrorCode:100.
 *   → Obtener token BIE (registro gratuito) y guardarlo como INEGI_TOKEN.
 *
 * ⚠️  UMA 2026: valor en FALLBACK es estimado (INPC 2024 ~4.x%).
 *   Confirmar en DOF (31-ene-2026) o en https://www.inegi.org.mx/temas/uma/
 *   y actualizar manualmente vía admin → UMA Settings.
 */
class UmaService
{
    // ── Claves en tabla `configuraciones` ─────────────────────────────────
    const CLAVE_DIARIA   = 'uma_diaria';
    const CLAVE_MENSUAL  = 'uma_mensual';
    const CLAVE_ANUAL    = 'uma_anual';
    const CLAVE_VIGENCIA = 'uma_vigencia'; // año de vigencia del valor guardado

    // ── Valores de respaldo (UMA 2026, vigente desde 01-feb-2026) ─────────
    // ⚠ ESTIMADO — calculado con INPC 2024 ~4.x% sobre UMA 2025 ($113.14)
    // Confirmar valor oficial en DOF 31-ene-2026 o https://www.inegi.org.mx/temas/uma/
    // y actualizar vía admin → UMA Settings para sobreescribir este fallback.
    const FALLBACK_DIARIA  = 117.63;
    const FALLBACK_MENSUAL = 3575.95;  // diaria × 30.4
    const FALLBACK_ANUAL   = 42934.95; // diaria × 365
    // Referencia: UMA 2025 oficial → $113.14/día · $3,439.46/mes · $41,273.52/año

    // ── Indicador INEGI BIE para UMA diaria ───────────────────────────────
    // ID 539358 en el Banco de Información Económica (BIE) del INEGI
    // ⚠ Solo accesible con token BIE (NO con token DENUE/BISE)
    const INEGI_INDICADOR_UMA = '539358';

    // ─────────────────────────────────────────────────────────────────────
    // GETTERS — usan BD con cache, caen a fallback si no hay registro
    // ─────────────────────────────────────────────────────────────────────

    public static function getUmaDiaria(): float
    {
        return (float) Configuracion::get(self::CLAVE_DIARIA, self::FALLBACK_DIARIA);
    }

    public static function getUmaMensual(): float
    {
        // Siempre recalculado desde la diaria para consistencia
        return round(self::getUmaDiaria() * 30.4, 2);
    }

    public static function getUmaAnual(): float
    {
        return round(self::getUmaDiaria() * 365, 2);
    }

    public static function getVigencia(): string
    {
        return (string) Configuracion::get(self::CLAVE_VIGENCIA, date('Y'));
    }

    /** Resumen completo del valor UMA actual */
    public static function info(): array
    {
        return [
            'diaria'   => self::getUmaDiaria(),
            'mensual'  => self::getUmaMensual(),
            'anual'    => self::getUmaAnual(),
            'vigencia' => self::getVigencia(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // ACTUALIZACIÓN — consulta fuentes externas y guarda en BD
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Actualiza el valor de la UMA desde fuentes externas.
     * Devuelve array ['fuente' => '...', 'datos' => [...] | null]
     */
    public static function actualizar(): array
    {
        // Opción 1: API oficial INEGI (si hay token configurado)
        $token = config('services.inegi.token');
        if ($token) {
            $datos = self::fetchFromInegiApi($token);
            if ($datos) {
                self::guardar($datos);
                Log::info('[UmaService] UMA actualizada desde INEGI API', $datos);
                return ['fuente' => 'INEGI API oficial', 'datos' => $datos];
            }
        }

        // Opción 2: Scraping de la página INEGI
        $datos = self::fetchFromScraping();
        if ($datos) {
            self::guardar($datos);
            Log::info('[UmaService] UMA actualizada desde scraping INEGI', $datos);
            return ['fuente' => 'INEGI scraping', 'datos' => $datos];
        }

        Log::warning('[UmaService] No se pudo actualizar UMA desde fuentes externas.');
        return ['fuente' => 'sin_actualizacion', 'datos' => null];
    }

    // ─────────────────────────────────────────────────────────────────────
    // FUENTES EXTERNAS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Consulta la API oficial del Banco de Información Económica (BIE) del INEGI.
     *
     * Requiere token BIE en INEGI_TOKEN (.env).
     * Token DENUE ≠ token BIE → devuelve ErrorCode:100 si se usa token DENUE.
     * Registro gratuito: https://www.inegi.org.mx/app/api/indicadores/
     *
     * URL: https://www.inegi.org.mx/app/api/indicadores/desarrolladores/
     *       jsonxml/INDICATOR/{ID}/es/00/true/BIE/2.0/{TOKEN}?type=json
     */
    private static function fetchFromInegiApi(string $token): ?array
    {
        $id  = self::INEGI_INDICADOR_UMA;
        $url = "https://www.inegi.org.mx/app/api/indicadores/desarrolladores/jsonxml/INDICATOR/{$id}/es/00/true/BIE/2.0/{$token}?type=json";

        try {
            $response = Http::timeout(15)->get($url);

            if (! $response->ok()) {
                Log::warning("[UmaService] INEGI API respondió {$response->status()}");
                return null;
            }

            $json = $response->json();

            // Detectar errores explícitos de la API (ej. ErrorCode:100 = token incorrecto o indicador no encontrado)
            if (isset($json[0]) && str_contains($json[0] ?? '', 'ErrorCode')) {
                Log::warning('[UmaService] INEGI API error: ' . ($json[0] ?? ''));
                return null;
            }

            $obs = $json['Series'][0]['OBSERVATIONS'][0] ?? null;

            if (! $obs || empty($obs['OBS_VALUE'])) {
                Log::warning('[UmaService] INEGI API: respuesta sin observaciones.');
                return null;
            }

            $valor    = (float) $obs['OBS_VALUE'];
            $periodo  = $obs['TIME_PERIOD'] ?? date('Y') . '/01'; // "2025/01"
            $vigencia = (int) substr($periodo, 0, 4);

            // Validar rango razonable: UMA entre $50 y $1,000 diarios
            if ($valor < 50 || $valor > 1000) {
                Log::warning("[UmaService] Valor UMA fuera de rango: {$valor}");
                return null;
            }

            return ['diaria' => $valor, 'vigencia' => $vigencia];

        } catch (\Throwable $e) {
            Log::warning('[UmaService] INEGI API excepción: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Scraping de múltiples fuentes como respaldo cuando no hay token BIE.
     *  1. Servicio interno del BIE (endpoint no autenticado del visor interactivo)
     *  2. Boletín de prensa INEGI (URL fija por año)
     *  3. Página principal UMA del INEGI
     */
    private static function fetchFromScraping(): ?array
    {
        $año = (int) date('Y');

        // Fuente 1: servicio interno del visor BIE (no requiere token de desarrollador)
        $datos = self::scrapeVisorBIE();
        if ($datos) return $datos;

        // Fuente 2: boletín UMA del año actual en INEGI (PDF texto)
        $datos = self::scrapeBoletin($año);
        if ($datos) return $datos;

        // Fuente 3: página principal UMA (carga dinámica con JS, raramente funciona)
        $datos = self::scrapeUmaPage();
        if ($datos) return $datos;

        return null;
    }

    /**
     * Intenta leer el valor UMA desde el web service interno del visor BIE de INEGI.
     * Endpoint: https://www.inegi.org.mx/app/tabulados/serviciocuadros/wsDataService.svc
     *           /listaindicadorbiinegi/{serie}/false/0700/es/json/{añoInicio}/{añoFin}/000/000/BIE
     *
     * Este endpoint es el que usa el visor interactivo de INEGI sin autenticación.
     * Devuelve un array JSON con los datos de la serie; puede devolver [] si no hay datos.
     */
    private static function scrapeVisorBIE(): ?array
    {
        $id   = self::INEGI_INDICADOR_UMA;
        $año  = (int) date('Y');
        $base = 'https://www.inegi.org.mx/app/tabulados/serviciocuadros/wsDataService.svc';
        $url  = "{$base}/listaindicadorbiinegi/{$id}/false/0700/es/json/2016/{$año}/000/000/BIE";

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CRMInmobiliaria/1.0)',
                    'Referer'    => 'https://www.inegi.org.mx/temas/uma/',
                ])
                ->get($url);

            if (! $response->ok()) return null;

            $data = $response->json();

            // Buscar el último período disponible con valor UMA
            if (! is_array($data) || empty($data)) return null;

            // Los datos vienen como [[año, valor], [año, valor], ...]
            // o como objetos con claves variadas — extraer el más reciente
            $ultimo = null;
            foreach ($data as $item) {
                if (is_array($item)) {
                    $val = null;
                    // Intentar extraer valor numérico de la fila
                    foreach ($item as $v) {
                        if (is_numeric($v)) {
                            $f = (float) $v;
                            if ($f >= 70 && $f <= 300) {
                                $val = $f;
                                break;
                            }
                        }
                    }
                    if ($val !== null) $ultimo = $val;
                }
            }

            if ($ultimo !== null) {
                Log::info("[UmaService] Visor BIE: valor UMA encontrado: {$ultimo}");
                return ['diaria' => $ultimo, 'vigencia' => $año];
            }

        } catch (\Throwable $e) {
            Log::warning('[UmaService] Visor BIE scraping excepción: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Intenta descargar el boletín de prensa UMA del INEGI y extraer el valor diario.
     * URL patrón: https://www.inegi.org.mx/contenidos/saladeprensa/boletines/{año}/uma/uma{año}.pdf
     */
    private static function scrapeBoletin(int $año): ?array
    {
        // INEGI publica el boletín siempre en enero del año vigente
        $url = "https://www.inegi.org.mx/contenidos/saladeprensa/boletines/{$año}/uma/uma{$año}.pdf";

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; CRMInmobiliaria/1.0)'])
                ->get($url);

            if (! $response->ok()) return null;

            $content = $response->body();

            // Buscar el patrón numérico típico de UMA diaria (1XX.XX) en el contenido del PDF
            // El PDF puede tener texto embebido en bloques BT/ET
            if (preg_match_all('/\b(1\d{2}\.\d{2})\b/', $content, $m)) {
                foreach ($m[1] as $val) {
                    $v = (float) $val;
                    if ($v >= 80 && $v <= 200) {
                        Log::info("[UmaService] Boletín INEGI: valor UMA encontrado: {$v}");
                        return ['diaria' => $v, 'vigencia' => $año];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[UmaService] Boletín scraping excepción: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Scraping de la página de UMA del INEGI como respaldo.
     * URL: https://www.inegi.org.mx/temas/uma/
     */
    private static function scrapeUmaPage(): ?array
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CRMInmobiliaria/1.0)',
                    'Accept'     => 'text/html',
                ])
                ->get('https://www.inegi.org.mx/temas/uma/');

            if (! $response->ok()) return null;

            $html = $response->body();

            // Buscar patrón de UMA diaria en la tabla del INEGI.
            if (preg_match('/Diaria\s*[^0-9$]*\$?\s*(1\d{2}\.\d{2})/i', $html, $m)) {
                $valor = (float) $m[1];
                if ($valor >= 80 && $valor <= 200) {
                    return ['diaria' => $valor, 'vigencia' => (int) date('Y')];
                }
            }

            // Patrón 2: buscar valores numéricos típicos de UMA (1XX.XX)
            if (preg_match_all('/\b(1\d{2}\.\d{2})\b/', $html, $matches)) {
                foreach ($matches[1] as $val) {
                    $v = (float) $val;
                    if ($v >= 80 && $v <= 200) {
                        return ['diaria' => $v, 'vigencia' => (int) date('Y')];
                    }
                }
            }

            Log::warning('[UmaService] Scraping: no se encontró el valor UMA en el HTML.');
            return null;

        } catch (\Throwable $e) {
            Log::warning('[UmaService] Scraping excepción: ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PERSISTENCIA
    // ─────────────────────────────────────────────────────────────────────

    private static function guardar(array $datos): void
    {
        $diaria   = (float) $datos['diaria'];
        $mensual  = round($diaria * 30.4, 2);
        $anual    = round($diaria * 365, 2);
        $vigencia = $datos['vigencia'] ?? (int) date('Y');

        Configuracion::set(self::CLAVE_DIARIA,   $diaria);
        Configuracion::set(self::CLAVE_MENSUAL,   $mensual);
        Configuracion::set(self::CLAVE_ANUAL,     $anual);
        Configuracion::set(self::CLAVE_VIGENCIA,  $vigencia);
    }
}
