<?php

namespace Database\Seeders;

use App\Models\Propiedad;
use Illuminate\Database\Seeder;

class PropiedadSeeder extends Seeder
{
    public function run(): void
    {
        $propiedades = [
            [
                'titulo'            => 'Casa en fraccionamiento Villas del Real',
                'slug'              => 'casa-en-fraccionamiento-villas-del-real',
                'tipo'              => 'Casa',
                'descripcion'       => 'Hermosa casa de dos plantas en fraccionamiento privado con acceso controlado. Cuenta con sala, comedor, cocina integral, jardín y cochera para dos autos. Excelente ubicación cerca de escuelas y centros comerciales.',
                'precio'            => 1450000.00,
                'superficie_m2'     => null,
                'recamaras'         => 3,
                'banos'             => 2,
                'estacionamientos'  => null,
                'estado'            => 'Hidalgo',
                'municipio'         => 'Pachuca de Soto',
                'colonia'           => 'Villas del Real',
                'estatus'           => 'en_venta',
                'destacada'         => true,
                'caracteristicas'   => null,
                'creditos'          => null,
                'imagenes'          => [],  // las imágenes se suben manualmente
                'latitud'           => null,
                'longitud'          => null,
                'mapa_iframe'       => null,
            ],
            [
                'titulo'            => 'Departamento moderno en zona centro',
                'slug'              => 'departamento-moderno-en-zona-centro',
                'tipo'              => 'Departamento',
                'descripcion'       => 'Departamento completamente remodelado en el corazón de la ciudad. Piso 3 con vista a la plaza principal. Incluye cajón de estacionamiento y bodega.',
                'precio'            => 980000.00,
                'superficie_m2'     => null,
                'recamaras'         => 2,
                'banos'             => 1,
                'estacionamientos'  => null,
                'estado'            => 'Hidalgo',
                'municipio'         => 'Tulancingo',
                'colonia'           => 'Centro Histórico',
                'estatus'           => 'en_venta',
                'destacada'         => true,
                'caracteristicas'   => null,
                'creditos'          => null,
                'imagenes'          => [],
                'latitud'           => null,
                'longitud'          => null,
                'mapa_iframe'       => null,
            ],
            [
                'titulo'            => 'Terreno residencial con escrituras',
                'slug'              => 'terreno-residencial-con-escrituras',
                'tipo'              => 'Terreno',
                'descripcion'       => 'Terreno plano con escrituras en regla en zona residencial de rápido crecimiento. Ideal para construir casa o proyecto de inversión. Todos los servicios disponibles.',
                'precio'            => 450000.00,
                'superficie_m2'     => null,
                'recamaras'         => null,
                'banos'             => null,
                'estacionamientos'  => null,
                'estado'            => 'Veracruz',
                'municipio'         => 'Poza Rica',
                'colonia'           => 'Residencial del Bosque',
                'estatus'           => 'en_venta',
                'destacada'         => true,
                'caracteristicas'   => null,
                'creditos'          => null,
                'imagenes'          => [],
                'latitud'           => null,
                'longitud'          => null,
                'mapa_iframe'       => null,
            ],
            [
                'titulo'            => 'Casa familiar en colonia San Francisco',
                'slug'              => 'casa-familiar-en-colonia-san-francisco',
                'tipo'              => 'Casa',
                'descripcion'       => 'Amplia casa de una planta con jardín trasero, cochera techada y cuarto de servicio. En colonia tranquila con vigilancia. Ideal para familia con niños.',
                'precio'            => 1850000.00,
                'superficie_m2'     => null,
                'recamaras'         => 4,
                'banos'             => 2,
                'estacionamientos'  => null,
                'estado'            => 'San Luis Potosí',
                'municipio'         => 'San Luis Potosí',
                'colonia'           => 'San Francisco',
                'estatus'           => 'en_venta',
                'destacada'         => true,
                'caracteristicas'   => null,
                'creditos'          => null,
                'imagenes'          => [],
                'latitud'           => null,
                'longitud'          => null,
                'mapa_iframe'       => null,
            ],
            [
                'titulo'            => 'Local comercial en avenida principal',
                'slug'              => 'local-comercial-en-avenida-principal',
                'tipo'              => 'Local',
                'descripcion'       => 'Local comercial en planta baja con gran vitrina a avenida de alto tráfico. Cuenta con baño, bodega y acceso independiente. Excelente para negocio de cualquier giro.',
                'precio'            => 2200000.00,
                'superficie_m2'     => null,
                'recamaras'         => null,
                'banos'             => 1,
                'estacionamientos'  => null,
                'estado'            => 'Hidalgo',
                'municipio'         => 'Tula de Allende',
                'colonia'           => 'Centro',
                'estatus'           => 'en_venta',
                'destacada'         => false,
                'caracteristicas'   => null,
                'creditos'          => null,
                'imagenes'          => [],
                'latitud'           => null,
                'longitud'          => null,
                'mapa_iframe'       => null,
            ],
        ];

        foreach ($propiedades as $data) {
            Propiedad::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
