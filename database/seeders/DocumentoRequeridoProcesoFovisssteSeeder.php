<?php

namespace Database\Seeders;

use App\Models\DocumentoRequerido;
use App\Models\TipoTramite;
use Illuminate\Database\Seeder;

/**
 * Agrega los documentos faltantes del proceso FOVISSSTE completo (pasos 10–20).
 * Se usa updateOrCreate para que sea idempotente (seguro de correr múltiples veces).
 */
class DocumentoRequeridoProcesoFovisssteSeeder extends Seeder
{
    public function run(): void
    {
        // Slugs de todos los tipos FOVISSSTE hipotecarios
        $slugsFovissste = [
            'fovissste-tradicional',
            'fovissste-pensionados',
            'fovissste-conyugal',
            'fovissste-infonavit-individual',
            'fovissste-para-todos',
        ];

        // ── Documentos de vivienda adicionales (pasos 10, 12, 17) ──────────────
        $viviendaAdicional = [
            // Paso 10 — Catastro municipal
            ['nombre' => 'Apeo y deslinde',
             'seccion' => 'vivienda', 'obligatorio' => false, 'orden' => 8,
             'descripcion' => 'Solo si hay fracción a vender (predio > regla 3:1). Trámite en municipio.'],
            ['nombre' => 'Permiso de subdivisión',
             'seccion' => 'vivienda', 'obligatorio' => false, 'orden' => 9,
             'descripcion' => 'Solo si la propiedad es mayor a la relación 3:1 FOVISSSTE. Trámite en municipio.'],
            ['nombre' => 'Planos del predio total',
             'seccion' => 'vivienda', 'obligatorio' => true, 'orden' => 10,
             'descripcion' => 'Planos completos del predio total. Trámite en municipio.'],
            ['nombre' => 'Planos de la fracción a vender',
             'seccion' => 'vivienda', 'obligatorio' => false, 'orden' => 11,
             'descripcion' => 'Solo si se vende una fracción del predio.'],
            ['nombre' => 'Planos de distribución de vivienda',
             'seccion' => 'vivienda', 'obligatorio' => true, 'orden' => 12,
             'descripcion' => 'Distribución interior de la vivienda.'],

            // Paso 12–13 — Pre-avalúo
            ['nombre' => 'Pre-avalúo comercial',
             'seccion' => 'vivienda', 'obligatorio' => true, 'orden' => 13,
             'descripcion' => 'Pre-avalúo para revisión de datos antes del avalúo definitivo. Verificar datos de vendedor, comprador y vivienda.'],

            // Paso 17 — Avalúo cerrado (vigencia 6 meses)
            ['nombre' => 'Avalúo comercial cerrado',
             'seccion' => 'vivienda', 'obligatorio' => true, 'orden' => 14,
             'descripcion' => 'Avalúo definitivo con vigencia de 6 meses, emitido por unidad de valuación.'],

            // Paso 14 — 5 fotos del inmueble con número oficial (para CUV en RUV)
            ['nombre' => '5 fotografías del inmueble con número oficial',
             'seccion' => 'vivienda', 'obligatorio' => true, 'orden' => 15,
             'descripcion' => 'Requeridas para registrar el inmueble en el RUV y generar la CUV.'],
        ];

        // ── Documentos de trámite (sección "tramite") ──────────────────────────
        // Estos aplican al expediente en su conjunto, no al acreditado ni vendedor
        $tramiteAdicional = [
            // Paso 15 — CUV
            ['nombre' => 'Comprobante de pago CUV',
             'seccion' => 'tramite', 'obligatorio' => true, 'orden' => 1,
             'descripcion' => 'Comprobante de pago de la Clave Única de Vivienda (CUV) generada por SOFOM en el RUV.'],

            // Paso 18 — Instrucción notarial
            ['nombre' => 'Instrucción notarial de SOFOM',
             'seccion' => 'tramite', 'obligatorio' => true, 'orden' => 2,
             'descripcion' => 'Documento generado por SOFOM con condiciones crediticias, financieras y cuenta destino del vendedor.'],

            // Paso 19 — CLG
            ['nombre' => 'Certificado de Libertad de Gravamen (CLG)',
             'seccion' => 'tramite', 'obligatorio' => true, 'orden' => 3,
             'descripcion' => 'Tramitado por la notaría. Tiempo estimado: 30 días hábiles.'],

            // Paso 7 — Prestación de servicios
            ['nombre' => 'Contrato de prestación de servicios firmado',
             'seccion' => 'tramite', 'obligatorio' => true, 'orden' => 4,
             'descripcion' => 'Incluye aceptación de documentación de firma y entrega de escrituras.'],
        ];

        // ── Documento de vendedor adicional (paso 16 — avalúo referido) ────────
        $vendedorAdicional = [
            ['nombre' => 'Avalúo referido (exención ISR)',
             'seccion' => 'vendedor', 'obligatorio' => false, 'orden' => 9,
             'descripcion' => 'Solo si el vendedor vendió otra propiedad en los últimos 3 años. Costo variable según monto de crédito.'],
        ];

        // Insertar en todos los tipos FOVISSSTE hipotecarios
        foreach ($slugsFovissste as $slug) {
            $tipo = TipoTramite::where('slug', $slug)->first();
            if (! $tipo) continue;

            foreach (array_merge($viviendaAdicional, $tramiteAdicional, $vendedorAdicional) as $doc) {
                DocumentoRequerido::updateOrCreate(
                    [
                        'tipo_tramite_id' => $tipo->id,
                        'nombre'          => $doc['nombre'],
                        'seccion'         => $doc['seccion'],
                    ],
                    [
                        'obligatorio' => $doc['obligatorio'],
                        'orden'       => $doc['orden'],
                        'descripcion' => $doc['descripcion'],
                    ]
                );
            }
        }

        $this->command->info('Documentos del proceso FOVISSSTE agregados correctamente.');
    }
}
