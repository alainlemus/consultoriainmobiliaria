<?php

namespace Database\Seeders;

use App\Models\TipoTramite;
use App\Models\EtapaTramite;
use App\Models\DocumentoRequerido;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CrmConfigSeeder extends Seeder
{
    public function run(): void
    {
        $tramites = [
            [
                'nombre'               => 'FOVISSSTE',
                'slug'                 => 'fovissste',
                'descripcion'          => 'Trámite de crédito FOVISSSTE para trabajadores del Estado.',
                'porcentaje_honorarios'=> 10.00,
                'orden'                => 1,
                'etapas' => [
                    ['nombre' => 'Expediente iniciado',          'color' => 'gray',   'orden' => 1, 'es_final' => false, 'descripcion' => 'Recolección de documentos del acreditado, vendedor y vivienda.'],
                    ['nombre' => 'Documentos completos',          'color' => 'blue',   'orden' => 2, 'es_final' => false, 'descripcion' => 'Todos los documentos recibidos, entregados a gestoría.'],
                    ['nombre' => 'Trámites previos',              'color' => 'yellow', 'orden' => 3, 'es_final' => false, 'descripcion' => 'Predial al corriente, catastro, alineamiento y número oficial.'],
                    ['nombre' => 'Avalúo realizado',              'color' => 'orange', 'orden' => 4, 'es_final' => false, 'descripcion' => 'Perito valuador emitió su dictamen.'],
                    ['nombre' => 'En notaría',                    'color' => 'purple', 'orden' => 5, 'es_final' => false, 'descripcion' => 'Notario revisa escrituras y prepara firma.'],
                    ['nombre' => 'Firma ante notario',            'color' => 'blue',   'orden' => 6, 'es_final' => false, 'descripcion' => 'Escritura firmada por todas las partes.'],
                    ['nombre' => 'Dispersión y cobro',            'color' => 'green',  'orden' => 7, 'es_final' => true,  'descripcion' => 'Crédito dispersado; honorarios y gastos cobrados. Trámite cerrado.'],
                ],
                'documentos' => [
                    // Acreditado
                    ['seccion' => 'acreditado', 'nombre' => 'Acta de nacimiento (2026)',                          'obligatorio' => true,  'orden' => 1],
                    ['seccion' => 'acreditado', 'nombre' => 'Credencial de elector INE vigente (anverso y reverso)', 'obligatorio' => true,  'orden' => 2],
                    ['seccion' => 'acreditado', 'nombre' => 'CURP 2026',                                          'obligatorio' => true,  'orden' => 3],
                    ['seccion' => 'acreditado', 'nombre' => 'Constancia de situación fiscal SAT 2026',            'obligatorio' => true,  'orden' => 4],
                    ['seccion' => 'acreditado', 'nombre' => 'Comprobante de domicilio actual',                    'obligatorio' => true,  'orden' => 5],
                    ['seccion' => 'acreditado', 'nombre' => '3 últimos talones de pago',                          'obligatorio' => true,  'orden' => 6],
                    ['seccion' => 'acreditado', 'nombre' => 'Estado de cuenta AFORE/SAR vigente',                 'obligatorio' => true,  'orden' => 7],
                    ['seccion' => 'acreditado', 'nombre' => 'Acta de matrimonio (si aplica)',                     'obligatorio' => false, 'orden' => 8],
                    ['seccion' => 'acreditado', 'nombre' => 'Carta de elección de mandataria firmada',            'obligatorio' => true,  'orden' => 9],
                    ['seccion' => 'acreditado', 'nombre' => 'Formato Universal de Aclaración firmado',            'obligatorio' => true,  'orden' => 10],
                    // Vendedor
                    ['seccion' => 'vendedor', 'nombre' => 'Acta de nacimiento',                                   'obligatorio' => true,  'orden' => 1],
                    ['seccion' => 'vendedor', 'nombre' => 'Credencial de elector INE vigente',                    'obligatorio' => true,  'orden' => 2],
                    ['seccion' => 'vendedor', 'nombre' => 'CURP',                                                  'obligatorio' => true,  'orden' => 3],
                    ['seccion' => 'vendedor', 'nombre' => 'Constancia de situación fiscal SAT',                   'obligatorio' => true,  'orden' => 4],
                    ['seccion' => 'vendedor', 'nombre' => 'Comprobante de domicilio',                             'obligatorio' => true,  'orden' => 5],
                    ['seccion' => 'vendedor', 'nombre' => 'Acta de matrimonio (si aplica)',                       'obligatorio' => false, 'orden' => 6],
                    ['seccion' => 'vendedor', 'nombre' => 'Estado de cuenta bancaria destino (CLABE)',            'obligatorio' => true,  'orden' => 7],
                    // Vivienda
                    ['seccion' => 'vivienda', 'nombre' => 'Escritura pública o título de propiedad',              'obligatorio' => true,  'orden' => 1],
                    ['seccion' => 'vivienda', 'nombre' => 'Predial 2026 (al corriente)',                          'obligatorio' => true,  'orden' => 2],
                    ['seccion' => 'vivienda', 'nombre' => 'Recibo de agua 2026',                                  'obligatorio' => true,  'orden' => 3],
                    ['seccion' => 'vivienda', 'nombre' => 'Comprobante de luz',                                   'obligatorio' => true,  'orden' => 4],
                    ['seccion' => 'vivienda', 'nombre' => 'Cédula o avalúo catastral',                            'obligatorio' => true,  'orden' => 5],
                    ['seccion' => 'vivienda', 'nombre' => 'Alineamiento y número oficial',                        'obligatorio' => true,  'orden' => 6],
                    ['seccion' => 'vivienda', 'nombre' => 'Permiso de subdivisión (si aplica)',                   'obligatorio' => false, 'orden' => 7],
                ],
            ],
            [
                'nombre'               => 'INFONAVIT',
                'slug'                 => 'infonavit',
                'descripcion'          => 'Trámite de crédito INFONAVIT para trabajadores del sector privado.',
                'porcentaje_honorarios'=> 10.00,
                'orden'                => 2,
                'etapas' => [
                    ['nombre' => 'Expediente iniciado',   'color' => 'gray',   'orden' => 1, 'es_final' => false, 'descripcion' => 'Recolección de documentos.'],
                    ['nombre' => 'Documentos completos',  'color' => 'blue',   'orden' => 2, 'es_final' => false, 'descripcion' => 'Documentos recibidos.'],
                    ['nombre' => 'Precalificación INFONAVIT', 'color' => 'yellow', 'orden' => 3, 'es_final' => false, 'descripcion' => 'Verificación del crédito en portal INFONAVIT.'],
                    ['nombre' => 'Trámites previos',      'color' => 'orange', 'orden' => 4, 'es_final' => false, 'descripcion' => 'Predial, catastro, alineamiento.'],
                    ['nombre' => 'Avalúo realizado',      'color' => 'purple', 'orden' => 5, 'es_final' => false, 'descripcion' => 'Perito valuador emitió dictamen.'],
                    ['nombre' => 'En notaría',            'color' => 'blue',   'orden' => 6, 'es_final' => false, 'descripcion' => 'Notario prepara escrituras.'],
                    ['nombre' => 'Firma y dispersión',    'color' => 'green',  'orden' => 7, 'es_final' => true,  'descripcion' => 'Escritura firmada, crédito dispersado, honorarios cobrados.'],
                ],
                'documentos' => [
                    ['seccion' => 'acreditado', 'nombre' => 'Acta de nacimiento',                                 'obligatorio' => true,  'orden' => 1],
                    ['seccion' => 'acreditado', 'nombre' => 'Credencial de elector INE vigente',                  'obligatorio' => true,  'orden' => 2],
                    ['seccion' => 'acreditado', 'nombre' => 'CURP',                                               'obligatorio' => true,  'orden' => 3],
                    ['seccion' => 'acreditado', 'nombre' => 'Constancia de situación fiscal SAT',                 'obligatorio' => true,  'orden' => 4],
                    ['seccion' => 'acreditado', 'nombre' => 'Comprobante de domicilio',                           'obligatorio' => true,  'orden' => 5],
                    ['seccion' => 'acreditado', 'nombre' => 'Últimos 3 recibos de nómina',                        'obligatorio' => true,  'orden' => 6],
                    ['seccion' => 'acreditado', 'nombre' => 'Estado de cuenta AFORE',                             'obligatorio' => true,  'orden' => 7],
                    ['seccion' => 'vendedor',   'nombre' => 'Acta de nacimiento',                                 'obligatorio' => true,  'orden' => 1],
                    ['seccion' => 'vendedor',   'nombre' => 'Credencial de elector INE',                          'obligatorio' => true,  'orden' => 2],
                    ['seccion' => 'vendedor',   'nombre' => 'CURP',                                               'obligatorio' => true,  'orden' => 3],
                    ['seccion' => 'vendedor',   'nombre' => 'Estado de cuenta bancaria destino',                  'obligatorio' => true,  'orden' => 4],
                    ['seccion' => 'vivienda',   'nombre' => 'Escritura o título de propiedad',                    'obligatorio' => true,  'orden' => 1],
                    ['seccion' => 'vivienda',   'nombre' => 'Predial al corriente',                               'obligatorio' => true,  'orden' => 2],
                    ['seccion' => 'vivienda',   'nombre' => 'Recibo de agua y luz',                               'obligatorio' => true,  'orden' => 3],
                    ['seccion' => 'vivienda',   'nombre' => 'Avalúo catastral',                                   'obligatorio' => true,  'orden' => 4],
                ],
            ],
            [
                'nombre'               => 'FOVISSSTE + INFONAVIT (Combo)',
                'slug'                 => 'fovissste-infonavit',
                'descripcion'          => 'Crédito cofinanciado entre FOVISSSTE e INFONAVIT.',
                'porcentaje_honorarios'=> 12.00,
                'orden'                => 3,
                'etapas' => [
                    ['nombre' => 'Expediente iniciado',          'color' => 'gray',   'orden' => 1, 'es_final' => false, 'descripcion' => 'Recolección de documentos de ambas instituciones.'],
                    ['nombre' => 'Documentos completos',          'color' => 'blue',   'orden' => 2, 'es_final' => false, 'descripcion' => 'Revisión y validación de los dos expedientes.'],
                    ['nombre' => 'Trámites previos',              'color' => 'yellow', 'orden' => 3, 'es_final' => false, 'descripcion' => 'Predial, catastro, alineamiento.'],
                    ['nombre' => 'Avalúo',                        'color' => 'orange', 'orden' => 4, 'es_final' => false, 'descripcion' => 'Avalúo vigente emitido.'],
                    ['nombre' => 'Coordinación notarial',         'color' => 'purple', 'orden' => 5, 'es_final' => false, 'descripcion' => 'Notario coordina ambas instituciones.'],
                    ['nombre' => 'Firma y dispersión',            'color' => 'green',  'orden' => 6, 'es_final' => true,  'descripcion' => 'Crédito cofinanciado dispersado.'],
                ],
                'documentos' => [
                    ['seccion' => 'acreditado', 'nombre' => 'Documentos FOVISSSTE completos',  'obligatorio' => true, 'orden' => 1],
                    ['seccion' => 'acreditado', 'nombre' => 'Documentos INFONAVIT completos',  'obligatorio' => true, 'orden' => 2],
                    ['seccion' => 'vendedor',   'nombre' => 'Documentos del vendedor',         'obligatorio' => true, 'orden' => 1],
                    ['seccion' => 'vivienda',   'nombre' => 'Documentos de la vivienda',       'obligatorio' => true, 'orden' => 1],
                ],
            ],
            [
                'nombre'               => 'Avalúo',
                'slug'                 => 'avaluo',
                'descripcion'          => 'Avalúo comercial o catastral de inmueble.',
                'porcentaje_honorarios'=> 0,
                'orden'                => 4,
                'etapas' => [
                    ['nombre' => 'Solicitud recibida',  'color' => 'gray',  'orden' => 1, 'es_final' => false, 'descripcion' => 'Datos del inmueble capturados.'],
                    ['nombre' => 'Visita programada',   'color' => 'blue',  'orden' => 2, 'es_final' => false, 'descripcion' => 'Perito agenda visita al inmueble.'],
                    ['nombre' => 'Dictamen emitido',    'color' => 'green', 'orden' => 3, 'es_final' => true,  'descripcion' => 'Avalúo entregado al cliente.'],
                ],
                'documentos' => [
                    ['seccion' => 'vivienda', 'nombre' => 'Escritura o título de propiedad', 'obligatorio' => true, 'orden' => 1],
                    ['seccion' => 'vivienda', 'nombre' => 'Predial vigente',                  'obligatorio' => true, 'orden' => 2],
                    ['seccion' => 'vivienda', 'nombre' => 'Plano de la propiedad (si existe)', 'obligatorio' => false, 'orden' => 3],
                ],
            ],
            [
                'nombre'               => 'Gestión de Escrituras',
                'slug'                 => 'escrituras',
                'descripcion'          => 'Regularización o trámite de escrituración de inmueble.',
                'porcentaje_honorarios'=> 0,
                'orden'                => 5,
                'etapas' => [
                    ['nombre' => 'Revisión de documentos', 'color' => 'gray',   'orden' => 1, 'es_final' => false, 'descripcion' => 'Se revisa situación jurídica del inmueble.'],
                    ['nombre' => 'Trámites previos',        'color' => 'yellow', 'orden' => 2, 'es_final' => false, 'descripcion' => 'Catastro, alineamiento, predial.'],
                    ['nombre' => 'En notaría',              'color' => 'blue',   'orden' => 3, 'es_final' => false, 'descripcion' => 'Notario prepara la escritura.'],
                    ['nombre' => 'Escritura firmada',       'color' => 'green',  'orden' => 4, 'es_final' => true,  'descripcion' => 'Escritura inscrita en el RPP y entregada.'],
                ],
                'documentos' => [
                    ['seccion' => 'acreditado', 'nombre' => 'Identificación oficial', 'obligatorio' => true, 'orden' => 1],
                    ['seccion' => 'acreditado', 'nombre' => 'CURP',                   'obligatorio' => true, 'orden' => 2],
                    ['seccion' => 'vivienda',   'nombre' => 'Documento de posesión o contrato', 'obligatorio' => true, 'orden' => 1],
                    ['seccion' => 'vivienda',   'nombre' => 'Predial al corriente',   'obligatorio' => true, 'orden' => 2],
                ],
            ],
            [
                'nombre'               => 'Asesoría Personalizada',
                'slug'                 => 'asesoria',
                'descripcion'          => 'Consultoría y orientación sobre créditos y bienes raíces.',
                'porcentaje_honorarios'=> 0,
                'orden'                => 6,
                'etapas' => [
                    ['nombre' => 'Cita agendada',       'color' => 'gray',  'orden' => 1, 'es_final' => false, 'descripcion' => 'Asesor programa la sesión de consultoría.'],
                    ['nombre' => 'Asesoría realizada',  'color' => 'green', 'orden' => 2, 'es_final' => true,  'descripcion' => 'Sesión completada y cliente orientado.'],
                ],
                'documentos' => [],
            ],
        ];

        foreach ($tramites as $data) {
            $etapas    = $data['etapas'];
            $documentos = $data['documentos'];
            unset($data['etapas'], $data['documentos']);

            $tipo = TipoTramite::firstOrCreate(['slug' => $data['slug']], $data);

            foreach ($etapas as $etapa) {
                EtapaTramite::firstOrCreate(
                    ['tipo_tramite_id' => $tipo->id, 'nombre' => $etapa['nombre']],
                    array_merge($etapa, ['tipo_tramite_id' => $tipo->id])
                );
            }

            foreach ($documentos as $doc) {
                DocumentoRequerido::firstOrCreate(
                    ['tipo_tramite_id' => $tipo->id, 'nombre' => $doc['nombre'], 'seccion' => $doc['seccion']],
                    array_merge($doc, ['tipo_tramite_id' => $tipo->id])
                );
            }
        }

        $this->command->info('✓ CRM: Tipos de trámite, etapas y documentos requeridos creados.');
    }
}
