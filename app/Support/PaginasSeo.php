<?php

namespace App\Support;

/**
 * Contenido de las páginas de servicio y de zona (SEO).
 *
 * Cada página vive aquí como un arreglo para que el texto se edite en un solo
 * lugar. La vista `pages.seo.landing` las pinta todas con la misma estructura.
 *
 *  - Servicios: /servicios/{slug}
 *  - Zonas:     /consultoria-inmobiliaria/{slug}
 */
class PaginasSeo
{
    public static function servicios(): array
    {
        return [
            'avaluos-inmobiliarios' => [
                'tipo'        => 'servicio',
                'ruta'        => 'servicios.avaluos',
                'eyebrow'     => 'Avalúos con peritos certificados',
                'h1'          => 'Avalúos inmobiliarios en Hidalgo, Veracruz y San Luis Potosí',
                'titulo'      => 'Avalúos inmobiliarios en Hidalgo | Comercial, fiscal y para crédito',
                'descripcion' => 'Avalúo comercial, fiscal o para crédito INFONAVIT y FOVISSSTE con peritos certificados en Huejutla, la Huasteca y toda la región. Cotiza sin compromiso.',
                'categoria_blog' => 'Avalúos',
                'wa_texto'    => 'Necesito información sobre Avalúos',
                'intro'       => [
                    'Un avalúo inmobiliario es el documento que fija el valor de una casa, terreno o local. Lo necesitas para ejercer un crédito INFONAVIT o FOVISSSTE, para escriturar, para pagar impuestos de una compraventa o herencia, o simplemente para saber cuánto vale tu propiedad antes de venderla.',
                    'En Consultoría Inmobiliaria trabajamos con peritos valuadores certificados que atienden Hidalgo, la Huasteca veracruzana y San Luis Potosí. Te decimos qué tipo de avalúo necesitas, cuánto cuesta y cuánto tarda, y lo coordinamos por ti.',
                ],
                'incluye_titulo' => '¿Qué tipo de avalúo necesitas?',
                'incluye'     => [
                    ['Avalúo comercial', 'Determina el valor de mercado de tu inmueble. Es el que piden los bancos y el que usas para fijar un precio de venta real.'],
                    ['Avalúo fiscal', 'Se usa para calcular los impuestos de una compraventa, donación o herencia ante notario y catastro.'],
                    ['Avalúo para crédito INFONAVIT o FOVISSSTE', 'Lo realiza un perito autorizado por el instituto. Sin este avalúo el crédito no avanza.'],
                    ['Avalúo de terrenos y locales', 'También valuamos terrenos, locales comerciales y propiedades en zonas rurales de la Huasteca.'],
                ],
                'pasos'       => [
                    ['Nos cuentas para qué lo necesitas', 'Por WhatsApp te decimos si requieres avalúo comercial, fiscal o para crédito, y te damos el costo.'],
                    ['Reunimos los documentos', 'Escritura o título de propiedad, predial pagado, identificación y croquis de ubicación.'],
                    ['Visita del perito', 'El perito certificado visita el inmueble, toma medidas y fotografías.'],
                    ['Entrega del avalúo', 'Recibes el avalúo firmado, listo para el notario, el banco o el instituto de vivienda.'],
                ],
                'faqs'        => [
                    ['¿Cuánto cuesta un avalúo inmobiliario?', 'Depende del tipo de avalúo, la ubicación y las características del inmueble. En Huejutla de Reyes y la región Huasteca los precios van aproximadamente de $2,500 a $6,000 MXN. Te damos un presupuesto sin compromiso.'],
                    ['¿Cuánto tarda un avalúo?', 'Normalmente entre 3 y 7 días hábiles a partir de la visita del perito, según el tipo de avalúo y la carga de trabajo del instituto cuando es para crédito.'],
                    ['¿Cuál es la diferencia entre avalúo fiscal y comercial?', 'El comercial refleja el valor de mercado y se usa para vender o para un crédito bancario. El fiscal sirve para calcular impuestos ante notario. Muchas operaciones requieren los dos.'],
                    ['¿Qué documentos necesito para un avalúo?', 'Escritura o título de propiedad, boleta predial al corriente, identificación oficial del propietario y, si existe, el plano o croquis del inmueble.'],
                ],
            ],

            'credito-infonavit' => [
                'tipo'        => 'servicio',
                'ruta'        => 'servicios.infonavit',
                'eyebrow'     => 'Asesoría en crédito INFONAVIT',
                'h1'          => 'Asesoría para tramitar tu crédito INFONAVIT en Hidalgo y la Huasteca',
                'titulo'      => 'Crédito INFONAVIT en Hidalgo | Asesoría y trámite completo',
                'descripcion' => 'Te ayudamos a precalificar, elegir casa, hacer el avalúo y escriturar con tu crédito INFONAVIT en Huejutla, Pachuca, la Huasteca y San Luis Potosí. Pagas al terminar.',
                'categoria_blog' => 'INFONAVIT',
                'wa_texto'    => 'Necesito información sobre Crédito INFONAVIT',
                'intro'       => [
                    'Si trabajas en el sector privado y cotizas en el IMSS, tienes derecho a un crédito INFONAVIT para comprar casa, terreno o construir. El problema es que el trámite tiene muchos pasos: puntos, precalificación, elección de vivienda, avalúo, notario y firma. Un error en cualquiera retrasa meses tu crédito.',
                    'En Consultoría Inmobiliaria te acompañamos de principio a fin. Revisamos tu precalificación, te ayudamos a encontrar la propiedad, coordinamos el avalúo con un perito autorizado y gestionamos la escrituración. Más de 500 familias ya recibieron sus llaves con nosotros.',
                ],
                'incluye_titulo' => '¿Qué incluye la asesoría?',
                'incluye'     => [
                    ['Precalificación', 'Verificamos tus puntos y cuánto crédito tienes disponible antes de que busques casa.'],
                    ['Búsqueda de propiedad', 'Te orientamos para que la vivienda que elijas cumpla con los requisitos del instituto.'],
                    ['Avalúo con perito autorizado', 'Coordinamos el avalúo que exige INFONAVIT para autorizar el crédito.'],
                    ['Documentación y notario', 'Integramos tu expediente, lo presentamos y coordinamos la firma de escrituras.'],
                ],
                'pasos'       => [
                    ['Contacto inicial', 'Nos escribes por WhatsApp. Te escuchamos y evaluamos tu situación sin compromiso.'],
                    ['Precalificación', 'Revisamos tu crédito disponible en INFONAVIT y qué opciones tienes.'],
                    ['Avalúo y documentación', 'Hacemos el avalúo de la propiedad e integramos todo tu expediente.'],
                    ['Firma de escrituras', 'Firmas ante notario y la casa queda a tu nombre. Pagas nuestros honorarios al terminar.'],
                ],
                'faqs'        => [
                    ['¿Cuánto cobran por la asesoría?', 'No pagas nada al inicio. Nuestros honorarios se cubren al terminar el trámite, cuando ya tienes tu crédito autorizado y tus escrituras.'],
                    ['¿Qué necesito para tramitar mi crédito INFONAVIT?', 'Relación laboral vigente, los puntos mínimos que pide el instituto según tu salario, una propiedad seleccionada y un avalúo hecho por perito autorizado. Nosotros te acompañamos en cada paso.'],
                    ['¿Puedo usar mi crédito INFONAVIT si trabajo en el gobierno?', 'No. Los trabajadores del Estado tienen acceso al crédito FOVISSSTE. Si estás en el ISSSTE, revisa nuestra página de crédito FOVISSSTE: también te asesoramos.'],
                    ['¿Atienden fuera de Huejutla?', 'Sí. Atendemos Huejutla, Pachuca, Ixmiquilpan, Tula y la Huasteca hidalguense, la Huasteca veracruzana y San Luis Potosí, con cita y seguimiento por WhatsApp.'],
                ],
                'nota'        => 'Consultoría Inmobiliaria no forma parte de INFONAVIT ni lo representa oficialmente. Somos una consultoría privada que orienta y acompaña a sus clientes en el trámite de su crédito hipotecario.',
            ],

            'escrituracion-y-gestoria' => [
                'tipo'        => 'servicio',
                'ruta'        => 'servicios.escrituracion',
                'eyebrow'     => 'Gestión de escrituras',
                'h1'          => 'Escrituración y gestoría inmobiliaria en Hidalgo',
                'titulo'      => 'Escrituración de casas y terrenos en Hidalgo | Gestoría notarial',
                'descripcion' => 'Coordinamos el trámite de escrituras ante notario y Registro Público en Huejutla, Pachuca y la Huasteca: compraventa, regularización, herencias y crédito INFONAVIT o FOVISSSTE.',
                'categoria_blog' => null,
                'wa_texto'    => 'Necesito información sobre Gestión de Escrituras',
                'intro'       => [
                    'Una propiedad sin escrituras no se puede vender, heredar con seguridad ni usar para un crédito. La escrituración es el trámite que deja tu casa o terreno legalmente a tu nombre ante notario y en el Registro Público de la Propiedad.',
                    'La gestoría inmobiliaria consiste en que un profesional se encargue de todo ese proceso por ti: revisar documentos, pedir certificados, calcular impuestos, coordinar con el notario y dar seguimiento al registro. En Consultoría Inmobiliaria lo hacemos todos los días en Hidalgo, Veracruz y San Luis Potosí.',
                ],
                'incluye_titulo' => '¿En qué casos te ayudamos?',
                'incluye'     => [
                    ['Compraventa de casa o terreno', 'Revisamos que la propiedad esté libre de problemas y coordinamos la firma ante notario.'],
                    ['Escrituras con crédito INFONAVIT o FOVISSSTE', 'Integramos el expediente que pide el instituto y el notario para liberar el crédito.'],
                    ['Regularización de propiedades', 'Casas o terrenos sin escritura, con contrato privado o con títulos antiguos.'],
                    ['Herencias y donaciones', 'Te orientamos sobre el avalúo fiscal, los impuestos y el trámite ante notario.'],
                ],
                'pasos'       => [
                    ['Revisión de documentos', 'Analizamos escritura anterior, predial, identificaciones y la situación de la propiedad.'],
                    ['Certificados y avalúo', 'Tramitamos certificado de libertad de gravamen y el avalúo fiscal que pide el notario.'],
                    ['Firma ante notario', 'Coordinamos la cita, revisamos el proyecto de escritura y te acompañamos a firmar.'],
                    ['Inscripción en el Registro Público', 'Damos seguimiento hasta que la escritura queda inscrita y te la entregamos.'],
                ],
                'faqs'        => [
                    ['¿Qué es la gestoría inmobiliaria?', 'Es el servicio por el que un profesional se encarga de los trámites legales y administrativos de una compraventa: verificación de documentos, escrituras ante notario, pago de derechos y registro en el Registro Público de la Propiedad.'],
                    ['¿Cuánto tarda escriturar una casa?', 'Depende del notario, del municipio y de si la propiedad tiene todos sus documentos en orden. Cuando el expediente está completo, el proceso suele llevar algunas semanas; nosotros lo acortamos evitando errores y vueltas.'],
                    ['¿Qué pasa si mi terreno solo tiene contrato privado?', 'Es un caso frecuente en la Huasteca. Revisamos el origen de la propiedad y te decimos qué camino de regularización aplica para que puedas escriturar.'],
                    ['¿Trabajan con cualquier notaría?', 'Coordinamos con notarías de Huejutla, Pachuca y la región. Si ya tienes un notario de confianza, trabajamos con él.'],
                ],
            ],
        ];
    }

    public static function zonas(): array
    {
        return [
            'huejutla-hidalgo' => [
                'tipo'        => 'zona',
                'ruta'        => 'zonas.huejutla',
                'ciudad'      => 'Huejutla de Reyes',
                'estado'      => 'Hidalgo',
                'eyebrow'     => 'Oficina principal · Huasteca hidalguense',
                'h1'          => 'Consultoría inmobiliaria en Huejutla de Reyes, Hidalgo',
                'titulo'      => 'Consultoría Inmobiliaria en Huejutla, Hidalgo | Crédito, avalúos y escrituras',
                'descripcion' => 'Asesores inmobiliarios en Huejutla de Reyes: crédito INFONAVIT y FOVISSSTE, avalúos y escrituración para toda la Huasteca hidalguense. Oficina en Plaza Tecoluco.',
                'categoria_blog' => null,
                'wa_texto'    => 'Necesito asesoría inmobiliaria en Huejutla',
                'intro'       => [
                    'Nuestra oficina está en Huejutla de Reyes, en Plaza Tecoluco sobre la avenida Corona del Rosal. Desde aquí atendemos a familias de toda la Huasteca hidalguense que quieren comprar casa con su crédito INFONAVIT o FOVISSSTE, valuar o escriturar una propiedad, o simplemente saber por dónde empezar.',
                    'Conocemos las notarías, los peritos y las particularidades de la región: terrenos con contrato privado, propiedades ejidales, viviendas que necesitan regularizarse antes de usar un crédito. Eso nos permite resolver trámites que en otro lado se quedan atorados.',
                ],
                'incluye_titulo' => '¿Qué hacemos en Huejutla y la Huasteca?',
                'incluye'     => [
                    ['Crédito INFONAVIT y FOVISSSTE', 'Precalificación, búsqueda de vivienda, avalúo, expediente y firma de escrituras.'],
                    ['Avalúos con perito certificado', 'Comerciales, fiscales y para crédito, con visita en Huejutla y municipios cercanos.'],
                    ['Escrituración y regularización', 'Compraventas, herencias y terrenos sin escritura en la Huasteca.'],
                    ['Atención presencial', 'Visítanos en Plaza Tecoluco o escríbenos por WhatsApp y agendamos tu cita.'],
                ],
                'pasos'       => [
                    ['Nos escribes o nos visitas', 'Por WhatsApp o en la oficina. Te escuchamos y evaluamos tu caso sin compromiso.'],
                    ['Revisamos tu crédito o tu propiedad', 'Precalificación en INFONAVIT o FOVISSSTE, o revisión de los documentos del inmueble.'],
                    ['Hacemos el trámite', 'Avalúo, expediente, notario y Registro Público. Te informamos en cada etapa.'],
                    ['Recibes tus llaves o tus escrituras', 'Pagas nuestros honorarios al terminar, cuando el trámite está resuelto.'],
                ],
                'faqs'        => [
                    ['¿Dónde está la oficina?', 'En Plaza Tecoluco, avenida Corona del Rosal, Huejutla de Reyes, Hidalgo. Escríbenos por WhatsApp para agendar tu visita.'],
                    ['¿Atienden otros municipios de la Huasteca hidalguense?', 'Sí. Atendemos Huejutla y los municipios cercanos de la Huasteca, así como Ixmiquilpan, Tula y Pachuca. También la Huasteca veracruzana y San Luis Potosí.'],
                    ['¿Cuánto cuesta un avalúo en Huejutla?', 'En Huejutla y la región Huasteca los avalúos van aproximadamente de $2,500 a $6,000 MXN según el tipo y el inmueble. Pide tu cotización sin compromiso.'],
                    ['¿Tengo que pagar algo al inicio?', 'No. La asesoría para tu crédito se paga al terminar el trámite. Los avalúos y gastos notariales se cotizan por separado antes de empezar.'],
                ],
            ],

            'pachuca-hidalgo' => [
                'tipo'        => 'zona',
                'ruta'        => 'zonas.pachuca',
                'ciudad'      => 'Pachuca de Soto',
                'estado'      => 'Hidalgo',
                'eyebrow'     => 'Pachuca, Tula e Ixmiquilpan',
                'h1'          => 'Consultoría inmobiliaria en Pachuca y el centro de Hidalgo',
                'titulo'      => 'Consultoría Inmobiliaria en Pachuca, Hidalgo | INFONAVIT, FOVISSSTE y avalúos',
                'descripcion' => 'Asesoría para comprar casa con crédito INFONAVIT o FOVISSSTE, avalúos y escrituración en Pachuca, Tula e Ixmiquilpan. Atención con cita y seguimiento por WhatsApp.',
                'categoria_blog' => null,
                'wa_texto'    => 'Necesito asesoría inmobiliaria en Pachuca',
                'intro'       => [
                    'Pachuca concentra gran parte de la oferta de vivienda nueva y usada de Hidalgo, y también a miles de trabajadores del gobierno estatal y federal con crédito FOVISSSTE que no saben cómo ejercerlo. Lo mismo ocurre en Tula e Ixmiquilpan con trabajadores del sector privado y su crédito INFONAVIT.',
                    'Consultoría Inmobiliaria atiende Pachuca y el centro del estado con cita previa y seguimiento por WhatsApp. Te ayudamos a precalificar, elegir la vivienda correcta, hacer el avalúo con perito autorizado y llegar a la firma de escrituras sin contratiempos.',
                ],
                'incluye_titulo' => '¿Qué hacemos en Pachuca, Tula e Ixmiquilpan?',
                'incluye'     => [
                    ['Crédito FOVISSSTE para trabajadores del Estado', 'Maestros, personal de salud y burócratas: revisamos tu puntaje y tramitamos tu crédito.'],
                    ['Crédito INFONAVIT', 'Precalificación, elección de vivienda, avalúo y escrituración.'],
                    ['Avalúos comerciales y fiscales', 'Con peritos certificados que visitan la propiedad en Pachuca y la región.'],
                    ['Escrituración', 'Compraventas, regularizaciones y herencias ante notarías de la zona.'],
                ],
                'pasos'       => [
                    ['Primer contacto por WhatsApp', 'Nos cuentas tu situación: dónde trabajas, qué crédito tienes y qué buscas.'],
                    ['Precalificación y plan', 'Te decimos cuánto crédito tienes y qué tipo de vivienda puedes comprar.'],
                    ['Avalúo y expediente', 'Coordinamos el avalúo e integramos los documentos para el instituto y el notario.'],
                    ['Firma y entrega', 'Firmas ante notario en Pachuca y recibes tus llaves. Pagas al terminar.'],
                ],
                'faqs'        => [
                    ['¿Tienen oficina en Pachuca?', 'Nuestra oficina principal está en Huejutla de Reyes. En Pachuca, Tula e Ixmiquilpan atendemos con cita previa y damos seguimiento por WhatsApp y por nuestra app.'],
                    ['Soy maestro, ¿puedo comprar casa en Pachuca con FOVISSSTE?', 'Sí. Revisamos tu puntaje y tu crédito disponible, y te acompañamos desde la precalificación hasta las escrituras.'],
                    ['¿Me ayudan a encontrar la casa?', 'Te orientamos para que la vivienda cumpla con los requisitos del instituto y revisamos que sus documentos estén en orden antes de que apartes.'],
                    ['¿Cuánto cuesta la asesoría?', 'La asesoría para tu crédito se paga al terminar el trámite. Avalúos y gastos notariales se cotizan por separado.'],
                ],
            ],

            'huasteca-veracruzana' => [
                'tipo'        => 'zona',
                'ruta'        => 'zonas.veracruz',
                'ciudad'      => 'Huasteca veracruzana',
                'estado'      => 'Veracruz',
                'eyebrow'     => 'Norte de Veracruz',
                'h1'          => 'Consultoría inmobiliaria en la Huasteca veracruzana',
                'titulo'      => 'Consultoría Inmobiliaria en Veracruz (Huasteca) | Crédito, avalúos y escrituras',
                'descripcion' => 'Asesoría inmobiliaria para la Huasteca veracruzana: crédito INFONAVIT y FOVISSSTE, avalúos con perito certificado y escrituración. Agenda tu cita en tu municipio.',
                'categoria_blog' => null,
                'wa_texto'    => 'Necesito asesoría inmobiliaria en Veracruz',
                'intro'       => [
                    'La Huasteca veracruzana está a unos minutos de nuestra oficina en Huejutla, y muchas de las familias que asesoramos viven o trabajan en el norte de Veracruz. Por eso atendemos la región como si fuera casa: con visitas a tu municipio, cita previa y seguimiento por WhatsApp.',
                    'Si tienes crédito INFONAVIT o FOVISSSTE y quieres comprar en Veracruz, o necesitas valuar o escriturar una propiedad en la zona, te ayudamos con los mismos peritos, notarías y procesos que usamos en Hidalgo.',
                ],
                'incluye_titulo' => '¿Qué hacemos en la Huasteca veracruzana?',
                'incluye'     => [
                    ['Crédito INFONAVIT y FOVISSSTE', 'Precalificación, búsqueda de vivienda y trámite completo hasta la firma.'],
                    ['Avalúos', 'Comerciales, fiscales y para crédito, con visita del perito a tu propiedad.'],
                    ['Escrituración', 'Compraventas y regularización de terrenos con contrato privado.'],
                    ['Atención en tu municipio', 'Agendamos la cita donde te quede mejor y seguimos el trámite a distancia.'],
                ],
                'pasos'       => [
                    ['Contacto por WhatsApp', 'Nos dices en qué municipio estás y qué necesitas.'],
                    ['Cita y revisión', 'Revisamos tu crédito o los documentos de la propiedad.'],
                    ['Trámite', 'Avalúo, expediente y coordinación con el notario.'],
                    ['Entrega', 'Llaves o escrituras en tus manos. Pagas al terminar.'],
                ],
                'faqs'        => [
                    ['¿Qué municipios de Veracruz atienden?', 'La Huasteca veracruzana y el norte del estado. Escríbenos con tu municipio y te confirmamos la cita.'],
                    ['¿Puedo usar mi crédito INFONAVIT de otro estado para comprar en Veracruz?', 'El crédito INFONAVIT se puede ejercer en cualquier estado del país. Revisamos tu caso y te decimos cómo hacerlo.'],
                    ['¿Hacen avalúos en zonas rurales?', 'Sí. Nuestros peritos valúan casas, terrenos y locales también en comunidades y zonas rurales de la Huasteca.'],
                    ['¿Tengo que ir a Huejutla?', 'No es necesario. La mayor parte del trámite se hace a distancia y las firmas se coordinan con la notaría más conveniente.'],
                ],
            ],

            'san-luis-potosi' => [
                'tipo'        => 'zona',
                'ruta'        => 'zonas.slp',
                'ciudad'      => 'San Luis Potosí',
                'estado'      => 'San Luis Potosí',
                'eyebrow'     => 'Huasteca potosina y San Luis Potosí',
                'h1'          => 'Consultoría inmobiliaria en San Luis Potosí',
                'titulo'      => 'Consultoría Inmobiliaria en San Luis Potosí | INFONAVIT, FOVISSSTE y avalúos',
                'descripcion' => 'Asesoría para comprar casa con crédito INFONAVIT o FOVISSSTE, avalúos y escrituración en la Huasteca potosina y San Luis Potosí. Atención con cita previa.',
                'categoria_blog' => null,
                'wa_texto'    => 'Necesito asesoría inmobiliaria en San Luis Potosí',
                'intro'       => [
                    'San Luis Potosí, y en especial la Huasteca potosina, forma parte de la región que atendemos desde Huejutla. Trabajadores del IMSS con crédito INFONAVIT y trabajadores del Estado con crédito FOVISSSTE nos buscan para ejercer su crédito sin perderse en el trámite.',
                    'Te atendemos con cita previa en tu municipio y seguimiento por WhatsApp. Precalificamos tu crédito, coordinamos el avalúo con perito autorizado, integramos tu expediente y te acompañamos a la firma de escrituras.',
                ],
                'incluye_titulo' => '¿Qué hacemos en San Luis Potosí?',
                'incluye'     => [
                    ['Crédito INFONAVIT y FOVISSSTE', 'Trámite completo: precalificación, vivienda, avalúo, expediente y firma.'],
                    ['Avalúos con perito certificado', 'Comerciales, fiscales y para crédito en la Huasteca potosina.'],
                    ['Escrituración', 'Compraventas, herencias y regularización de propiedades.'],
                    ['Asesoría personalizada', 'Analizamos tu situación y te decimos cuál es la mejor opción para tu patrimonio.'],
                ],
                'pasos'       => [
                    ['Contacto por WhatsApp', 'Nos cuentas tu situación y en qué municipio estás.'],
                    ['Precalificación', 'Revisamos tu crédito disponible en INFONAVIT o FOVISSSTE.'],
                    ['Avalúo y expediente', 'Coordinamos el avalúo e integramos los documentos.'],
                    ['Firma de escrituras', 'Firmas ante notario y recibes tus llaves. Pagas al terminar.'],
                ],
                'faqs'        => [
                    ['¿Qué parte de San Luis Potosí atienden?', 'Principalmente la Huasteca potosina. Si tu propiedad está en otra zona del estado, escríbenos: es probable que podamos atenderte.'],
                    ['¿La asesoría tiene costo inicial?', 'No. Nuestros honorarios por la asesoría de crédito se pagan al terminar el trámite.'],
                    ['¿Cuánto tarda el trámite del crédito?', 'Depende del instituto, de la propiedad y del notario. Con el expediente completo y bien integrado el proceso avanza mucho más rápido; te informamos en cada etapa.'],
                    ['¿Qué documentos necesito para empezar?', 'Identificación oficial, CURP, comprobante de domicilio y tu número de seguridad social o de trabajador del Estado. Con eso hacemos la precalificación.'],
                ],
            ],
        ];
    }

    public static function servicio(string $slug): ?array
    {
        return self::servicios()[$slug] ?? null;
    }

    public static function zona(string $slug): ?array
    {
        return self::zonas()[$slug] ?? null;
    }
}
