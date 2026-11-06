<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO: el artículo Infonavit vs Fovissste aparece mucho en Google (posición ~9)
 * y casi no recibe clics. Título directo a la pregunta que busca la gente, tabla
 * comparativa arriba y preguntas frecuentes. Los posts viven en la base de datos.
 */
return new class extends Migration
{
    private const SLUG = 'diferencias-infonavit-fovissste-cual-conviene';

    public function up(): void
    {
        DB::table('posts')->where('slug', self::SLUG)->update([
            'titulo'     => 'Infonavit o Fovissste: ¿cuál es mejor? Diferencias en 2026',
            'resumen'    => 'Infonavit es para quien cotiza en el IMSS y Fovissste para quien cotiza en el ISSSTE. Compara tasas y plazos, y descubre qué crédito te corresponde.',
            'contenido'  => <<<'HTML'
<p>Infonavit es para quien trabaja en el sector privado y cotiza en el IMSS. Fovissste es para quien trabaja en el gobierno y cotiza en el ISSSTE. Por eso, más que elegir el «mejor», lo primero es saber cuál te corresponde según tu empleo. Aquí comparamos ambos.</p>

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
<p>Antes de decidir, es importante que un asesor revise tu situación particular. En Consultoría Inmobiliaria hacemos ese análisis sin costo. Escríbenos por WhatsApp y te ayudamos a encontrar la mejor opción.</p>
HTML,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('posts')->where('slug', self::SLUG)->update([
            'titulo'     => 'Diferencias entre crédito INFONAVIT y FOVISSSTE: ¿cuál te conviene?',
            'resumen'    => 'Si eres trabajador del sector privado o del gobierno, tienes acceso a créditos de vivienda distintos. Aquí te explicamos las diferencias clave para que tomes la mejor decisión.',
            'contenido'  => <<<'HTML'
<p>Muchas familias se preguntan cuál crédito de vivienda les conviene más. La respuesta depende principalmente de tu tipo de empleo y de las condiciones de cada esquema. Aquí te lo explicamos de forma clara.</p>

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
<p>Antes de decidir, es importante que un asesor revise tu situación particular. En Consultoría Inmobiliaria hacemos ese análisis sin costo. Escríbenos por WhatsApp y te ayudamos a encontrar la mejor opción.</p>
HTML,
            'updated_at' => now(),
        ]);
    }
};
