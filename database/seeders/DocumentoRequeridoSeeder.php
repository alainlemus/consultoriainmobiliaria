<?php

namespace Database\Seeders;

use App\Models\DocumentoRequerido;
use App\Models\TipoTramite;
use Illuminate\Database\Seeder;

class DocumentoRequeridoSeeder extends Seeder
{
    public function run(): void
    {
        // Documentos compartidos reutilizables
        $acreditadoFovissste = [
            ['nombre' => 'Acta de nacimiento (2026)',                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
            ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
            ['nombre' => 'CURP 2026',                                       'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
            ['nombre' => 'Constancia de situación fiscal SAT 2026',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
            ['nombre' => 'Comprobante de domicilio actual',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
            ['nombre' => '3 últimos talones de pago vigentes',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
            ['nombre' => 'Estado de cuenta AFORE/SAR vigente',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7,  'descripcion' => 'Obtener desde app "Afore Móvil Pensión"'],
            ['nombre' => 'Acta de matrimonio (si aplica)',                   'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 8,  'descripcion' => null],
            ['nombre' => 'Carta de elección de mandataria firmada',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 9,  'descripcion' => null],
            ['nombre' => 'Formato Universal de Aclaración firmado',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => null],
        ];

        $acreditadoInfonait = [
            ['nombre' => 'Acta de nacimiento (2026)',                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
            ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
            ['nombre' => 'CURP 2026',                                       'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
            ['nombre' => 'Constancia de situación fiscal SAT 2026',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
            ['nombre' => 'Comprobante de domicilio actual',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
            ['nombre' => '3 últimos talones de pago vigentes',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
            ['nombre' => 'Número de Seguridad Social (NSS)',                'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7,  'descripcion' => 'Proporcionado por el IMSS'],
            ['nombre' => 'Acta de matrimonio (si aplica)',                   'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 8,  'descripcion' => null],
            ['nombre' => 'Solicitud de crédito INFONAVIT',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 9,  'descripcion' => 'Generada desde Mi Cuenta Infonavit'],
        ];

        $vendedorComun = [
            ['nombre' => 'Acta de nacimiento (2026)',                      'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
            ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
            ['nombre' => 'CURP 2026',                                       'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
            ['nombre' => 'Constancia de situación fiscal SAT 2026',         'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
            ['nombre' => 'RFC con homoclave',                               'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
            ['nombre' => 'Comprobante de domicilio actual',                 'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 6, 'descripcion' => null],
            ['nombre' => 'Acta de matrimonio (si aplica)',                   'seccion' => 'vendedor', 'obligatorio' => false, 'orden' => 7, 'descripcion' => 'Si en escritura o título menciona casado'],
            ['nombre' => 'Estado de cuenta bancaria destino (CLABE)',       'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 8, 'descripcion' => 'Cuenta donde llegará el recurso del pago'],
        ];

        $viviendaFovissste = [
            ['nombre' => 'Escritura pública o título de propiedad inscrita en RPP', 'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
            ['nombre' => 'Predial 2026 (al corriente)',                              'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
            ['nombre' => 'Recibo de agua 2026',                                      'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
            ['nombre' => 'Comprobante de luz actual',                                'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
            ['nombre' => 'Cédula o avalúo catastral',                                'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 5, 'descripcion' => 'Trámite en Catastro del municipio correspondiente'],
            ['nombre' => 'Alineamiento y número oficial',                            'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 6, 'descripcion' => 'Trámite en Catastro del municipio correspondiente'],
            ['nombre' => 'Permiso de subdivisión del predio (si aplica)',            'seccion' => 'vivienda', 'obligatorio' => false, 'orden' => 7, 'descripcion' => 'Solo si es fracción de predio, trámite en Catastro municipal'],
        ];

        $viviendaInfonait = array_merge($viviendaFovissste, [
            ['nombre' => 'Avalúo por perito autorizado INFONAVIT', 'seccion' => 'vivienda', 'obligatorio' => true, 'orden' => 8, 'descripcion' => 'Realizado por unidad de valuación registrada ante INFONAVIT'],
        ]);

        $data = [
            // ── FOVISSSTE ───────────────────────────────────────────────────────
            'fovissste-tradicional' => [
                ...$acreditadoFovissste,
                ...$vendedorComun,
                ...$viviendaFovissste,
            ],
            'fovissste-pensionados' => [
                ['nombre' => 'Acta de nacimiento (2026)',                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
                ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                ['nombre' => 'CURP 2026',                                       'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
                ['nombre' => 'Comprobante de domicilio actual',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
                ['nombre' => 'Resolución de pensión ISSSTE',                    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6, 'descripcion' => 'Documento que acredita la pensión vigente'],
                ['nombre' => 'Estado de cuenta AFORE/SAR vigente',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7, 'descripcion' => 'Obtener desde app "Afore Móvil Pensión"'],
                ['nombre' => 'Carta de elección de mandataria firmada',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 8, 'descripcion' => null],
                ['nombre' => 'Acta de matrimonio (si aplica)',                   'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 9, 'descripcion' => null],
                ...$vendedorComun,
                ...$viviendaFovissste,
            ],
            'fovissste-conyugal' => [
                // Cónyuge FOVISSSTE
                ['nombre' => 'Acta de nacimiento 2026 (cónyuge FOVISSSTE)',            'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
                ['nombre' => 'INE vigente (cónyuge FOVISSSTE)',                         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
                ['nombre' => 'CURP 2026 (cónyuge FOVISSSTE)',                           'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026 (cónyuge FOVISSSTE)', 'seccion' => 'acreditado', 'obligatorio' => true, 'orden' => 4, 'descripcion' => null],
                ['nombre' => 'Comprobante de domicilio (cónyuge FOVISSSTE)',            'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
                ['nombre' => '3 últimos talones de pago (cónyuge FOVISSSTE)',           'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
                ['nombre' => 'Estado de cuenta AFORE/SAR vigente (cónyuge FOVISSSTE)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7,  'descripcion' => null],
                ['nombre' => 'Carta de elección de mandataria firmada (FOVISSSTE)',    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 8,  'descripcion' => null],
                // Cónyuge INFONAVIT
                ['nombre' => 'Acta de nacimiento 2026 (cónyuge INFONAVIT)',            'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 9,  'descripcion' => null],
                ['nombre' => 'INE vigente (cónyuge INFONAVIT)',                         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => null],
                ['nombre' => 'CURP 2026 (cónyuge INFONAVIT)',                           'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 11, 'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026 (cónyuge INFONAVIT)', 'seccion' => 'acreditado', 'obligatorio' => true, 'orden' => 12, 'descripcion' => null],
                ['nombre' => 'NSS (cónyuge INFONAVIT)',                                'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 13, 'descripcion' => null],
                ['nombre' => 'Solicitud de crédito INFONAVIT',                         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 14, 'descripcion' => 'Generada desde Mi Cuenta Infonavit'],
                ['nombre' => 'Acta de matrimonio',                                     'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 15, 'descripcion' => 'Acredita vínculo conyugal entre los dos titulares'],
                ...$vendedorComun,
                ...$viviendaFovissste,
            ],
            'fovissste-infonavit-individual' => [
                ['nombre' => 'Acta de nacimiento (2026)',                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
                ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
                ['nombre' => 'CURP 2026',                                       'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
                ['nombre' => 'Comprobante de domicilio actual',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
                ['nombre' => '3 últimos talones de pago vigentes',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
                ['nombre' => 'Estado de cuenta AFORE/SAR vigente (FOVISSSTE)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7,  'descripcion' => 'Requerido para el trámite FOVISSSTE'],
                ['nombre' => 'Número de Seguridad Social (NSS) INFONAVIT',     'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 8,  'descripcion' => 'Requerido para el trámite INFONAVIT'],
                ['nombre' => 'Solicitud de crédito INFONAVIT',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 9,  'descripcion' => 'Generada desde Mi Cuenta Infonavit'],
                ['nombre' => 'Carta de elección de mandataria (FOVISSSTE)',    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => null],
                ['nombre' => 'Acta de matrimonio (si aplica)',                   'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 11, 'descripcion' => null],
                ...$vendedorComun,
                ...$viviendaInfonait,
            ],
            'fovissste-para-todos' => [
                ['nombre' => 'Acta de nacimiento (2026)',                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
                ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
                ['nombre' => 'CURP 2026',                                       'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
                ['nombre' => 'Comprobante de domicilio actual',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
                ['nombre' => '3 últimos talones de pago vigentes',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
                ['nombre' => 'Estado de cuenta AFORE/SAR vigente',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7,  'descripcion' => null],
                ['nombre' => 'Estados de cuenta bancarios (3 meses)',           'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 8,  'descripcion' => 'Requerido por el banco participante (HSBC, Banorte o BBVA)'],
                ['nombre' => 'Carta de elección de mandataria firmada',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 9,  'descripcion' => null],
                ['nombre' => 'Acta de matrimonio (si aplica)',                   'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 10, 'descripcion' => null],
                ...$vendedorComun,
                ...$viviendaFovissste,
            ],
            'fovissste-construyes' => [
                ['nombre' => 'Acta de nacimiento (2026)',                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
                ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                ['nombre' => 'CURP 2026',                                       'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
                ['nombre' => 'Comprobante de domicilio actual',                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
                ['nombre' => '3 últimos talones de pago vigentes',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6, 'descripcion' => null],
                ['nombre' => 'Estado de cuenta AFORE/SAR vigente',              'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7, 'descripcion' => null],
                ['nombre' => 'Carta de elección de mandataria firmada',         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 8, 'descripcion' => null],
                ['nombre' => 'Acta de matrimonio (si aplica)',                   'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 9, 'descripcion' => null],
                // Vendedor = terreno/constructor
                ['nombre' => 'Escrituras del terreno inscritas en RPP',         'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 1, 'descripcion' => 'El terreno debe estar inscrito en el Registro Público de la Propiedad'],
                ['nombre' => 'Predial del terreno al corriente',                'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                ['nombre' => 'Plano arquitectónico autorizado',                 'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 3, 'descripcion' => 'Con firma de DRO (Director Responsable de Obra)'],
                ['nombre' => 'Licencia de construcción',                        'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 4, 'descripcion' => 'Expedida por el municipio correspondiente'],
                ['nombre' => 'Presupuesto de obra detallado',                   'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
                // Vivienda = predio
                ['nombre' => 'Cédula o avalúo catastral del terreno',           'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 1, 'descripcion' => 'Trámite en Catastro municipal'],
                ['nombre' => 'Alineamiento y número oficial',                   'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 2, 'descripcion' => 'Trámite en Catastro municipal'],
                ['nombre' => 'Memoria descriptiva del proyecto',                'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                ['nombre' => 'Contrato de obra con constructor',                'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
            ],
            // ── INFONAVIT ───────────────────────────────────────────────────────
            'infonavit-tradicional' => [
                ...$acreditadoInfonait,
                ...$vendedorComun,
                ...$viviendaInfonait,
            ],
            'infonavit-total' => [
                ...$acreditadoInfonait,
                ...$vendedorComun,
                ...$viviendaInfonait,
            ],
            'cofinavit' => [
                ...$acreditadoInfonait,
                ['nombre' => 'Estados de cuenta bancarios (3 meses)',    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => 'Requerido por el banco participante'],
                ['nombre' => 'Comprobante de ingresos adicionales',       'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 11, 'descripcion' => 'Si se declaran ingresos complementarios'],
                ...$vendedorComun,
                ...$viviendaInfonait,
            ],
            'infonavit-unamos' => [
                ...$acreditadoInfonait,
                ['nombre' => 'Acta de nacimiento 2026 (cotitular)',                       'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => 'Segundo derechohabiente INFONAVIT'],
                ['nombre' => 'INE vigente (cotitular)',                                    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 11, 'descripcion' => null],
                ['nombre' => 'CURP 2026 (cotitular)',                                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 12, 'descripcion' => null],
                ['nombre' => 'NSS (cotitular)',                                            'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 13, 'descripcion' => null],
                ['nombre' => 'Solicitud de crédito INFONAVIT (cotitular)',                 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 14, 'descripcion' => 'Generada desde Mi Cuenta Infonavit del cotitular'],
                ['nombre' => 'Acta de matrimonio / parentesco o acuerdo de corresidencia', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 15, 'descripcion' => 'Documento que acredita la relación entre cotitulares'],
                ...$vendedorComun,
                ...$viviendaInfonait,
            ],
        ];

        foreach ($data as $slug => $docs) {
            $tipo = TipoTramite::where('slug', $slug)->first();
            if (! $tipo) continue;

            foreach ($docs as $doc) {
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
    }
}
