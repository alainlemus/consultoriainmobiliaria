<?php

namespace Database\Seeders;

use App\Models\Testimonio;
use Illuminate\Database\Seeder;

class TestimonioSeeder extends Seeder
{
    public function run(): void
    {
        $testimonios = [
            [
                'nombre'     => 'María González',
                'ciudad'     => 'Pachuca, Hidalgo',
                'servicio'   => 'INFONAVIT',
                'testimonio' => 'Gracias a Consultoría Inmobiliaria pude ejercer mi crédito INFONAVIT. Me ayudaron en todo el proceso, desde la precalificación hasta la firma de escrituras. ¡100% recomendados!',
                'estrellas'  => 5,
                'activo'     => true,
                'orden'      => 1,
            ],
            [
                'nombre'     => 'José Hernández',
                'ciudad'     => 'Huejutla de Reyes, Hgo.',
                'servicio'   => 'FOVISSSTE',
                'testimonio' => 'Estaba perdido con los requisitos de mi crédito FOVISSSTE. El equipo me explicó todo con paciencia y me consiguieron el avalúo rápidamente. Excelente servicio.',
                'estrellas'  => 5,
                'activo'     => true,
                'orden'      => 2,
            ],
            [
                'nombre'     => 'Ana Laura Martínez',
                'ciudad'     => 'Veracruz, Ver.',
                'servicio'   => 'Escrituras',
                'testimonio' => 'El proceso fue muy transparente. En todo momento supe qué estaba pasando con mi crédito. Ahora tengo mi casa escriturada. ¡Muchas gracias!',
                'estrellas'  => 5,
                'activo'     => true,
                'orden'      => 3,
            ],
        ];

        foreach ($testimonios as $data) {
            Testimonio::firstOrCreate(
                ['nombre' => $data['nombre'], 'ciudad' => $data['ciudad']],
                $data
            );
        }
    }
}
