<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada — {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">

    @if(app('App\Models\Configuracion')::get('favicon'))
    <link rel="icon" href="{{ asset('storage/' . app('App\Models\Configuracion')::get('favicon')) }}">
    @else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-dark-900 min-h-screen flex flex-col">

    @include('partials.navbar')

    <main class="flex-1 flex items-center justify-center px-4" style="padding-top: 80px;">
        <div class="text-center max-w-lg mx-auto py-20">

            {{-- Número 404 decorativo --}}
            <div class="relative mb-6 select-none">
                <span class="block font-serif font-bold text-gold-400/10 leading-none"
                      style="font-size: clamp(8rem, 20vw, 14rem);">404</span>
            </div>

            {{-- Mensaje --}}
            <p class="section-subtitle text-gold-400 mb-3">Error 404</p>
            <h1 class="text-3xl sm:text-4xl font-serif font-bold text-white mb-4">
                Página no encontrada
            </h1>
            <div class="gold-divider mb-6"></div>
            <p class="text-cream-300 text-sm leading-relaxed mb-10">
                La página que buscas no existe o fue movida.<br>
                Te invitamos a regresar al inicio o explorar nuestros servicios.
            </p>

            {{-- Acciones --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/') }}" class="btn-gold">
                    ← Regresar al inicio
                </a>
                <a href="{{ url('/#servicios') }}"
                   class="flex items-center justify-center px-6 py-3 border border-gold-400/40 text-gold-400 hover:border-gold-400 hover:bg-gold-400/10 text-sm uppercase tracking-wider transition-all duration-300">
                    Ver servicios
                </a>
            </div>

            {{-- WhatsApp --}}
            <p class="text-cream-300/50 text-xs mt-10">
                ¿Necesitas ayuda?
                <a href="https://wa.me/{{ app('App\Models\Configuracion')::get('whatsapp_1', '527711910395') }}"
                   target="_blank" class="text-gold-400 hover:text-gold-300 transition-colors">
                    Escríbenos por WhatsApp
                </a>
            </p>

        </div>
    </main>

    @include('partials.footer')

</body>
</html>
