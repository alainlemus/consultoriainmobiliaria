<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Tipos de trámite INFONAVIT ───────────────────────────────────────
        $tipos = [
            [
                'nombre'                => 'Crédito INFONAVIT Tradicional',
                'slug'                  => 'infonavit-tradicional',
                'descripcion'           => 'Adquisición de vivienda nueva o usada para trabajadores derechohabientes del IMSS. Tasa diferenciada por salario, plazo hasta 30 años. Descuento vía nómina.',
                'porcentaje_honorarios' => 8.00,
                'activo'                => true,
                'orden'                 => 10,
            ],
            [
                'nombre'                => 'Crédito INFONAVIT Total',
                'slug'                  => 'infonavit-total',
                'descripcion'           => 'Modalidad de crédito INFONAVIT en pesos (no en VSM). Monto máximo mayor, tasa fija en pesos. Para trabajadores con salario integrado desde 1 salario mínimo.',
                'porcentaje_honorarios' => 8.00,
                'activo'                => true,
                'orden'                 => 11,
            ],
            [
                'nombre'                => 'Cofinavit (INFONAVIT + Banco)',
                'slug'                  => 'cofinavit',
                'descripcion'           => 'Combina crédito INFONAVIT con crédito bancario para ampliar el monto total. El trabajador paga dos mensualidades: una al INFONAVIT (vía nómina) y otra al banco.',
                'porcentaje_honorarios' => 8.00,
                'activo'                => true,
                'orden'                 => 12,
            ],
            [
                'nombre'                => 'Unamos Créditos INFONAVIT',
                'slug'                  => 'infonavit-unamos',
                'descripcion'           => 'Dos derechohabientes INFONAVIT (cónyuges, familiares o corresidentes) suman sus subcuentas y capacidades de pago para obtener un mayor financiamiento.',
                'porcentaje_honorarios' => 8.00,
                'activo'                => true,
                'orden'                 => 13,
            ],
        ];

        foreach ($tipos as $tipo) {
            $exists = DB::table('tipo_tramites')->where('slug', $tipo['slug'])->exists();
            if (! $exists) {
                DB::table('tipo_tramites')->insert(array_merge($tipo, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // ─── Helper: insertar documentos si no existen ───────────────────────
        $insertDocs = function (int $tipoId, array $docs): void {
            foreach ($docs as $doc) {
                $exists = DB::table('documento_requeridos')
                    ->where('tipo_tramite_id', $tipoId)
                    ->where('nombre', $doc['nombre'])
                    ->where('seccion', $doc['seccion'])
                    ->exists();

                if (! $exists) {
                    DB::table('documento_requeridos')->insert(array_merge($doc, [
                        'tipo_tramite_id' => $tipoId,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]));
                }
            }
        };

        // ─── Documentos compartidos por sección ──────────────────────────────
        $acreditadoComun = [
            ['nombre' => 'Acta de nacimiento (2026)',                     'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
            ['nombre' => 'Credencial de elector INE vigente (ambos lados)','seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
            ['nombre' => 'CURP 2026',                                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
            ['nombre' => 'Constancia de situación fiscal SAT 2026',        'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
            ['nombre' => 'Comprobante de domicilio actual',                 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
            ['nombre' => '3 últimos talones de pago vigentes',             'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
            ['nombre' => 'Número de Seguridad Social (NSS)',               'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7,  'descripcion' => 'Proporcionado por el IMSS'],
            ['nombre' => 'Acta de matrimonio (si aplica)',                  'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 8,  'descripcion' => null],
            ['nombre' => 'Solicitud de crédito INFONAVIT',                 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 9,  'descripcion' => 'Generada desde Mi Cuenta Infonavit'],
        ];

        $vendedorComun = [
            ['nombre' => 'Acta de nacimiento (2026)',                     'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
            ['nombre' => 'Credencial de elector INE vigente (ambos lados)','seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
            ['nombre' => 'CURP 2026',                                      'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
            ['nombre' => 'Constancia de situación fiscal SAT 2026',        'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
            ['nombre' => 'RFC con homoclave',                              'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
            ['nombre' => 'Comprobante de domicilio actual',                'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 6, 'descripcion' => null],
            ['nombre' => 'Acta de matrimonio (si aplica)',                  'seccion' => 'vendedor', 'obligatorio' => false, 'orden' => 7, 'descripcion' => 'Si en escritura o título menciona casado'],
            ['nombre' => 'Estado de cuenta bancaria destino (CLABE)',      'seccion' => 'vendedor', 'obligatorio' => true,  'orden' => 8, 'descripcion' => 'Cuenta donde llegará el recurso del pago INFONAVIT'],
        ];

        $viviendaComun = [
            ['nombre' => 'Escritura pública o título de propiedad inscrita en RPP', 'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
            ['nombre' => 'Predial 2026 (al corriente)',                              'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
            ['nombre' => 'Recibo de agua 2026',                                      'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
            ['nombre' => 'Comprobante de luz actual',                                'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
            ['nombre' => 'Avalúo por perito autorizado INFONAVIT',                   'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 5, 'descripcion' => 'Realizado por unidad de valuación registrada ante INFONAVIT'],
            ['nombre' => 'Cédula o avalúo catastral',                                'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 6, 'descripcion' => 'Trámite en Catastro del municipio correspondiente'],
            ['nombre' => 'Alineamiento y número oficial',                            'seccion' => 'vivienda', 'obligatorio' => true,  'orden' => 7, 'descripcion' => 'Trámite en Catastro del municipio correspondiente'],
            ['nombre' => 'Permiso de subdivisión del predio (si aplica)',            'seccion' => 'vivienda', 'obligatorio' => false, 'orden' => 8, 'descripcion' => 'Solo si es fracción de predio'],
        ];

        // ─── INFONAVIT Tradicional ────────────────────────────────────────────
        $idTradicional = DB::table('tipo_tramites')->where('slug', 'infonavit-tradicional')->value('id');
        $insertDocs($idTradicional, $acreditadoComun);
        $insertDocs($idTradicional, $vendedorComun);
        $insertDocs($idTradicional, $viviendaComun);

        // ─── INFONAVIT Total ──────────────────────────────────────────────────
        $idTotal = DB::table('tipo_tramites')->where('slug', 'infonavit-total')->value('id');
        $insertDocs($idTotal, $acreditadoComun);
        $insertDocs($idTotal, $vendedorComun);
        $insertDocs($idTotal, $viviendaComun);

        // ─── Cofinavit (INFONAVIT + Banco) ───────────────────────────────────
        $idCofinavit = DB::table('tipo_tramites')->where('slug', 'cofinavit')->value('id');
        $acreditadoCofinavit = array_merge($acreditadoComun, [
            ['nombre' => 'Estados de cuenta bancarios (3 meses)',    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => 'Requerido por el banco participante'],
            ['nombre' => 'Comprobante de ingresos adicionales',       'seccion' => 'acreditado', 'obligatorio' => false, 'orden' => 11, 'descripcion' => 'Si se declaran ingresos complementarios'],
        ]);
        $insertDocs($idCofinavit, $acreditadoCofinavit);
        $insertDocs($idCofinavit, $vendedorComun);
        $insertDocs($idCofinavit, $viviendaComun);

        // ─── Unamos Créditos INFONAVIT ────────────────────────────────────────
        $idUnamos = DB::table('tipo_tramites')->where('slug', 'infonavit-unamos')->value('id');
        // Cada cotitular necesita los mismos docs del acreditado
        $acreditadoUnamos = $acreditadoComun;
        // Agregar docs del segundo cotitular
        $acreditadoUnamos[] = ['nombre' => 'Acta de nacimiento 2026 (cotitular)',                      'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => 'Segundo derechohabiente INFONAVIT'];
        $acreditadoUnamos[] = ['nombre' => 'INE vigente (cotitular)',                                  'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 11, 'descripcion' => null];
        $acreditadoUnamos[] = ['nombre' => 'CURP 2026 (cotitular)',                                    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 12, 'descripcion' => null];
        $acreditadoUnamos[] = ['nombre' => 'NSS (cotitular)',                                          'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 13, 'descripcion' => null];
        $acreditadoUnamos[] = ['nombre' => 'Solicitud de crédito INFONAVIT (cotitular)',               'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 14, 'descripcion' => 'Generada desde Mi Cuenta Infonavit del cotitular'];
        $acreditadoUnamos[] = ['nombre' => 'Acta de matrimonio / parentesco o acuerdo de corresidencia','seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 15, 'descripcion' => 'Documento que acredita la relación entre cotitulares'];
        $insertDocs($idUnamos, $acreditadoUnamos);
        $insertDocs($idUnamos, $vendedorComun);
        $insertDocs($idUnamos, $viviendaComun);

        // ─── Completar Conyugal FOVISSSTE-INFONAVIT (tipo_tramite_id = 3) ────
        // Reemplazar los 4 registros genéricos con documentos reales
        $idConyugal = DB::table('tipo_tramites')->where('slug', 'fovissste-conyugal')->value('id');
        if ($idConyugal) {
            // Borrar los genéricos anteriores
            DB::table('documento_requeridos')
                ->where('tipo_tramite_id', $idConyugal)
                ->whereIn('nombre', [
                    'Documentos FOVISSSTE completos',
                    'Documentos INFONAVIT completos',
                    'Documentos del vendedor',
                    'Documentos de la vivienda',
                ])
                ->delete();

            $acreditadoConyugal = [
                // Cónyuge FOVISSSTE
                ['nombre' => 'Acta de nacimiento 2026 (cónyuge FOVISSSTE)',           'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
                ['nombre' => 'INE vigente (cónyuge FOVISSSTE)',                        'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
                ['nombre' => 'CURP 2026 (cónyuge FOVISSSTE)',                          'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026 (cónyuge FOVISSSTE)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
                ['nombre' => 'Comprobante de domicilio (cónyuge FOVISSSTE)',           'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
                ['nombre' => '3 últimos talones de pago (cónyuge FOVISSSTE)',          'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
                ['nombre' => 'Estado de cuenta AFORE/SAR vigente (cónyuge FOVISSSTE)','seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 7,  'descripcion' => null],
                ['nombre' => 'Carta de elección de mandataria firmada (FOVISSSTE)',    'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 8,  'descripcion' => null],
                // Cónyuge INFONAVIT
                ['nombre' => 'Acta de nacimiento 2026 (cónyuge INFONAVIT)',           'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 9,  'descripcion' => null],
                ['nombre' => 'INE vigente (cónyuge INFONAVIT)',                        'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 10, 'descripcion' => null],
                ['nombre' => 'CURP 2026 (cónyuge INFONAVIT)',                          'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 11, 'descripcion' => null],
                ['nombre' => 'Constancia de situación fiscal SAT 2026 (cónyuge INFONAVIT)', 'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 12, 'descripcion' => null],
                ['nombre' => 'NSS (cónyuge INFONAVIT)',                                'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 13, 'descripcion' => null],
                ['nombre' => 'Solicitud de crédito INFONAVIT',                         'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 14, 'descripcion' => 'Generada desde Mi Cuenta Infonavit'],
                // Compartidos
                ['nombre' => 'Acta de matrimonio',                                     'seccion' => 'acreditado', 'obligatorio' => true,  'orden' => 15, 'descripcion' => 'Acredita vínculo conyugal entre los dos titulares'],
            ];

            $insertDocs($idConyugal, $acreditadoConyugal);
            $insertDocs($idConyugal, $vendedorComun);
            $insertDocs($idConyugal, $viviendaComun);
        }
    }

    public function down(): void
    {
        $slugs = ['infonavit-tradicional', 'infonavit-total', 'cofinavit', 'infonavit-unamos'];

        $ids = DB::table('tipo_tramites')->whereIn('slug', $slugs)->pluck('id');
        DB::table('documento_requeridos')->whereIn('tipo_tramite_id', $ids)->delete();
        DB::table('tipo_tramites')->whereIn('slug', $slugs)->delete();
    }
};
