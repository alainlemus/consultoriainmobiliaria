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
                'titulo'       => 'Infonavit o Fovissste: ¿cuál es mejor? Diferencias en 2026',
                'slug'         => 'diferencias-infonavit-fovissste-cual-conviene',
                'resumen'      => 'Infonavit es para quien cotiza en el IMSS y Fovissste para quien cotiza en el ISSSTE. Compara tasas y plazos, y descubre qué crédito te corresponde.',
                'categoria'    => 'FOVISSSTE',
                'imagen'       => null,
                'publicado'    => true,
                'estado'       => 'publicado',
                'published_at' => '2026-05-07 22:22:39',
                'contenido'    => '<p>Infonavit es para quien trabaja en el sector privado y cotiza en el IMSS. Fovissste es para quien trabaja en el gobierno y cotiza en el ISSSTE. Por eso, más que elegir el «mejor», lo primero es saber cuál te corresponde según tu empleo. Aquí comparamos ambos.</p>

<h2>Infonavit vs Fovissste: comparación rápida</h2>
<div style="overflow-x:auto">
<table>
<thead>
<tr><th></th><th>Infonavit</th><th>Fovissste</th></tr>
</thead>
<tbody>
<tr><td><strong>Para quién</strong></td><td>Sector privado (IMSS)</td><td>Sector público (ISSSTE)</td></tr>
<tr><td><strong>Tasa de interés</strong></td><td>De 3.69% a 10.45%, según tu salario; fija toda la vida del crédito</td><td>Crédito tradicional: de 4% a 6% anual. Otros esquemas manejan tasas distintas</td></tr>
<tr><td><strong>Plazo máximo</strong></td><td>30 años</td><td>30 años</td></tr>
<tr><td><strong>Requisito base</strong></td><td>Relación laboral vigente y precalificación en Mi Cuenta Infonavit</td><td>Servicio activo y al menos 18 meses de aportaciones</td></tr>
<tr><td><strong>Cómo se paga</strong></td><td>Descuento de tu salario, según tu sueldo y tu crédito</td><td>Descuento de 30% de tu sueldo básico</td></tr>
</tbody>
</table>
</div>
<p><em>Datos de referencia de 2026. Las condiciones cambian según el programa: confirma las tuyas en <a href="https://portalmx.infonavit.org.mx" rel="noopener" target="_blank">Infonavit</a> o en <a href="https://www.gob.mx/fovissste" rel="noopener" target="_blank">Fovissste</a>.</em></p>

<h2>¿Infonavit y Fovissste son lo mismo?</h2>
<p>No. Los dos dan crédito para vivienda, pero son instituciones distintas y atienden a trabajadores diferentes: Infonavit a los afiliados al IMSS y Fovissste a los afiliados al ISSSTE.</p>

<h2>¿Qué es mejor, Infonavit o Fovissste?</h2>
<p>Depende de dónde cotizas, porque normalmente el crédito lo define tu empleo. La tasa de Infonavit cambia según tu salario y la de Fovissste depende del esquema que elijas, así que conviene comparar tu caso con números reales y no solo los rangos generales.</p>

<h2>¿Se pueden juntar los dos créditos?</h2>
<p>Hay esquemas para combinar un crédito Infonavit con uno Fovissste y comprar una sola vivienda, lo que puede aumentar el monto disponible. Quién puede participar depende del programa vigente, y algunos son solo para matrimonios. Antes de planearlo, confirma los requisitos actuales.</p>

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
