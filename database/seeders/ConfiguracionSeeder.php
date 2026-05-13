<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            // General
            'logo'              => null,   // subir manualmente en el admin
            'favicon'           => null,   // subir manualmente en el admin
            'site_name'         => 'Consultoría Inmobiliaria',
            'telefono_1'        => '7711910395',
            'telefono_2'        => '7717818005',
            'whatsapp_1'        => '527711910395',
            'whatsapp_2'        => '527717818005',
            'oficina_principal' => '1',    // ID del registro de Cobertura "Hidalgo"
            'correo_contacto'   => 'alainttlm@gmail.com',

            // Redes sociales
            'facebook_url'      => 'https://www.facebook.com/profile.php?id=100078798308825',
            'instagram_url'     => null,
            'tiktok_url'        => null,

            // SEO
            'seo_titulo'        => 'Consultoría Inmobiliaria | Crédito INFONAVIT y FOVISSSTE en Hidalgo',
            'seo_descripcion'   => 'Asesores expertos en crédito INFONAVIT, FOVISSSTE, avalúos y escrituras en Huejutla de Reyes, Hidalgo. Te ayudamos a conseguir tu casa propia sin costo inicial.',
            'seo_keywords'      => 'crédito INFONAVIT, crédito FOVISSSTE, avalúos, escrituras, asesoría inmobiliaria, Huejutla de Reyes, Hidalgo',
            'seo_og_imagen'     => null,   // subir manualmente en el admin
            'seo_autor'         => 'Consultoría Inmobiliaria',
            'seo_robots'        => 'index, follow',

            // Aviso de privacidad
            'aviso_privacidad'  => '<h2>Aviso de Privacidad</h2>
<p><strong>Consultoría Inmobiliaria</strong>, con domicilio en Hidalgo, México, es responsable del tratamiento de los datos personales que nos proporcione.</p>

<h3>¿Qué datos personales recopilamos?</h3>
<p>Para llevar a cabo las finalidades descritas en el presente aviso de privacidad, utilizamos los siguientes datos personales:</p>
<ul>
  <li>Nombre completo</li>
  <li>Correo electrónico</li>
  <li>Número de teléfono o WhatsApp</li>
  <li>Información relacionada con su interés en adquirir o gestionar un bien inmueble</li>
</ul>

<h3>¿Para qué usamos sus datos personales?</h3>
<p>Los datos personales que recabamos serán utilizados para las siguientes finalidades:</p>
<ul>
  <li>Brindarle asesoría sobre créditos INFONAVIT y FOVISSSTE</li>
  <li>Gestionar trámites de avalúo, escrituración y compraventa de bienes inmuebles</li>
  <li>Darle seguimiento a su solicitud de servicio</li>
  <li>Enviarle información relevante sobre nuestros servicios inmobiliarios</li>
  <li>Contactarle para agendar citas o aclarar dudas sobre su proceso</li>
</ul>

<h3>¿Con quién compartimos sus datos?</h3>
<p>Sus datos personales no serán transferidos a terceros sin su consentimiento, salvo en los casos previstos por la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP), tales como instituciones públicas como el INFONAVIT, FOVISSSTE o notarías, cuando sea estrictamente necesario para concluir el trámite solicitado.</p>

<h3>¿Cómo protegemos sus datos?</h3>
<p>Implementamos medidas de seguridad administrativas, técnicas y físicas para proteger sus datos personales contra daño, pérdida, alteración, destrucción o el uso, acceso o tratamiento no autorizado.</p>

<h3>¿Cuáles son sus derechos ARCO?</h3>
<p>Usted tiene derecho a <strong>Acceder</strong>, <strong>Rectificar</strong>, <strong>Cancelar</strong> u <strong>Oponerse</strong> al tratamiento de sus datos personales (derechos ARCO). Para ejercerlos, puede contactarnos a través de:</p>
<ul>
  <li>Correo electrónico registrado en nuestra página de contacto</li>
  <li>WhatsApp o teléfono de atención al cliente</li>
</ul>
<p>Daremos respuesta a su solicitud en un plazo máximo de 20 días hábiles.</p>

<h3>Cambios al aviso de privacidad</h3>
<p>Nos reservamos el derecho de efectuar modificaciones o actualizaciones al presente aviso de privacidad. Dichas modificaciones estarán disponibles en esta misma página.</p>

<h3>Cookies y tecnologías de rastreo</h3>
<p>Nuestro sitio web puede utilizar cookies con la finalidad de mejorar su experiencia de navegación. Estas cookies no recopilan información personal identificable.</p>

<p><em>Última actualización: 12 de May de 2026</em></p>',
        ];

        foreach ($configs as $clave => $valor) {
            Configuracion::firstOrCreate(
                ['clave' => $clave],
                ['valor' => $valor]
            );
        }
    }
}
