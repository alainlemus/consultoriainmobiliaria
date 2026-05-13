<!DOCTYPE html>
<html lang="es">
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
    <meta name="robots" content="{{ setting('seo_robots', 'index, follow') }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', setting('seo_titulo', 'Consultoría Inmobiliaria'))">
    <meta property="og:description" content="@yield('og_description', setting('seo_descripcion', ''))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:image" content="@yield('og_image', setting('seo_og_imagen') ? asset('storage/' . setting('seo_og_imagen')) : '')">
    <meta property="og:locale" content="es_MX">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', setting('seo_titulo', 'Consultoría Inmobiliaria'))">
    <meta name="twitter:description" content="@yield('og_description', setting('seo_descripcion', ''))">
    <meta name="twitter:image" content="@yield('og_image', setting('seo_og_imagen') ? asset('storage/' . setting('seo_og_imagen')) : '')">

    {{-- Favicon --}}
    @if(setting('favicon'))
    <link rel="icon" href="{{ asset('storage/' . setting('favicon')) }}">
    @else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400&family=Inter:wght@300;400;500;600&family=Dancing+Script:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="antialiased">

    @include('partials.navbar')

    <main>@yield('content')</main>

    @include('partials.footer')

    {{-- WhatsApp flotante --}}
    <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text=Hola,%20me%20interesa%20información%20sobre%20sus%20servicios"
       target="_blank" class="whatsapp-float" title="WhatsApp">
        <svg class="w-7 h-7 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>

    @stack('scripts')

    {{-- Banner de cookies --}}
    @include('partials.cookies-banner')

</body>
</html>
