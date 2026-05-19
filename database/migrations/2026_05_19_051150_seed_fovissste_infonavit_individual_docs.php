<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Los tipos sin documentos requeridos aún
        $pendientes = [
            // FOVISSSTE-INFONAVIT Individual: mismos docs que FOVISSSTE + NSS de INFONAVIT
            'fovissste-infonavit-individual' => [
                'acreditado' => [
                    ['nombre' => 'Acta de nacimiento (2026)',                      'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
                    ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
                    ['nombre' => 'CURP 2026',                                       'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
                    ['nombre' => 'Constancia de situación fiscal SAT 2026',         'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
                    ['nombre' => 'Comprobante de domicilio actual',                  'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
                    ['nombre' => '3 últimos talones de pago vigentes',              'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
                    ['nombre' => 'Estado de cuenta AFORE/SAR vigente (FOVISSSTE)',  'obligatorio' => true,  'orden' => 7,  'descripcion' => 'Requerido para el trámite FOVISSSTE'],
                    ['nombre' => 'Número de Seguridad Social (NSS) INFONAVIT',      'obligatorio' => true,  'orden' => 8,  'descripcion' => 'Requerido para el trámite INFONAVIT'],
                    ['nombre' => 'Solicitud de crédito INFONAVIT',                  'obligatorio' => true,  'orden' => 9,  'descripcion' => 'Generada desde Mi Cuenta Infonavit'],
                    ['nombre' => 'Carta de elección de mandataria (FOVISSSTE)',     'obligatorio' => true,  'orden' => 10, 'descripcion' => null],
                    ['nombre' => 'Acta de matrimonio (si aplica)',                   'obligatorio' => false, 'orden' => 11, 'descripcion' => null],
                ],
                'vendedor' => [
                    ['nombre' => 'Acta de nacimiento (2026)',                      'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
                    ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                    ['nombre' => 'CURP 2026',                                       'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                    ['nombre' => 'Constancia de situación fiscal SAT 2026',         'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
                    ['nombre' => 'RFC con homoclave',                               'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
                    ['nombre' => 'Comprobante de domicilio actual',                 'obligatorio' => true,  'orden' => 6, 'descripcion' => null],
                    ['nombre' => 'Acta de matrimonio (si aplica)',                   'obligatorio' => false, 'orden' => 7, 'descripcion' => 'Si en escritura o título menciona casado'],
                    ['nombre' => 'Estado de cuenta bancaria destino (CLABE)',       'obligatorio' => true,  'orden' => 8, 'descripcion' => 'Donde llegará el pago de ambas instituciones'],
                ],
                'vivienda' => [
                    ['nombre' => 'Escritura pública o título de propiedad inscrita en RPP', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
                    ['nombre' => 'Predial 2026 (al corriente)',                              'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                    ['nombre' => 'Recibo de agua 2026',                                      'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                    ['nombre' => 'Comprobante de luz actual',                                'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
                    ['nombre' => 'Avalúo por perito autorizado INFONAVIT',                   'obligatorio' => true,  'orden' => 5, 'descripcion' => 'Requerido por INFONAVIT'],
                    ['nombre' => 'Cédula o avalúo catastral',                                'obligatorio' => true,  'orden' => 6, 'descripcion' => 'Trámite en Catastro municipal'],
                    ['nombre' => 'Alineamiento y número oficial',                            'obligatorio' => true,  'orden' => 7, 'descripcion' => 'Trámite en Catastro municipal'],
                    ['nombre' => 'Permiso de subdivisión del predio (si aplica)',            'obligatorio' => false, 'orden' => 8, 'descripcion' => null],
                ],
            ],
            // FOVISSSTE Para Todos (Bancos)
            'fovissste-para-todos' => [
                'acreditado' => [
                    ['nombre' => 'Acta de nacimiento (2026)',                      'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
                    ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
                    ['nombre' => 'CURP 2026',                                       'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
                    ['nombre' => 'Constancia de situación fiscal SAT 2026',         'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
                    ['nombre' => 'Comprobante de domicilio actual',                  'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
                    ['nombre' => '3 últimos talones de pago vigentes',              'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
                    ['nombre' => 'Estado de cuenta AFORE/SAR vigente',              'obligatorio' => true,  'orden' => 7,  'descripcion' => null],
                    ['nombre' => 'Estados de cuenta bancarios (3 meses)',           'obligatorio' => true,  'orden' => 8,  'descripcion' => 'Requerido por el banco participante (HSBC, Banorte o BBVA)'],
                    ['nombre' => 'Carta de elección de mandataria (FOVISSSTE)',     'obligatorio' => true,  'orden' => 9,  'descripcion' => null],
                    ['nombre' => 'Acta de matrimonio (si aplica)',                   'obligatorio' => false, 'orden' => 10, 'descripcion' => null],
                ],
                'vendedor' => [
                    ['nombre' => 'Acta de nacimiento (2026)',                      'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
                    ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                    ['nombre' => 'CURP 2026',                                       'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                    ['nombre' => 'Constancia de situación fiscal SAT 2026',         'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
                    ['nombre' => 'RFC con homoclave',                               'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
                    ['nombre' => 'Comprobante de domicilio actual',                 'obligatorio' => true,  'orden' => 6, 'descripcion' => null],
                    ['nombre' => 'Acta de matrimonio (si aplica)',                   'obligatorio' => false, 'orden' => 7, 'descripcion' => null],
                    ['nombre' => 'Estado de cuenta bancaria destino (CLABE)',       'obligatorio' => true,  'orden' => 8, 'descripcion' => null],
                ],
                'vivienda' => [
                    ['nombre' => 'Escritura pública o título de propiedad inscrita en RPP', 'obligatorio' => true,  'orden' => 1, 'descripcion' => null],
                    ['nombre' => 'Predial 2026 (al corriente)',                              'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                    ['nombre' => 'Recibo de agua 2026',                                      'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                    ['nombre' => 'Comprobante de luz actual',                                'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
                    ['nombre' => 'Cédula o avalúo catastral',                                'obligatorio' => true,  'orden' => 5, 'descripcion' => 'Trámite en Catastro municipal'],
                    ['nombre' => 'Alineamiento y número oficial',                            'obligatorio' => true,  'orden' => 6, 'descripcion' => 'Trámite en Catastro municipal'],
                    ['nombre' => 'Permiso de subdivisión del predio (si aplica)',            'obligatorio' => false, 'orden' => 7, 'descripcion' => null],
                ],
            ],
            // ConstruYes (Construcción FOVISSSTE)
            'fovissste-construyes' => [
                'acreditado' => [
                    ['nombre' => 'Acta de nacimiento (2026)',                      'obligatorio' => true,  'orden' => 1,  'descripcion' => null],
                    ['nombre' => 'Credencial de elector INE vigente (ambos lados)', 'obligatorio' => true,  'orden' => 2,  'descripcion' => null],
                    ['nombre' => 'CURP 2026',                                       'obligatorio' => true,  'orden' => 3,  'descripcion' => null],
                    ['nombre' => 'Constancia de situación fiscal SAT 2026',         'obligatorio' => true,  'orden' => 4,  'descripcion' => null],
                    ['nombre' => 'Comprobante de domicilio actual',                  'obligatorio' => true,  'orden' => 5,  'descripcion' => null],
                    ['nombre' => '3 últimos talones de pago vigentes',              'obligatorio' => true,  'orden' => 6,  'descripcion' => null],
                    ['nombre' => 'Estado de cuenta AFORE/SAR vigente',              'obligatorio' => true,  'orden' => 7,  'descripcion' => null],
                    ['nombre' => 'Carta de elección de mandataria (FOVISSSTE)',     'obligatorio' => true,  'orden' => 8,  'descripcion' => null],
                    ['nombre' => 'Acta de matrimonio (si aplica)',                   'obligatorio' => false, 'orden' => 9,  'descripcion' => null],
                ],
                'vendedor' => [
                    ['nombre' => 'Escrituras del terreno inscritas en RPP',         'obligatorio' => true,  'orden' => 1, 'descripcion' => 'El terreno debe estar inscrito en el Registro Público de la Propiedad'],
                    ['nombre' => 'Predial del terreno al corriente',                'obligatorio' => true,  'orden' => 2, 'descripcion' => null],
                    ['nombre' => 'Plano arquitectónico autorizado',                 'obligatorio' => true,  'orden' => 3, 'descripcion' => 'Con firma de DRO (Director Responsable de Obra)'],
                    ['nombre' => 'Licencia de construcción',                        'obligatorio' => true,  'orden' => 4, 'descripcion' => 'Expedida por el municipio correspondiente'],
                    ['nombre' => 'Presupuesto de obra detallado',                   'obligatorio' => true,  'orden' => 5, 'descripcion' => null],
                ],
                'vivienda' => [
                    ['nombre' => 'Cédula o avalúo catastral del terreno',           'obligatorio' => true,  'orden' => 1, 'descripcion' => 'Trámite en Catastro municipal'],
                    ['nombre' => 'Alineamiento y número oficial',                   'obligatorio' => true,  'orden' => 2, 'descripcion' => 'Trámite en Catastro municipal'],
                    ['nombre' => 'Memoria descriptiva del proyecto',                'obligatorio' => true,  'orden' => 3, 'descripcion' => null],
                    ['nombre' => 'Contrato de obra con constructor',                'obligatorio' => true,  'orden' => 4, 'descripcion' => null],
                ],
            ],
        ];

        foreach ($pendientes as $slug => $secciones) {
            $tipoId = DB::table('tipo_tramites')->where('slug', $slug)->value('id');
            if (! $tipoId) continue;

            foreach ($secciones as $seccion => $docs) {
                foreach ($docs as $doc) {
                    $exists = DB::table('documento_requeridos')
                        ->where('tipo_tramite_id', $tipoId)
                        ->where('nombre', $doc['nombre'])
                        ->where('seccion', $seccion)
                        ->exists();
                    if (! $exists) {
                        DB::table('documento_requeridos')->insert([
                            'tipo_tramite_id' => $tipoId,
                            'seccion'         => $seccion,
                            'nombre'          => $doc['nombre'],
                            'descripcion'     => $doc['descripcion'],
                            'obligatorio'     => $doc['obligatorio'],
                            'orden'           => $doc['orden'],
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $slugs = ['fovissste-infonavit-individual', 'fovissste-para-todos', 'fovissste-construyes'];
        $ids   = DB::table('tipo_tramites')->whereIn('slug', $slugs)->pluck('id');
        DB::table('documento_requeridos')->whereIn('tipo_tramite_id', $ids)->delete();
    }
};
