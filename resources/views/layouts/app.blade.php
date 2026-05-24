<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO básico --}}
    <title>@yield('seo_title', setting('seo_titulo', 'Consultoría Inmobiliaria'))</title>
    <meta name="description" content="@yield('seo_description', setting('seo_descripcion', 'Asesores expertos en crédito INFONAVIT, FOVISSSTE, avalúos y escrituras.'))">
    @if(setting('seo_keywords'))
    <meta name="keywords" content="{{ setting('seo_keywords') }}">
    @endif
    @if(setting('seo_autor'))
    <meta name="author" content="{{ setting('seo_autor') }}">
    @endif
    <meta name="robots" content="@yield('robots', setting('seo_robots', 'index, follow'))">

    {{-- Canonical --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- Open Graph --}}
    <meta property="og:site_name" content="{{ setting('site_name', 'Consultoría Inmobiliaria') }}">
    <meta property="og:title" content="@yield('og_title', setting('seo_titulo', 'Consultoría Inmobiliaria'))">
    <meta property="og:description" content="@yield('og_description', setting('seo_descripcion', ''))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    @php $ogImage = trim(\Illuminate\Support\Facades\View::yieldContent('og_image') ?: (setting('seo_og_imagen') ? asset('storage/' . setting('seo_og_imagen')) : '')); @endphp
    @if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @endif
    <meta property="og:locale" content="es_MX">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    @if(setting('twitter_handle'))
    <meta name="twitter:site" content="{{ setting('twitter_handle') }}">
    <meta name="twitter:creator" content="{{ setting('twitter_handle') }}">
    @endif
    <meta name="twitter:title" content="@yield('og_title', setting('seo_titulo', 'Consultoría Inmobiliaria'))">
    <meta name="twitter:description" content="@yield('og_description', setting('seo_descripcion', ''))">
    @if($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    {{-- Google Search Console verification --}}
    @if(setting('gsc_verification'))
    <meta name="google-site-verification" content="{{ setting('gsc_verification') }}">
    @endif

    {{-- Favicon --}}
    @if(setting('favicon'))
    <link rel="icon" href="{{ asset('storage/' . setting('favicon')) }}">
    @else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    {{-- View Transition API --}}
    <meta name="view-transition" content="same-origin">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    {{-- JSON-LD extra por página --}}
    @stack('jsonld')
</head>
<body class="antialiased">

    @include('partials.navbar')

    <main style="view-transition-name: main-content;">@yield('content')</main>

    @include('partials.footer')

    {{-- WhatsApp flotante --}}
    <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text=Hola,%20me%20interesa%20información%20sobre%20sus%20servicios"
       target="_blank" rel="noopener noreferrer" class="whatsapp-float" title="WhatsApp">
        <svg class="w-7 h-7 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>

    @stack('scripts')

    {{-- View Transition API — intercept same-origin navigation --}}
    <script>
        if ('startViewTransition' in document) {
            document.addEventListener('click', (e) => {
                const a = e.target.closest('a[href]');
                if (!a) return;

                const url = new URL(a.href, location.href);

                // Solo misma origin, no anclas (#), no target="_blank", no admin
                if (
                    url.origin !== location.origin ||
                    url.pathname === location.pathname && url.hash ||
                    a.target === '_blank' ||
                    url.pathname.startsWith('/admin')
                ) return;

                e.preventDefault();
                document.startViewTransition(() => {
                    location.href = a.href;
                });
            });
        }
    </script>

    {{-- Google Analytics 4 — solo carga tras consentimiento de cookies --}}
    @if(setting('ga4_id'))
    <script>
        window.__ga4Id = '{{ setting('ga4_id') }}';
        function loadGA4() {
            if (window.__ga4Loaded) return;
            window.__ga4Loaded = true;
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + window.__ga4Id;
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            window.gtag = gtag;
            gtag('js', new Date());
            gtag('config', window.__ga4Id);
        }
        // Si ya aceptó cookies previamente, carga inmediatamente
        if (localStorage.getItem('cookies_accepted') === 'true') {
            loadGA4();
        }
        // Escucha el evento de aceptación del banner
        window.addEventListener('cookies:accepted', loadGA4);
    </script>
    @endif

    {{-- Banner de cookies --}}
    @include('partials.cookies-banner')

    {{-- JSON-LD RealEstateAgent (global) --}}
    @php
        $socialLinks = collect([setting('facebook_url'), setting('instagram_url')])->filter()->values();
    @endphp
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "RealEstateAgent",
        "name": "{{ setting('site_name', 'Consultoría Inmobiliaria') }}",
        "url": "{{ config('app.url') }}",
        "logo": "{{ setting('logo') ? asset('storage/' . setting('logo')) : asset('favicon.ico') }}",
        "description": "{{ setting('seo_descripcion', 'Asesores expertos en crédito INFONAVIT, FOVISSSTE, avalúos y escrituras.') }}",
        "telephone": "+52{{ setting('whatsapp_1', '7711910395') }}",
        "address": {
            "@@type": "PostalAddress",
            "streetAddress": "{{ setting('direccion_calle', 'Plaza Tecoluco, Av. Corona del Rosal') }}",
            "addressLocality": "{{ setting('direccion_ciudad', 'Huejutla de Reyes') }}",
            "addressRegion": "{{ setting('direccion_estado', 'Hidalgo') }}",
            "postalCode": "{{ setting('direccion_cp', '43000') }}",
            "addressCountry": "MX"
        },
        "areaServed": [
            { "@@type": "State", "name": "Hidalgo", "sameAs": "https://www.wikidata.org/wiki/Q80074" },
            { "@@type": "State", "name": "Veracruz", "sameAs": "https://www.wikidata.org/wiki/Q80080" },
            { "@@type": "State", "name": "San Luis Potosí", "sameAs": "https://www.wikidata.org/wiki/Q80078" }
        ],
        "contactPoint": {
            "@@type": "ContactPoint",
            "contactType": "customer service",
            "telephone": "+52{{ setting('whatsapp_1', '7711910395') }}",
            "availableLanguage": "Spanish"
        }
        @if($socialLinks->isNotEmpty())
        ,"sameAs": {!! $socialLinks->toJson() !!}
        @endif
    }
    </script>

<x-env-ribbon />
</body>
</html>
