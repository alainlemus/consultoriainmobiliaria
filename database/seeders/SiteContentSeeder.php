<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cobertura;
use App\Models\Proceso;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        // ── Servicios ────────────────────────────────────────────────────────
        $servicios = [
            ['titulo' => 'Crédito INFONAVIT',     'items' => ['Te acompañamos en todo el proceso para ejercer tu crédito INFONAVIT y adquirir la propiedad que deseas.'], 'wa_texto' => 'Necesito información sobre Crédito INFONAVIT', 'icon_path' => '', 'orden' => 1],
            ['titulo' => 'Crédito FOVISSSTE',      'items' => ['Gestionamos tu crédito FOVISSSTE de principio a fin, con asesoría personalizada para trabajadores del sector público.'], 'wa_texto' => 'Necesito información sobre Crédito FOVISSSTE', 'icon_path' => '', 'orden' => 2],
            ['titulo' => 'Avalúos',                'items' => ['Realizamos avalúos inmobiliarios, fiscales y comerciales con peritos certificados en Hidalgo, Veracruz y SLP.'], 'wa_texto' => 'Necesito información sobre Avalúos', 'icon_path' => '', 'orden' => 3],
            ['titulo' => 'Gestión de Escrituras',  'items' => ['Coordinamos todo el proceso notarial para que la escrituración de tu propiedad sea rápida y sin contratiempos.'], 'wa_texto' => 'Necesito información sobre Gestión de Escrituras', 'icon_path' => '', 'orden' => 4],
            ['titulo' => 'Asesoría Personalizada', 'items' => ['Analizamos tu situación particular y te orientamos hacia la mejor opción para adquirir o vender tu propiedad.'], 'wa_texto' => 'Necesito información sobre Asesoría Personalizada', 'icon_path' => '', 'orden' => 5],
        ];

        foreach ($servicios as $data) {
            Servicio::firstOrCreate(
                ['titulo' => $data['titulo']],
                array_merge($data, ['activo' => true])
            );
        }

        // ── Proceso ───────────────────────────────────────────────────────────
        $pasos = [
            ['numero' => '01', 'titulo' => 'Contacto Inicial',      'descripcion' => 'Nos contactas por WhatsApp. Te escuchamos y evaluamos tu situación sin compromiso.'],
            ['numero' => '02', 'titulo' => 'Precalificación',        'descripcion' => 'Verificamos tu crédito disponible en INFONAVIT o FOVISSSTE.'],
            ['numero' => '03', 'titulo' => 'Avalúo',                 'descripcion' => 'Realizamos el avalúo de la propiedad para validar el trámite.'],
            ['numero' => '04', 'titulo' => 'Documentación',          'descripcion' => 'Gestionamos toda la documentación necesaria ante el instituto.'],
            ['numero' => '05', 'titulo' => 'Trámites Notariales',    'descripcion' => 'Coordinamos con el notario todos los trámites legales previos.'],
            ['numero' => '06', 'titulo' => 'Aprobación',             'descripcion' => 'Se autoriza tu crédito. Te notificamos en cada etapa.'],
            ['numero' => '07', 'titulo' => 'Firma de Escrituras',    'descripcion' => 'Firmamos ante notario. La propiedad queda oficialmente a tu nombre.'],
            ['numero' => '08', 'titulo' => '¡Tu Nuevo Hogar!',       'descripcion' => 'Recibes las llaves. ¡Hiciste realidad el sueño de tener casa propia!'],
        ];

        foreach ($pasos as $data) {
            Proceso::firstOrCreate(
                ['numero' => $data['numero']],
                array_merge($data, ['activo' => true])
            );
        }

        // ── Coberturas ────────────────────────────────────────────────────────
        $coberturas = [
            ['nombre' => 'Hidalgo',          'descripcion' => 'Contáctanos para agendar tu cita en tu municipio', 'detalle' => 'Huejutla de Reyes, Hgo. Plaza Tecoluco, Av. Corona del Rosal | Pachuca, Hgo. Centro comercial Via Dorada, Av. Ferrocarril Central', 'activo' => true],
            ['nombre' => 'Veracruz',         'descripcion' => 'Contáctanos para agendar tu cita en tu municipio',            'detalle' => '', 'activo' => true],
            ['nombre' => 'San Luis Potosí',  'descripcion' => 'Contáctanos para agendar tu cita en tu municipio',            'detalle' => '', 'activo' => true],
        ];

        foreach ($coberturas as $data) {
            Cobertura::firstOrCreate(['nombre' => $data['nombre']], $data);
        }

        // ── Categorías de blog ────────────────────────────────────────────────
        $categorias = ['INFONAVIT', 'FOVISSSTE', 'Avalúos', 'Escrituras', 'Asesoría', 'General'];

        foreach ($categorias as $nombre) {
            Categoria::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
