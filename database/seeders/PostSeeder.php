<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'titulo'       => '¿Cómo saber cuánto crédito INFONAVIT tienes disponible?',
                'slug'         => 'como-saber-credito-infonavit-disponible',
                'resumen'      => 'Conocer tu saldo de subcuenta y puntos INFONAVIT es el primer paso para hacer realidad el sueño de tu casa propia. Te explicamos cómo consultarlo en minutos.',
                'categoria'    => 'INFONAVIT',
                'imagen'       => null,
                'publicado'    => true,
                'estado'       => 'publicado',
                'published_at' => '2026-05-02 22:22:39',
                'contenido'    => '<p>Antes de iniciar cualquier trámite de crédito INFONAVIT, es fundamental que conozcas cuánto tienes disponible. Este dato determina el monto máximo que puedes solicitar y los inmuebles a los que puedes acceder.</p>

<h2>¿Qué necesitas para consultar tu crédito?</h2>
<p>Solo necesitas tu Número de Seguridad Social (NSS) y acceso a internet. Puedes hacerlo directamente en el portal oficial de INFONAVIT o desde la app "Mi Cuenta INFONAVIT".</p>

<h2>Pasos para consultar tu precalificación</h2>
<ol>
<li>Ingresa a <strong>www.infonavit.org.mx</strong></li>
<li>Selecciona "Precalificación y puntos"</li>
<li>Ingresa tu NSS y contraseña</li>
<li>Consulta tu saldo de subcuenta y el monto de crédito disponible</li>
</ol>

<h2>¿Qué factores determinan tu crédito?</h2>
<p>El monto de tu crédito INFONAVIT depende de varios factores: tu salario diario integrado, el saldo acumulado en tu subcuenta de vivienda, tus puntos INFONAVIT y tu historial crediticio en el Buró de Crédito.</p>

<h2>¿Y si no tengo suficientes puntos?</h2>
<p>No te preocupes. Existen esquemas como <strong>Crédito Tradicional</strong> y <strong>Crédito FOVISSSTE</strong> que pueden adaptarse a tu situación. En Consultoría Inmobiliaria te ayudamos a evaluar cuál es la mejor opción para ti, sin costo inicial.</p>

<p>Contáctanos por WhatsApp y con gusto te orientamos paso a paso.</p>',
            ],
            [
                'titulo'       => 'Diferencias entre crédito INFONAVIT y FOVISSSTE: ¿cuál te conviene?',
                'slug'         => 'diferencias-infonavit-fovissste-cual-conviene',
                'resumen'      => 'Si eres trabajador del sector privado o del gobierno, tienes acceso a créditos de vivienda distintos. Aquí te explicamos las diferencias clave para que tomes la mejor decisión.',
                'categoria'    => 'FOVISSSTE',
                'imagen'       => null,
                'publicado'    => true,
                'estado'       => 'publicado',
                'published_at' => '2026-05-07 22:22:39',
                'contenido'    => '<p>Muchas familias se preguntan cuál crédito de vivienda les conviene más. La respuesta depende principalmente de tu tipo de empleo y de las condiciones de cada esquema. Aquí te lo explicamos de forma clara.</p>

<h2>¿Quién puede usar cada crédito?</h2>
<ul>
<li><strong>INFONAVIT:</strong> Trabajadores del sector privado afiliados al IMSS.</li>
<li><strong>FOVISSSTE:</strong> Trabajadores del sector público afiliados al ISSSTE.</li>
</ul>

<h2>Principales diferencias</h2>
<ul>
<li><strong>Afiliación:</strong> INFONAVIT = IMSS · FOVISSSTE = ISSSTE</li>
<li><strong>Tasa de interés:</strong> INFONAVIT varía según salario · FOVISSSTE generalmente fija y más baja</li>
<li><strong>Plazo máximo:</strong> 30 años en ambos casos</li>
<li><strong>Cofinanciamiento:</strong> Disponible en los dos esquemas con bancos participantes</li>
</ul>

<h2>¿Puedo combinar ambos?</h2>
<p>Si tú y tu pareja trabajan, uno en el sector privado y otro en el gobierno, existe la posibilidad de combinar ambos créditos mediante el esquema <strong>INFONAVIT-FOVISSSTE</strong>, lo que aumenta considerablemente el monto disponible.</p>

<h2>Nuestra recomendación</h2>
<p>Antes de decidir, es importante que un asesor revise tu situación particular. En Consultoría Inmobiliaria hacemos ese análisis sin costo. Escríbenos por WhatsApp y te ayudamos a encontrar la mejor opción.</p>',
            ],
            [
                'titulo'       => '¿Qué es un avalúo y por qué es indispensable para comprar casa?',
                'slug'         => 'que-es-avaluo-indispensable-comprar-casa',
                'resumen'      => 'El avalúo es uno de los documentos más importantes en el proceso de compra de un inmueble. Descubre qué es, para qué sirve y quién lo realiza.',
                'categoria'    => 'Avalúos',
                'imagen'       => null,
                'publicado'    => true,
                'estado'       => 'publicado',
                'published_at' => '2026-05-10 22:22:39',
                'contenido'    => '<p>Si estás en proceso de adquirir una propiedad con crédito INFONAVIT o FOVISSSTE, seguramente has escuchado la palabra <strong>avalúo</strong>. Pero, ¿sabes exactamente qué es y por qué es obligatorio?</p>

<h2>¿Qué es un avalúo inmobiliario?</h2>
<p>Un avalúo es un dictamen técnico realizado por un perito certificado que determina el <strong>valor comercial real</strong> de un inmueble. No es lo mismo que el precio de venta — es una valoración objetiva e independiente.</p>

<h2>¿Para qué sirve?</h2>
<ul>
<li>Garantiza que el crédito no supere el valor real del inmueble</li>
<li>Protege tanto al comprador como al instituto (INFONAVIT/FOVISSSTE)</li>
<li>Es requisito obligatorio para tramitar escrituras</li>
<li>Sirve como base para calcular impuestos y derechos notariales</li>
</ul>

<h2>¿Quién realiza el avalúo?</h2>
<p>Debe ser un perito valuador certificado y registrado ante el instituto correspondiente. En Consultoría Inmobiliaria contamos con peritos autorizados que realizan avalúos inmobiliarios, fiscales y comerciales en Hidalgo, Veracruz y San Luis Potosí.</p>

<h2>¿Cuánto tiempo tarda?</h2>
<p>Normalmente entre 3 y 7 días hábiles, dependiendo de la disponibilidad del perito y la ubicación del inmueble.</p>

<h2>¿Tiene costo?</h2>
<p>Sí, el avalúo tiene un costo que varía según el valor y tipo del inmueble. Sin embargo, cuando gestionas tu crédito con nosotros, te orientamos para que este trámite sea parte del proceso general sin sorpresas.</p>

<p>¿Tienes dudas sobre el avalúo de tu propiedad? Contáctanos, con gusto te asesoramos.</p>',
            ],
        ];

        foreach ($posts as $data) {
            Post::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
