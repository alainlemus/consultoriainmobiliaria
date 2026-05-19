<?php

namespace Database\Seeders;

use App\Models\EtapaTramite;
use App\Models\TipoTramite;
use Illuminate\Database\Seeder;

class TipoTramiteSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            // ─── Productos FOVISSSTE principales ────────────────────────────────
            [
                'id'                   => 1,
                'nombre'               => 'Crédito Tradicional FOVISSSTE',
                'slug'                 => 'fovissste-tradicional',
                'descripcion'          => 'Adquisición de vivienda nueva o usada para trabajadores en activo del ISSSTE. Tasa 2–6%, plazo 30 años, descuento quincenal del 30% del sueldo base. Disponible en pesos y UMAs.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 1,
            ],
            [
                'id'                   => 2,
                'nombre'               => 'Crédito Pensionados FOVISSSTE',
                'slug'                 => 'fovissste-pensionados',
                'descripcion'          => 'Para derechohabientes pensionados del ISSSTE (47–74 años). Monto máx. $703,012.89 (213 UMAs). Tasa 2–4.45%, plazo 20 años, amortización 20% de la pensión. Incluye seguro de vida.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 2,
            ],
            [
                'id'                   => 3,
                'nombre'               => 'Crédito Conyugal FOVISSSTE',
                'slug'                 => 'fovissste-conyugal',
                'descripcion'          => 'Suma la capacidad de crédito de ambos cónyuges que cotizan al FOVISSSTE o cuando uno cotiza al FOVISSSTE y otro al INFONAVIT. Tasa 4–6%, plazo 30 años.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 3,
            ],
            [
                'id'                   => 7,
                'nombre'               => 'FOVISSSTE-INFONAVIT Individual',
                'slug'                 => 'fovissste-infonavit-individual',
                'descripcion'          => 'Un derechohabiente que cotiza simultáneamente al FOVISSSTE e INFONAVIT suma el 100% de crédito de cada institución. Solo primer crédito en ambas. Tasa 4–6%, plazo 30 años.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 4,
            ],
            [
                'id'                   => 8,
                'nombre'               => 'FOVISSSTE Para Todos (Bancos)',
                'slug'                 => 'fovissste-para-todos',
                'descripcion'          => 'Esquema bancario vía HSBC, Banorte o BBVA. Primer y segundo crédito. Edad 25–70 años. Tasa fija 10.38%, CAT 11.40%. Plazos: 5, 10, 15, 20, 23 y 25 años. Monto $100K–$4.8M + SSV. Sin actualizaciones UMA.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 5,
            ],
            [
                'id'                   => 9,
                'nombre'               => 'ConstruYes (Construcción FOVISSSTE)',
                'slug'                 => 'fovissste-construyes',
                'descripcion'          => 'Crédito para construcción en terreno propio o con adquisición de suelo. Requiere escrituras del terreno inscritas en RPP. Mismas condiciones financieras que Crédito Tradicional.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 6,
            ],
            // ─── Productos INFONAVIT ─────────────────────────────────────────────
            [
                'id'                   => 10,
                'nombre'               => 'Crédito INFONAVIT Tradicional',
                'slug'                 => 'infonavit-tradicional',
                'descripcion'          => 'Adquisición de vivienda nueva o usada para trabajadores derechohabientes del IMSS. Tasa diferenciada por salario, plazo hasta 30 años. Descuento vía nómina.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 10,
            ],
            [
                'id'                   => 11,
                'nombre'               => 'Crédito INFONAVIT Total',
                'slug'                 => 'infonavit-total',
                'descripcion'          => 'Modalidad de crédito INFONAVIT en pesos (no en VSM). Monto máximo mayor, tasa fija en pesos. Para trabajadores con salario integrado desde 1 salario mínimo.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 11,
            ],
            [
                'id'                   => 12,
                'nombre'               => 'Cofinavit (INFONAVIT + Banco)',
                'slug'                 => 'cofinavit',
                'descripcion'          => 'Combina crédito INFONAVIT con crédito bancario para ampliar el monto total. El trabajador paga dos mensualidades: una al INFONAVIT (vía nómina) y otra al banco.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 12,
            ],
            [
                'id'                   => 13,
                'nombre'               => 'Unamos Créditos INFONAVIT',
                'slug'                 => 'infonavit-unamos',
                'descripcion'          => 'Dos derechohabientes INFONAVIT (cónyuges, familiares o corresidentes) suman sus subcuentas y capacidades de pago para obtener un mayor financiamiento.',
                'porcentaje_honorarios' => 8.00,
                'activo'               => true,
                'orden'                => 13,
            ],
            // ─── Servicios complementarios (se conservan) ────────────────────
            [
                'id'                   => 4,
                'nombre'               => 'Avalúo Comercial',
                'slug'                 => 'avaluo',
                'descripcion'          => 'Servicio de avalúo comercial del inmueble requerido para el trámite hipotecario.',
                'porcentaje_honorarios' => 0.00,
                'activo'               => true,
                'orden'                => 7,
            ],
            [
                'id'                   => 5,
                'nombre'               => 'Gestión de Escrituras',
                'slug'                 => 'escrituras',
                'descripcion'          => 'Gestión y seguimiento del proceso notarial y registro en RPP.',
                'porcentaje_honorarios' => 0.00,
                'activo'               => true,
                'orden'                => 8,
            ],
            [
                'id'                   => 6,
                'nombre'               => 'Asesoría Personalizada',
                'slug'                 => 'asesoria',
                'descripcion'          => 'Asesoría financiera sobre la prestación FOVISSSTE, simulación de crédito y precalificación.',
                'porcentaje_honorarios' => 0.00,
                'activo'               => true,
                'orden'                => 9,
            ],
        ];

        foreach ($tipos as $tipo) {
            // Buscar primero por id, luego por slug para evitar duplicados
            $existing = TipoTramite::find($tipo['id'])
                ?? TipoTramite::where('slug', $tipo['slug'])->first();

            if ($existing) {
                $existing->update([
                    'nombre'               => $tipo['nombre'],
                    'slug'                 => $tipo['slug'],
                    'descripcion'          => $tipo['descripcion'],
                    'porcentaje_honorarios' => $tipo['porcentaje_honorarios'],
                    'activo'               => $tipo['activo'],
                    'orden'                => $tipo['orden'],
                ]);
            } else {
                TipoTramite::insert(array_merge($tipo, ['activo' => (int) $tipo['activo'],
                    'created_at' => now(), 'updated_at' => now()]));
            }
        }

        // ─── Etapas para tipos nuevos ─────────────────────────────────────────
        $this->seedEtapas();
    }

    private function seedEtapas(): void
    {
        // Etapas comunes para todos los créditos hipotecarios FOVISSSTE
        $etapasHipotecarias = [
            ['nombre' => 'Expediente iniciado',          'descripcion' => 'Contacto inicial y recopilación de datos básicos.',            'orden' => 1],
            ['nombre' => 'Documentos completos',         'descripcion' => 'Checklist de documentos validado y completo.',                  'orden' => 2],
            ['nombre' => 'Validación SOFOM',             'descripcion' => 'Expediente enviado y en revisión por DAE Hipotecaria.',         'orden' => 3],
            ['nombre' => 'Avalúo realizado',             'descripcion' => 'Avalúo comercial del inmueble concluido.',                      'orden' => 4],
            ['nombre' => 'Asignación a notaría',        'descripcion' => 'Expediente asignado a notaría para escrituración.',              'orden' => 5],
            ['nombre' => 'Firma ante notario',           'descripcion' => 'Firma de escrituras realizada ante notario público.',            'orden' => 6],
            ['nombre' => 'Dispersión y cobro',           'descripcion' => 'FOVISSSTE libera el pago al vendedor. Cobro de honorarios.',    'orden' => 7],
        ];

        // Tipos de crédito hipotecario nuevos que no tienen etapas aún
        $tiposNuevos = [
            2  => 'fovissste-pensionados',
            7  => 'fovissste-infonavit-individual',
            8  => 'fovissste-para-todos',
            9  => 'fovissste-construyes',
            10 => 'infonavit-tradicional',
            11 => 'infonavit-total',
            12 => 'cofinavit',
            13 => 'infonavit-unamos',
        ];

        foreach ($tiposNuevos as $tipoId => $slug) {
            $tipo = TipoTramite::find($tipoId);
            if (! $tipo) {
                continue;
            }

            // Solo crea si no tiene etapas
            if ($tipo->etapas()->count() === 0) {
                foreach ($etapasHipotecarias as $etapa) {
                    EtapaTramite::create(array_merge($etapa, [
                        'tipo_tramite_id' => $tipoId,
                    ]));
                }
            }
        }

        // Actualizar etapas del Crédito Conyugal (ID 3, antes "Combo FOVISSSTE+INFONAVIT")
        // Sus etapas actuales son válidas, solo actualizamos el nombre del tipo en la relación
        // (nada que cambiar en etapas, el tipo ya fue renombrado)
    }
}
