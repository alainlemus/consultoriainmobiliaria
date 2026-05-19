<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class AvisoPrivacidadSeeder extends Seeder
{
    public function run(): void
    {
        $contenido = <<<'HTML'
<h2><strong>AVISO DE PRIVACIDAD INTEGRAL</strong></h2>
<p><strong>Consultoría Inmobiliaria / José Antonio Solís Santuario</strong><br>
Plaza Tecoluco, Av. Corona del Rosal, Huejutla de Reyes, Hidalgo, México.<br>
Correo electrónico: <a href="mailto:contacto@consultoriainmobiliaria.com.mx">contacto@consultoriainmobiliaria.com.mx</a><br>
Teléfonos: 771 191 0395 / 771 781 8005</p>

<p>En cumplimiento con lo dispuesto en la <strong>Ley Federal de Protección de Datos Personales en Posesión de los Particulares</strong> (LFPDPPP), su Reglamento y los Lineamientos del Aviso de Privacidad emitidos por el Instituto Nacional de Transparencia, Acceso a la Información y Protección de Datos Personales (INAI), ponemos a su disposición el presente Aviso de Privacidad.</p>

<hr>

<h3><strong>I. IDENTIDAD Y DOMICILIO DEL RESPONSABLE</strong></h3>
<p><strong>José Antonio Solís Santuario</strong>, persona física con actividad empresarial, con domicilio en Plaza Tecoluco, Av. Corona del Rosal, Huejutla de Reyes, Hidalgo, C.P. 43000, es el responsable del tratamiento de sus datos personales.</p>

<hr>

<h3><strong>II. DATOS PERSONALES QUE SE RECABAN</strong></h3>
<p>Para las finalidades señaladas en el presente aviso, recabamos las siguientes categorías de datos personales:</p>
<ul>
<li><strong>Datos de identificación:</strong> nombre completo, CURP, RFC, INE/IFE, estado civil.</li>
<li><strong>Datos de contacto:</strong> domicilio, número telefónico, correo electrónico.</li>
<li><strong>Datos laborales y patrimoniales:</strong> número de seguridad social (NSS), salario, número de crédito INFONAVIT o FOVISSSTE, institución financiera, CLABE interbancaria.</li>
<li><strong>Datos del inmueble:</strong> ubicación, tipo, valor comercial, número de escritura o folio registral.</li>
<li><strong>Datos sensibles:</strong> en casos que lo requieran, podemos recabar información de salud cuando sea indispensable para la obtención del crédito hipotecario. Estos datos recibirán especial protección y solo serán tratados con su consentimiento expreso.</li>
</ul>

<hr>

<h3><strong>III. FINALIDADES DEL TRATAMIENTO</strong></h3>
<p><strong>Finalidades primarias</strong> (necesarias para la relación jurídica):</p>
<ul>
<li>Gestión, trámite y seguimiento de créditos hipotecarios INFONAVIT, FOVISSSTE y cofinanciados.</li>
<li>Elaboración y formalización de contratos de prestación de servicios y convenios de honorarios.</li>
<li>Integración del expediente documental requerido por instituciones de vivienda y notarías.</li>
<li>Coordinación con valuadores, notarios públicos y Registro Público de la Propiedad.</li>
<li>Facturación y gestión de cobro de honorarios profesionales.</li>
<li>Cumplimiento de obligaciones legales y fiscales.</li>
</ul>
<p><strong>Finalidades secundarias</strong> (no necesarias, puede oponerse):</p>
<ul>
<li>Envío de información sobre nuevos servicios, promociones o actualizaciones del sector inmobiliario.</li>
<li>Encuestas de satisfacción y solicitud de testimonios sobre nuestros servicios.</li>
<li>Elaboración de estadísticas internas de gestión y calidad.</li>
</ul>
<p>Si desea que sus datos no sean tratados para las finalidades secundarias, puede manifestarlo enviando un correo a <a href="mailto:contacto@consultoriainmobiliaria.com.mx">contacto@consultoriainmobiliaria.com.mx</a> con el asunto "Oposición a finalidades secundarias".</p>

<hr>

<h3><strong>IV. TRANSFERENCIA DE DATOS PERSONALES</strong></h3>
<p>Sus datos personales pueden ser transferidos a terceros en los siguientes supuestos, sin requerir su consentimiento cuando sean necesarios para la ejecución del contrato:</p>
<ul>
<li><strong>INFONAVIT / FOVISSSTE:</strong> para la gestión y autorización del crédito hipotecario.</li>
<li><strong>Notarios públicos:</strong> para la formalización de escrituras.</li>
<li><strong>Valuadores certificados:</strong> para la realización del avalúo del inmueble.</li>
<li><strong>Registro Público de la Propiedad:</strong> para inscripción de escrituras.</li>
<li><strong>Instituciones bancarias cofinancieras:</strong> cuando el crédito así lo requiera.</li>
<li><strong>Autoridades fiscales (SAT):</strong> en cumplimiento de obligaciones tributarias.</li>
</ul>
<p>En ningún caso venderemos, cederemos ni rentaremos su información personal a terceros ajenos a las finalidades descritas.</p>

<hr>

<h3><strong>V. DERECHOS ARCO</strong></h3>
<p>Usted tiene derecho a <strong>Acceder, Rectificar, Cancelar u Oponerse</strong> (Derechos ARCO) al tratamiento de sus datos personales. Para ejercerlos deberá enviar una solicitud a <a href="mailto:contacto@consultoriainmobiliaria.com.mx">contacto@consultoriainmobiliaria.com.mx</a> o presentarla en nuestras oficinas, incluyendo:</p>
<ul>
<li>Nombre completo e identificación oficial.</li>
<li>Descripción clara del derecho que desea ejercer.</li>
<li>Documentos que sustenten su solicitud, en su caso.</li>
</ul>
<p>Responderemos en un plazo máximo de <strong>20 días hábiles</strong> a partir de la recepción de la solicitud. La respuesta será notificada al correo electrónico proporcionado.</p>

<hr>

<h3><strong>VI. MECANISMOS PARA REVOCAR EL CONSENTIMIENTO</strong></h3>
<p>Puede revocar el consentimiento otorgado para el tratamiento de sus datos personales en cualquier momento, siempre que no sea en perjuicio de derechos derivados del contrato o de obligaciones legales vigentes. La solicitud deberá realizarse a través del mismo medio indicado en la sección anterior.</p>

<hr>

<h3><strong>VII. USO DE COOKIES Y TECNOLOGÍAS DE RASTREO</strong></h3>
<p>Nuestro sitio web puede utilizar cookies y tecnologías similares para mejorar la experiencia de navegación, analizar el tráfico y optimizar nuestros servicios. Puede deshabilitar las cookies desde la configuración de su navegador; sin embargo, esto podría afectar el funcionamiento de ciertas funcionalidades del sitio.</p>

<hr>

<h3><strong>VIII. MODIFICACIONES AL AVISO DE PRIVACIDAD</strong></h3>
<p>Nos reservamos el derecho de modificar el presente Aviso de Privacidad en cualquier momento para adaptarlo a novedades legislativas o cambios en nuestros servicios. Cualquier modificación será publicada en nuestro sitio web <strong>www.consultoriainmobiliaria.com.mx</strong> y, cuando sea relevante, le notificaremos por correo electrónico.</p>

<hr>

<h3><strong>IX. AUTORIDAD COMPETENTE</strong></h3>
<p>Si considera que su derecho a la protección de datos personales ha sido vulnerado, puede acudir al <strong>Instituto Nacional de Transparencia, Acceso a la Información y Protección de Datos Personales (INAI)</strong>, con domicilio en Insurgentes Sur 3211, Col. Insurgentes Cuicuilco, Alcaldía Coyoacán, C.P. 04530, Ciudad de México. Sitio web: <a href="https://www.inai.org.mx">www.inai.org.mx</a></p>

<hr>

<p><em>Última actualización: mayo de 2026.</em></p>
HTML;

        // Usar Configuracion::set() para que también invalide el cache automáticamente
        Configuracion::set('aviso_privacidad', $contenido);
    }
}
