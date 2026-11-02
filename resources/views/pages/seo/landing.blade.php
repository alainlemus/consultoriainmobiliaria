@extends('layouts.app')

@php
    $wa      = setting('whatsapp_1', '527711910395');
    $waLink  = 'https://wa.me/' . $wa . '?text=' . rawurlencode($pagina['wa_texto']);
    $urlPagina = route($pagina['ruta']);
    $esZona  = ($pagina['tipo'] ?? '') === 'zona';
    $migaPadre = $esZona
        ? ['nombre' => 'Cobertura', 'url' => route('home') . '#cobertura']
        : ['nombre' => 'Servicios', 'url' => route('home') . '#servicios'];
@endphp

@section('seo_title', $pagina['titulo'])
@section('seo_description', $pagina['descripcion'])
@section('og_title', $pagina['titulo'])
@section('og_description', $pagina['descripcion'])
@section('og_url', $urlPagina)
@section('canonical', $urlPagina)

@push('jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Inicio", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "{{ $migaPadre['nombre'] }}", "item": "{{ $migaPadre['url'] }}" },
        { "@@type": "ListItem", "position": 3, "name": "{{ $pagina['h1'] }}", "item": "{{ $urlPagina }}" }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach($pagina['faqs'] as $faq)
        {
            "@@type": "Question",
            "name": {!! json_encode($faq[0], JSON_UNESCAPED_UNICODE) !!},
            "acceptedAnswer": { "@@type": "Answer", "text": {!! json_encode($faq[1], JSON_UNESCAPED_UNICODE) !!} }
        }{{ $loop->last ? '' : ',' }}
        @endforeach
    ]
}
</script>
@if($esZona)
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "RealEstateAgent",
    "name": "{{ setting('site_name', 'Consultoría Inmobiliaria') }}",
    "url": "{{ $urlPagina }}",
    "telephone": "+52{{ setting('whatsapp_1', '7711910395') }}",
    "areaServed": { "@@type": "Place", "name": {!! json_encode($pagina['ciudad'] . ', ' . $pagina['estado'], JSON_UNESCAPED_UNICODE) !!} },
    "address": {
        "@@type": "PostalAddress",
        "streetAddress": "{{ setting('direccion_calle', 'Plaza Tecoluco, Av. Corona del Rosal') }}",
        "addressLocality": "{{ setting('direccion_ciudad', 'Huejutla de Reyes') }}",
        "addressRegion": "{{ setting('direccion_estado', 'Hidalgo') }}",
        "postalCode": "{{ setting('direccion_cp', '43000') }}",
        "addressCountry": "MX"
    }
}
</script>
@else
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Service",
    "name": {!! json_encode($pagina['h1'], JSON_UNESCAPED_UNICODE) !!},
    "description": {!! json_encode($pagina['descripcion'], JSON_UNESCAPED_UNICODE) !!},
    "url": "{{ $urlPagina }}",
    "provider": {
        "@@type": "RealEstateAgent",
        "name": "{{ setting('site_name', 'Consultoría Inmobiliaria') }}",
        "url": "{{ config('app.url') }}",
        "telephone": "+52{{ setting('whatsapp_1', '7711910395') }}"
    },
    "areaServed": [
        { "@@type": "State", "name": "Hidalgo" },
        { "@@type": "State", "name": "Veracruz" },
        { "@@type": "State", "name": "San Luis Potosí" }
    ]
}
</script>
@endif
@endpush

@section('content')

{{-- ── Encabezado ──────────────────────────────────────────────────────── --}}
<section class="relative overflow-hidden bg-dark-900" style="padding-top: 140px;">
    <div class="absolute top-0 left-0 w-64 h-64 -translate-x-1/2 -translate-y-1/2 border rounded-full border-gold-500/10"></div>
    <div class="absolute bottom-0 right-0 translate-x-1/2 translate-y-1/2 border rounded-full w-96 h-96 border-gold-500/10"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <nav class="text-xs uppercase tracking-wider mb-6 flex flex-wrap items-center gap-2" aria-label="Migas de pan">
            <a href="{{ route('home') }}" class="text-white hover:text-gold-400 transition-colors">Inicio</a>
            <span class="text-gold-400">›</span>
            <a href="{{ $migaPadre['url'] }}" class="text-white hover:text-gold-400 transition-colors">{{ $migaPadre['nombre'] }}</a>
            <span class="text-gold-400">›</span>
            <span class="text-gold-400">{{ $esZona ? $pagina['ciudad'] : $pagina['eyebrow'] }}</span>
        </nav>

        <p class="section-subtitle text-gold-400 mb-3" x-reveal.blur>{{ $pagina['eyebrow'] }}</p>
        <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight mb-6" x-split>
            {{ $pagina['h1'] }}
        </h1>
        <div class="w-20 h-0.5 bg-gold-400 mb-6"></div>

        <div class="space-y-4 text-cream-200 text-lg leading-relaxed max-w-3xl" x-reveal.delay.200>
            @foreach($pagina['intro'] as $parrafo)
                <p>{{ $parrafo }}</p>
            @endforeach
        </div>

        <div class="mt-10 flex flex-col sm:flex-row gap-4" x-reveal.delay.400>
            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="btn-gold !px-8 !py-4 text-base justify-center" x-magnetic>
                Hablar con un asesor por WhatsApp
            </a>
            <a href="#como-trabajamos" class="btn-dark !px-8 !py-4 text-base justify-center">Ver cómo trabajamos</a>
        </div>
    </div>
</section>

{{-- ── Qué incluye ─────────────────────────────────────────────────────── --}}
<section class="py-24 bg-cream-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="section-title mb-4" x-split>{{ $pagina['incluye_titulo'] }}</h2>
            <div class="gold-divider" x-reveal.scale.delay.300></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($pagina['incluye'] as $item)
            <div class="card-service flex flex-col text-left" x-reveal.scale.delay.{{ ($loop->index % 4) * 120 }} data-spotlight>
                <div class="text-gold-500 text-2xl mb-3">✦</div>
                <h3 class="font-serif text-lg font-bold text-dark-800 mb-2">{{ $item[0] }}</h3>
                <p class="text-sm text-dark-500 leading-relaxed">{{ $item[1] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Cómo trabajamos ─────────────────────────────────────────────────── --}}
<section id="como-trabajamos" class="py-24 bg-dark-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <p class="section-subtitle text-gold-400 mb-3" x-reveal.blur>Paso a paso</p>
            <h2 class="font-serif text-3xl sm:text-4xl font-bold text-white mb-4" x-split>Así trabajamos contigo</h2>
            <div class="gold-divider" x-reveal.scale.delay.300></div>
        </div>
        <ol class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($pagina['pasos'] as $paso)
            <li class="glass rounded-3xl p-7 flex gap-5" x-reveal.delay.{{ $loop->index * 120 }}>
                <span class="font-serif text-3xl font-bold text-gold-400 shrink-0 leading-none">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <div>
                    <h3 class="font-serif text-lg font-bold text-white mb-1">{{ $paso[0] }}</h3>
                    <p class="text-sm text-cream-300 leading-relaxed">{{ $paso[1] }}</p>
                </div>
            </li>
            @endforeach
        </ol>
        @if(!empty($pagina['nota']))
            <p class="text-center text-cream-300/60 text-xs max-w-2xl mx-auto mt-10">{{ $pagina['nota'] }}</p>
        @endif
    </div>
</section>

{{-- ── Preguntas frecuentes ────────────────────────────────────────────── --}}
<section class="py-24 bg-cream-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="section-title mb-4" x-split>Preguntas frecuentes</h2>
            <div class="gold-divider" x-reveal.scale.delay.300></div>
        </div>
        <div class="space-y-3">
            @foreach($pagina['faqs'] as $faq)
            <details class="group bg-white rounded-2xl border border-cream-300 open:border-gold-300 transition-colors" x-reveal.delay.{{ $loop->index * 80 }}>
                <summary class="cursor-pointer list-none px-6 py-4 flex items-center justify-between gap-4 font-semibold text-dark-800">
                    <span>{{ $faq[0] }}</span>
                    <span class="text-gold-500 transition-transform group-open:rotate-45 text-xl leading-none">+</span>
                </summary>
                <p class="px-6 pb-5 text-sm text-dark-500 leading-relaxed">{{ $faq[1] }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Artículos relacionados ──────────────────────────────────────────── --}}
@if($posts->isNotEmpty())
<section class="py-20 bg-cream-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-serif text-2xl font-bold text-dark-800 mb-8" x-reveal>Guías que te pueden servir</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="bg-white border border-cream-300 rounded-2xl p-6 hover:border-gold-400 hover:shadow-lg transition-all group" x-reveal.delay.{{ $loop->index * 100 }}>
                @if($post->categoria)
                    <span class="text-xs text-gold-500 uppercase tracking-wider font-semibold">{{ $post->categoria }}</span>
                @endif
                <p class="font-bold text-dark-800 group-hover:text-gold-600 transition-colors mt-1 line-clamp-2">{{ $post->titulo }}</p>
                @if($post->resumen)
                    <p class="text-sm text-dark-500 mt-2 line-clamp-3">{{ $post->resumen }}</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ── Enlaces cruzados ────────────────────────────────────────────────── --}}
<section class="py-16 bg-cream-100 border-t border-cream-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="font-serif text-xl font-bold text-dark-800 mb-5">También te puede interesar</h2>
        <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-2 text-sm">
            @foreach($relacionadas as $rel)
                <li><a href="{{ $rel['url'] }}" class="text-dark-600 hover:text-gold-600 transition-colors">✦ {{ $rel['texto'] }}</a></li>
            @endforeach
            <li><a href="{{ route('fovissste.index') }}" class="text-dark-600 hover:text-gold-600 transition-colors">✦ Crédito FOVISSSTE: orientación para trabajadores del Estado</a></li>
            <li><a href="{{ route('blog.index') }}" class="text-dark-600 hover:text-gold-600 transition-colors">✦ Blog: guías sobre INFONAVIT, FOVISSSTE y avalúos</a></li>
        </ul>
    </div>
</section>

{{-- ── CTA final ───────────────────────────────────────────────────────── --}}
<section class="py-24 bg-dark-900 text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8" x-reveal>
        <p class="font-script text-gold-400 text-2xl mb-3">¡Estamos para ayudarte!</p>
        <h2 class="font-serif text-3xl sm:text-4xl font-bold text-white mb-4">Hablemos de tu patrimonio</h2>
        <p class="text-cream-300 mb-8">Un asesor te orienta sin costo y sin compromiso. Escríbenos por WhatsApp o llámanos.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="btn-gold !px-8 !py-4 text-base justify-center" x-magnetic>Escribir por WhatsApp</a>
            <a href="{{ route('home') }}#contacto" class="btn-dark !px-8 !py-4 text-base justify-center">Dejar mis datos</a>
        </div>
        <p class="mt-6 text-cream-300/60 text-xs">{{ setting('telefono_1', '771 191 0395') }} · {{ setting('direccion_calle', 'Plaza Tecoluco, Av. Corona del Rosal') }}, {{ setting('direccion_ciudad', 'Huejutla de Reyes') }}, {{ setting('direccion_estado', 'Hidalgo') }}</p>
    </div>
</section>

@endsection
