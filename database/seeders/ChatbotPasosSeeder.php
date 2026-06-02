<?php

namespace Database\Seeders;

use App\Models\ChatbotPaso;
use Illuminate\Database\Seeder;

class ChatbotPasosSeeder extends Seeder
{
    public function run(): void
    {
        $pasos = [
            [
                'clave'         => 'bienvenida',
                'tipo'          => 'mensaje',
                'etiqueta'      => 'Bienvenida',
                'mensaje'       => "¡Hola {nombre}! 👋 Bienvenido a *Consultoría Inmobiliaria*.\n\n¿En qué podemos ayudarte hoy? Responde con el número de tu opción:\n\n{menu}",
                'opciones'      => null,
                'siguiente_paso'=> 'servicio',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 1,
            ],
            [
                'clave'         => 'servicio',
                'tipo'          => 'seleccion',
                'etiqueta'      => 'Selección de servicio',
                'mensaje'       => "Por favor responde con un número del *1 al {total}* para seleccionar tu servicio. 😊",
                'opciones'      => [
                    ['valor' => '1', 'etiqueta' => 'Crédito INFONAVIT',      'requiere_curp' => true],
                    ['valor' => '2', 'etiqueta' => 'Crédito FOVISSSTE',      'requiere_curp' => true],
                    ['valor' => '3', 'etiqueta' => 'Avalúo',                 'requiere_curp' => false],
                    ['valor' => '4', 'etiqueta' => 'Escrituración',          'requiere_curp' => false],
                    ['valor' => '5', 'etiqueta' => 'Asesoría personalizada', 'requiere_curp' => false],
                ],
                'siguiente_paso'=> 'nombre',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 2,
            ],
            [
                'clave'         => 'nombre',
                'tipo'          => 'texto_libre',
                'etiqueta'      => 'Nombre completo',
                'mensaje'       => "Excelente, seleccionaste *{servicio}* ✅\n\n¿Cuál es tu *nombre completo*?",
                'opciones'      => null,
                'siguiente_paso'=> 'correo',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 3,
            ],
            [
                'clave'         => 'correo',
                'tipo'          => 'texto_libre',
                'etiqueta'      => 'Correo electrónico',
                'mensaje'       => "Gracias *{nombre}* 😊\n\n¿Cuál es tu *correo electrónico*?\n_(Escribe 'omitir' si no deseas proporcionarlo)_",
                'opciones'      => null,
                'siguiente_paso'=> 'curp',
                'activo'        => true,
                'requerido'     => false,
                'orden'         => 4,
            ],
            [
                'clave'         => 'curp',
                'tipo'          => 'condicional',
                'etiqueta'      => 'CURP (solo INFONAVIT/FOVISSSTE)',
                'mensaje'       => "Para realizar una simulación de crédito necesitamos tu *CURP*.\n\nPor favor escríbela (18 caracteres):\n_(Escribe 'omitir' para continuar sin ella)_",
                'opciones'      => null,
                'siguiente_paso'=> null,
                'activo'        => true,
                'requerido'     => false,
                'orden'         => 5,
            ],
        ];

        foreach ($pasos as $paso) {
            ChatbotPaso::updateOrCreate(['clave' => $paso['clave']], $paso);
        }
    }
}
