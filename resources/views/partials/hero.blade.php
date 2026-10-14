@php
    $heroImage = setting('hero_image')
        ? asset('storage/' . setting('hero_image'))
        : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1600&q=80';
@endphp
{{-- Hero: ocupa toda la pantalla; al hacer scroll el contenido se aleja y se desvanece (--p de 0 a 1) --}}
<section id="inicio" class="relative h-[135vh]" x-data x-scrollprogress.sticky>
    <div class="sticky top-0 flex h-screen items-center justify-center overflow-hidden bg-dark-900">

        {{-- Fondo: Ken Burns + parallax; se oscurece y escala con el scroll --}}
        <div class="absolute inset-0" style="transform: scale(calc(1 + var(--p, 0) * 0.12)); opacity: calc(1 - var(--p, 0) * 0.55);">
            <div class="absolute inset-0 kenburns bg-cover bg-center" style="background-image:url('{{ $heroImage }}')"></div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-dark-900/70 via-dark-900/55 to-dark-900"></div>
        <div class="absolute inset-0 bg-[radial-gradient(60%_50%_at_50%_40%,rgba(205,157,54,0.18),transparent_70%)]"></div>

        {{-- Contenido centrado --}}
        <div class="relative z-10 mx-auto max-w-5xl px-5 text-center"
             style="transform: translate3d(0, calc(var(--p, 0) * -70px), 0) scale(calc(1 - var(--p, 0) * 0.06)); opacity: calc(1 - var(--p, 0) * 1.6);">
            <p class="mb-5 text-xs font-semibold uppercase tracking-[0.22em] text-gold-300 sm:text-sm" x-data x-reveal.blur>
                Consultoría Inmobiliaria · Huejutla de Reyes, Hidalgo
            </p>

            <h1 class="font-serif text-[2.9rem] font-bold leading-[0.98] text-white sm:text-7xl lg:text-[6.5rem]"
                style="letter-spacing:-0.045em" x-data x-split.delay.150>
                Tu <span class="text-gold-shine">patrimonio,</span><br>
                nuestra <span class="text-gold-shine">prioridad.</span>
            </h1>

            <p class="mx-auto mt-7 max-w-2xl text-lg leading-relaxed text-white/75 sm:text-xl" x-data x-reveal.delay.700>
                Tramitamos tu crédito <strong class="font-semibold text-white">INFONAVIT</strong> o
                <strong class="font-semibold text-white">FOVISSSTE</strong> en Hidalgo, Veracruz y San Luis Potosí.
                Avalúos, escrituras y gestoría sin costo inicial.
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row" x-data x-reveal.delay.900>
                <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text=Hola,%20quiero%20información"
                   target="_blank" rel="noopener noreferrer" class="btn-gold !px-8 !py-4 text-base" x-magnetic>
                    <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Hablar con un asesor
                </a>
                <a href="#servicios" class="btn-dark !px-8 !py-4 text-base group" x-magnetic>
                    Ver servicios
                    <svg class="h-4 w-4 transition-transform duration-500 group-hover:translate-x-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <p class="mx-auto mt-8 max-w-xl text-[11px] leading-relaxed text-white/40" x-data x-reveal.delay.1100>
                Consultoría Inmobiliaria no forma parte de INFONAVIT ni de FOVISSSTE, ni los representa oficialmente.
                Somos una consultoría privada que orienta y acompaña a nuestros clientes en su trámite de crédito hipotecario.
            </p>
        </div>

        {{-- Indicador de scroll --}}
        <div class="absolute bottom-40 left-1/2 z-10 hidden -translate-x-1/2 sm:block" style="opacity: calc(1 - var(--p, 0) * 6);">
            <div class="flex h-10 w-6 justify-center rounded-full border border-white/30 pt-2">
                <span class="h-2 w-1 animate-bounce rounded-full bg-white/70"></span>
            </div>
        </div>

        {{-- Cifras en vidrio, flotando al pie --}}
        <div class="absolute inset-x-0 bottom-6 z-10 px-4" style="opacity: calc(1 - var(--p, 0) * 2.2); transform: translate3d(0, calc(var(--p, 0) * 40px), 0);">
            <div class="glass mx-auto grid max-w-3xl grid-cols-3 divide-x divide-white/10 rounded-3xl" x-data x-reveal.delay.1300>
                <div class="px-2 py-4 text-center sm:py-5" x-data="countUp(500, { prefix: '+', duration: 1800 })">
                    <div class="font-serif text-2xl font-bold text-gold-300 sm:text-4xl" x-text="display"></div>
                    <div class="mt-1 text-[10px] uppercase tracking-wider text-white/60 sm:text-xs">Familias asesoradas</div>
                </div>
                <div class="px-2 py-4 text-center sm:py-5" x-data="countUp(4, { duration: 1200 })">
                    <div class="font-serif text-2xl font-bold text-gold-300 sm:text-4xl" x-text="display"></div>
                    <div class="mt-1 text-[10px] uppercase tracking-wider text-white/60 sm:text-xs">Estados</div>
                </div>
                <div class="px-2 py-4 text-center sm:py-5" x-data="countUp(100, { suffix: '%', duration: 1800 })">
                    <div class="font-serif text-2xl font-bold text-gold-300 sm:text-4xl" x-text="display"></div>
                    <div class="mt-1 text-[10px] uppercase tracking-wider text-white/60 sm:text-xs">Pagas al terminar</div>
                </div>
            </div>
        </div>
    </div>
</section>
