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
                'siguiente_paso'=> 'confirmacion_telefono',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 3,
            ],
            [
                'clave'         => 'confirmacion_telefono',
                'tipo'          => 'seleccion',
                'etiqueta'      => 'Confirmación de teléfono',
                'mensaje'       => "Tu número detectado es *{telefono}* 📱\n\n¿Es correcto?\n\n1️⃣  Sí, es mi número\n2️⃣  No, quiero cambiarlo",
                'opciones'      => [
                    ['valor' => '1', 'etiqueta' => 'Teléfono confirmado', 'requiere_curp' => false],
                    ['valor' => '2', 'etiqueta' => 'Cambiar teléfono',    'requiere_curp' => false],
                ],
                'siguiente_paso'=> 'estado_ubicacion',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 4,
            ],
            [
                'clave'         => 'telefono_manual',
                'tipo'          => 'texto_libre',
                'etiqueta'      => 'Teléfono manual',
                'mensaje'       => "Por favor escribe tu número celular a *10 dígitos*:",
                'opciones'      => null,
                'siguiente_paso'=> 'estado_ubicacion',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 5,
            ],
            [
                'clave'         => 'estado_ubicacion',
                'tipo'          => 'seleccion',
                'etiqueta'      => 'Estado / Ubicación',
                'mensaje'       => "¿En qué estado te encuentras? 📍\n\n1️⃣  Hidalgo\n2️⃣  Veracruz\n3️⃣  Tamaulipas\n4️⃣  San Luis Potosí",
                'opciones'      => [
                    ['valor' => '1', 'etiqueta' => 'Hidalgo',          'requiere_curp' => false],
                    ['valor' => '2', 'etiqueta' => 'Veracruz',         'requiere_curp' => false],
                    ['valor' => '3', 'etiqueta' => 'Tamaulipas',       'requiere_curp' => false],
                    ['valor' => '4', 'etiqueta' => 'San Luis Potosí',  'requiere_curp' => false],
                ],
                'siguiente_paso'=> 'situacion_laboral',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 6,
            ],
            [
                'clave'         => 'situacion_laboral',
                'tipo'          => 'seleccion',
                'etiqueta'      => 'Situación laboral',
                'mensaje'       => "¿Cuál es tu situación laboral? 💼\n\n1️⃣  Trabajador IMSS (INFONAVIT)\n2️⃣  Trabajador ISSSTE (FOVISSSTE)\n3️⃣  Independiente / otro",
                'opciones'      => [
                    ['valor' => '1', 'etiqueta' => 'Trabajador IMSS (INFONAVIT)',   'requiere_curp' => false],
                    ['valor' => '2', 'etiqueta' => 'Trabajador ISSSTE (FOVISSSTE)', 'requiere_curp' => false],
                    ['valor' => '3', 'etiqueta' => 'Independiente / otro',           'requiere_curp' => false],
                ],
                'siguiente_paso'=> 'sueldo_precal',
                'activo'        => true,
                'requerido'     => true,
                'orden'         => 7,
            ],
            // ── Pasos de precalificación (solo para servicios de crédito) ──
            [
                'clave'         => 'sueldo_precal',
                'tipo'          => 'condicional',
                'etiqueta'      => 'Precal: Sueldo mensual',
                'mensaje'       => "Para darte un *estimado de crédito* necesito algunos datos 📊\n\n¿Cuál es tu *sueldo mensual neto* en pesos?\n_(Solo el número, ej: 15000)_\n_(Escribe *omitir* para saltar la precalificación)_",
                'opciones'      => null,
                'siguiente_paso'=> 'edad_precal',
                'activo'        => true,
                'requerido'     => false,
                'orden'         => 8,
            ],
            [
                'clave'         => 'edad_precal',
                'tipo'          => 'condicional',
                'etiqueta'      => 'Precal: Edad',
                'mensaje'       => "¿Cuántos *años* tienes actualmente?\n_(Solo el número, ej: 35)_",
                'opciones'      => null,
                'siguiente_paso'=> 'antiguedad_precal',
                'activo'        => true,
                'requerido'     => false,
                'orden'         => 9,
            ],
            [
                'clave'         => 'antiguedad_precal',
                'tipo'          => 'condicional',
                'etiqueta'      => 'Precal: Antigüedad laboral',
                'mensaje'       => "¿Cuántos *años* llevas trabajando en tu empleo actual?\n_(Solo el número, ej: 5)_",
                'opciones'      => null,
                'siguiente_paso'=> 'subcuenta_precal',
                'activo'        => true,
                'requerido'     => false,
                'orden'         => 10,
            ],
            [
                'clave'         => 'subcuenta_precal',
                'tipo'          => 'condicional',
                'etiqueta'      => 'Precal: Subcuenta de vivienda',
                'mensaje'       => "¿Sabes cuánto tienes ahorrado en tu *subcuenta de vivienda* (INFONAVIT/FOVISSSTE)?\n_(Solo el número, ej: 45000 — o escribe *omitir* si no lo sabes)_",
                'opciones'      => null,
                'siguiente_paso'=> 'mensaje_libre',
                'activo'        => true,
                'requerido'     => false,
                'orden'         => 11,
            ],
            // ── Fin precalificación ────────────────────────────────────────
            [
                'clave'         => 'mensaje_libre',
                'tipo'          => 'texto_libre',
                'etiqueta'      => 'Mensaje adicional',
                'mensaje'       => "¿Hay algo más que quieras contarnos sobre tu situación o lo que buscas? 📝\n_(Escribe tu mensaje o escribe *omitir* para continuar)_",
                'opciones'      => null,
                'siguiente_paso'=> 'correo',
                'activo'        => true,
                'requerido'     => false,
                'orden'         => 12,
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
                'orden'         => 13,
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
                'orden'         => 14,
            ],
        ];

        foreach ($pasos as $paso) {
            ChatbotPaso::updateOrCreate(['clave' => $paso['clave']], $paso);
        }
    }
}
