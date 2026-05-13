<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace no disponible — {{ config('app.name') }}</title>
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

            <div class="w-20 h-20 bg-gold-500/10 border border-gold-500/30 rounded-full flex items-center justify-center mx-auto mb-8">
                <svg class="w-10 h-10 text-gold-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>

            <p class="section-subtitle text-gold-400 mb-3">Enlace no disponible</p>
            <h1 class="text-3xl sm:text-4xl font-serif font-bold text-white mb-4">
                {{ $exception->getMessage() ?: 'Este enlace ya no está disponible' }}
            </h1>
            <div class="gold-divider mb-6"></div>
            <p class="text-cream-300 text-sm leading-relaxed mb-10">
                El enlace para dejar tu testimonio es de un solo uso y tiene una vigencia de 7 días.<br>
                Si necesitas un nuevo enlace, contáctanos.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/') }}" class="btn-gold">
                    ← Regresar al inicio
                </a>
            </div>

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
