<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoExpediente extends Model
{
    protected $table = 'documentos_expediente';

    protected $fillable = [
        'expediente_id',
        'tipo',
        'nombre',
        'seccion',
        'categoria',
        'estado',
        'notas',
        'ruta_archivo',
    ];

    // ─── IDs de TipoTramite ───────────────────────────────────────────────────
    // 1  = Crédito Tradicional FOVISSSTE
    // 2  = Crédito Pensionados FOVISSSTE
    // 3  = Crédito Conyugal FOVISSSTE
    // 4  = Avalúo Comercial
    // 5  = Gestión de Escrituras
    // 6  = Asesoría Personalizada
    // 7  = FOVISSSTE-INFONAVIT Individual
    // 8  = FOVISSSTE Para Todos (Bancos)
    // 9  = ConstruYes (Construcción FOVISSSTE)

    /**
     * Catálogo de documentos requeridos por tipo de trámite y tipo de inmueble.
     * vivienda_tipo: 'casa' | 'terreno' | null
     */
    public static function catalogoPara(int $tipoTramiteId, ?string $viviendaTipo): array
    {
        // ── Documentos base del acreditado (aplican a todos los créditos hipotecarios) ──
        $baseAcreditado = [
            ['tipo' => 'formato_asignacion_sofom',  'nombre' => 'Formato de Asignación SOFOM'],
            ['tipo' => 'ine',                        'nombre' => 'INE con QR (acreditado)'],
            ['tipo' => 'curp',                       'nombre' => 'CURP con QR'],
            ['tipo' => 'acta_nacimiento',            'nombre' => 'Acta de nacimiento'],
            ['tipo' => 'comprobante_domicilio',      'nombre' => 'Comprobante de domicilio (máx. 3 meses)'],
            ['tipo' => 'sit_fiscal',                 'nombre' => 'Constancia de situación fiscal (RFC)'],
        ];

        // ── Documentos del inmueble (compraventa) ──
        $docsInmueble = [];
        $tiposConInmueble = [1, 3, 7, 8]; // Tradicional, Conyugal, INFONAVIT-Individual, Para Todos
        if (in_array($tipoTramiteId, $tiposConInmueble) && $viviendaTipo) {
            $docsInmueble[] = ['tipo' => 'escritura',       'nombre' => 'Escritura del inmueble'];
            $docsInmueble[] = ['tipo' => 'predial',         'nombre' => 'Boleta predial vigente'];
            if ($viviendaTipo === 'casa') {
                $docsInmueble[] = ['tipo' => 'recibo_agua', 'nombre' => 'Recibo de agua'];
                $docsInmueble[] = ['tipo' => 'recibo_luz',  'nombre' => 'Recibo de luz'];
            }
        }

        return match ($tipoTramiteId) {

            // ── Crédito Tradicional FOVISSSTE ─────────────────────────────────
            1 => array_merge($baseAcreditado, [
                ['tipo' => 'talones_pago',        'nombre' => 'Último talón de pago (trabajador activo)'],
                ['tipo' => 'constancia_laboral',  'nombre' => 'Constancia o Expediente Electrónico ISSSTE'],
            ], $docsInmueble),

            // ── Crédito Pensionados FOVISSSTE ─────────────────────────────────
            2 => array_merge($baseAcreditado, [
                ['tipo' => 'credencial_pensionado', 'nombre' => 'Credencial de pensionado ISSSTE'],
                ['tipo' => 'recibo_pension',        'nombre' => 'Último recibo de pago de pensión'],
                ['tipo' => 'escritura',             'nombre' => 'Escritura del inmueble'],
                ['tipo' => 'predial',               'nombre' => 'Boleta predial vigente'],
                ['tipo' => 'recibo_agua',           'nombre' => 'Recibo de agua'],
                ['tipo' => 'recibo_luz',            'nombre' => 'Recibo de luz'],
            ]),

            // ── Crédito Conyugal FOVISSSTE ────────────────────────────────────
            3 => array_merge($baseAcreditado, [
                ['tipo' => 'talones_pago',          'nombre' => 'Último talón de pago (acreditado principal)'],
                ['tipo' => 'constancia_laboral',    'nombre' => 'Constancia o Expediente Electrónico ISSSTE'],
                ['tipo' => 'acta_matrimonio',       'nombre' => 'Acta de matrimonio (original y copia)'],
                ['tipo' => 'ine_conyuge',           'nombre' => 'INE con QR (cónyuge)'],
                ['tipo' => 'curp_conyuge',          'nombre' => 'CURP con QR (cónyuge)'],
                ['tipo' => 'talones_pago_conyuge',  'nombre' => 'Último talón de pago (cónyuge)'],
            ], $docsInmueble),

            // ── FOVISSSTE-INFONAVIT Individual ────────────────────────────────
            7 => array_merge($baseAcreditado, [
                ['tipo' => 'talones_pago',          'nombre' => 'Último talón de pago (FOVISSSTE)'],
                ['tipo' => 'constancia_laboral',    'nombre' => 'Constancia o Expediente Electrónico ISSSTE'],
                ['tipo' => 'precalificacion_infonavit', 'nombre' => 'Pre-calificación INFONAVIT'],
                ['tipo' => 'carta_autorizacion_infonavit', 'nombre' => 'Carta de autorización INFONAVIT (emite SOFOM)'],
            ], $docsInmueble),

            // ── FOVISSSTE Para Todos (Bancos) ─────────────────────────────────
            8 => array_merge($baseAcreditado, [
                ['tipo' => 'talones_pago',          'nombre' => 'Último talón de pago'],
                ['tipo' => 'constancia_laboral',    'nombre' => 'Constancia laboral o Expediente Electrónico'],
                ['tipo' => 'buro_credito',          'nombre' => 'Reporte de Buró de Crédito'],
                ['tipo' => 'solicitud_banco',       'nombre' => 'Solicitud de crédito bancario (HSBC/Banorte/BBVA)'],
            ], $docsInmueble),

            // ── ConstruYes (Construcción FOVISSSTE) ───────────────────────────
            9 => array_merge($baseAcreditado, [
                ['tipo' => 'talones_pago',          'nombre' => 'Último talón de pago (trabajador activo)'],
                ['tipo' => 'constancia_laboral',    'nombre' => 'Constancia o Expediente Electrónico ISSSTE'],
                ['tipo' => 'escritura_terreno',     'nombre' => 'Escrituras del terreno inscritas en RPP'],
                ['tipo' => 'plano_localizacion',    'nombre' => 'Plano de localización del terreno (norte geográfico + coordenadas)'],
                ['tipo' => 'factibilidad_agua',     'nombre' => 'Factibilidad de agua potable y drenaje'],
                ['tipo' => 'factibilidad_electrica', 'nombre' => 'Factibilidad eléctrica'],
                ['tipo' => 'fotografias_terreno',   'nombre' => 'Fotografías del terreno (mín. 9: 4 esquinas + 5 entorno)'],
            ]),

            // ── Avalúo Comercial ──────────────────────────────────────────────
            4 => [
                ['tipo' => 'escritura',             'nombre' => 'Escritura del inmueble inscrita en RPP'],
                ['tipo' => 'predial',               'nombre' => 'Boleta predial vigente'],
                ['tipo' => 'ine',                   'nombre' => 'INE del propietario'],
                ['tipo' => 'croquis_domicilio',     'nombre' => 'Croquis o plano de localización del inmueble'],
            ],

            // ── Gestión de Escrituras ─────────────────────────────────────────
            5 => [
                ['tipo' => 'escritura_anterior',    'nombre' => 'Escritura anterior del inmueble'],
                ['tipo' => 'predial',               'nombre' => 'Boleta predial vigente'],
                ['tipo' => 'ine',                   'nombre' => 'INE del vendedor y comprador'],
                ['tipo' => 'curp',                  'nombre' => 'CURP del vendedor y comprador'],
                ['tipo' => 'sit_fiscal',            'nombre' => 'Constancia de situación fiscal'],
            ],

            // ── Asesoría Personalizada ────────────────────────────────────────
            6 => [
                ['tipo' => 'curp',                  'nombre' => 'CURP con QR (para consulta en portal FOVISSSTE)'],
                ['tipo' => 'talones_pago',          'nombre' => 'Último talón de pago (referencia sueldo base)'],
            ],

            // ── Default (sin tipo específico) ─────────────────────────────────
            default => array_merge($baseAcreditado, [
                ['tipo' => 'talones_pago',          'nombre' => 'Último talón de pago'],
            ]),
        };
    }

    public static function estadoLabel(string $estado): string
    {
        return match($estado) {
            'recibido'  => 'Recibido',
            'no_aplica' => 'No aplica',
            default     => 'Pendiente',
        };
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
