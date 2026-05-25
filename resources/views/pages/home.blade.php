@extends('layouts.app')

@section('seo_title', 'Consultoría Inmobiliaria - Tu patrimonio, nuestra prioridad')
@section('seo_description', 'Asesores expertos en crédito INFONAVIT y FOVISSSTE, avalúos comerciales y gestión de escrituras en Hidalgo, Veracruz y San Luis Potosí. +500 familias asesoradas.')
@section('og_title', 'Consultoría Inmobiliaria - Tu patrimonio, nuestra prioridad')
@section('og_description', 'Asesores expertos en crédito INFONAVIT y FOVISSSTE, avalúos comerciales y gestión de escrituras en Hidalgo, Veracruz y San Luis Potosí. +500 familias asesoradas.')

@section('content')
    @include('partials.hero')
    @include('partials.servicios')
    @include('partials.porque-elegirnos')
    @include('partials.proceso')
    @include('partials.cobertura')
    @include('partials.propiedades')
    @include('partials.testimonios')
    @include('partials.blog')
    @include('partials.contacto')
@endsection

@push('jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "¿Cómo tramitar un crédito INFONAVIT en Hidalgo?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Para tramitar tu crédito INFONAVIT en Hidalgo necesitas tener relación laboral vigente, un mínimo de puntos INFONAVIT (que varía según tu salario), una propiedad seleccionada y un perito valuador autorizado. En Consultoría Inmobiliaria te acompañamos en todo el proceso sin costo inicial: valuación, gestión de documentos, trámite ante el notario y escrituración."
            }
        },
        {
            "@@type": "Question",
            "name": "¿Qué documentos necesito para tramitar mi crédito FOVISSSTE?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Para el crédito FOVISSSTE necesitas: identificación oficial vigente, CURP, acta de nacimiento, comprobante de domicilio, constancia de empleo con sueldo, estados de cuenta de los últimos 3 meses, y los documentos de la propiedad (escrituras o contrato de promesa de compraventa). Nosotros te orientamos para reunir todo correctamente y evitar rechazos."
            }
        },
        {
            "@@type": "Question",
            "name": "¿Cuánto cuesta un avalúo inmobiliario?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "El costo de un avalúo inmobiliario depende del tipo (comercial o fiscal), la ubicación y las características del inmueble. En Huejutla de Reyes y la región Huasteca los precios varían entre $2,500 y $6,000 MXN aproximadamente. Contamos con peritos valuadores certificados. Contáctanos para un presupuesto sin compromiso."
            }
        },
        {
            "@@type": "Question",
            "name": "¿Qué es la gestoría inmobiliaria y para qué sirve?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "La gestoría inmobiliaria es el servicio mediante el cual un profesional se encarga de todos los trámites legales y administrativos para la compraventa de un inmueble: verificación de documentos, trámite de escrituras ante notario, pago de derechos y registro en el Registro Público de la Propiedad. Esto te ahorra tiempo, evita errores costosos y garantiza que la compraventa sea legal y segura."
            }
        },
        {
            "@@type": "Question",
            "name": "¿Puedo tramitar crédito INFONAVIT si soy trabajador del ISSSTE?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. Los trabajadores del ISSSTE tienen acceso al crédito FOVISSSTE, no al INFONAVIT. El INFONAVIT es exclusivo para trabajadores del sector privado afiliados al IMSS. Si eres trabajador del gobierno federal o estatal, tu crédito es FOVISSSTE. Podemos asesorarte en ambos tipos de crédito hipotecario."
            }
        },
        {
            "@@type": "Question",
            "name": "¿En qué estados tienen cobertura?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Atendemos principalmente Hidalgo (Huejutla de Reyes, Ixmiquilpan, Tula, Pachuca), Veracruz (zona Huasteca) y San Luis Potosí. Si tu propiedad está en otra región, contáctanos: es posible que podamos atenderte igualmente."
            }
        }
    ]
}
</script>
@endpush
