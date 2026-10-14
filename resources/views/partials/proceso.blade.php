<section id="proceso" class="py-28 md:py-36 bg-cream-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-20">
            <p class="section-subtitle mb-4" x-reveal.blur>Cómo funciona</p>
            <h2 class="section-title mb-4" x-split>El proceso <span class="text-crimson-600">paso a paso.</span></h2>
            <div class="gold-divider" x-reveal.scale.delay.300></div>
            <p class="text-dark-600 mt-4 max-w-2xl mx-auto text-lg" x-reveal.delay.400>Te acompañamos desde el primer contacto hasta que tengas las llaves en mano.</p>
        </div>
        {{-- --p avanza de 0 a 1 mientras los pasos cruzan la pantalla: llena la línea y enciende cada paso --}}
        <div class="relative" x-scrollprogress>
            <div class="absolute hidden lg:block left-[12.5%] right-[12.5%] top-8 h-[3px] rounded-full bg-cream-300 overflow-hidden" aria-hidden="true">
                <div class="h-full origin-left rounded-full bg-gradient-to-r from-gold-400 to-crimson-600" style="transform: scaleX(clamp(0, calc((var(--p, 0) - 0.18) * 2.2), 1));"></div>
            </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($procesos as $paso)
            <div class="flex flex-col items-center text-center group" x-reveal.delay.{{ $loop->index * 140 }}>
                <div class="w-16 h-16 bg-white border-2 border-gold-400 rounded-full flex items-center justify-center mb-5 shadow-lg shadow-gold-400/20 group-hover:bg-crimson-600 group-hover:border-crimson-600 group-hover:scale-110 transition-all duration-500 z-10">
                    <span class="font-serif font-bold text-gold-500 text-xl group-hover:text-white transition-colors">{{ $paso->numero }}</span>
                </div>
                <div class="relative bg-white rounded-3xl p-6 shadow-sm border border-cream-300 group-hover:border-gold-300 group-hover:shadow-xl group-hover:shadow-gold-400/20 group-hover:-translate-y-1 transition-all duration-500 w-full flex-1" x-tilt.4>
                    <h4 class="font-serif font-bold text-dark-800 text-base mb-2">{{ $paso->titulo }}</h4>
                    <p class="text-dark-500 text-sm leading-relaxed">{{ $paso->descripcion }}</p>
                </div>
            </div>
            @endforeach
        </div>
        </div>
        <div class="mt-16 text-center" x-reveal.scale>
            <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text=Quiero%20iniciar%20mi%20trámite" target="_blank" rel="noopener noreferrer" class="btn-gold !px-8 !py-4 text-base" x-magnetic>
                Iniciar mi trámite ahora
            </a>
        </div>
    </div>
</section>

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "HowTo",
    "name": "Cómo gestionar tu crédito hipotecario INFONAVIT o FOVISSSTE",
    "description": "Proceso paso a paso para obtener tu crédito hipotecario con asesoría especializada en Consultoría Inmobiliaria.",
    "step": [
        @foreach($procesos as $index => $paso)
        {
            "@@type": "HowToStep",
            "position": {{ $paso->numero }},
            "name": "{{ addslashes($paso->titulo) }}",
            "text": "{{ addslashes($paso->descripcion) }}"
        }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ]
}
</script>
