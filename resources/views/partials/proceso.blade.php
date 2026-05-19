<section id="proceso" class="py-24 bg-cream-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <p class="section-subtitle mb-3">Cómo funciona</p>
            <h2 class="section-title mb-4">El Proceso <span class="text-crimson-600">Paso a Paso</span></h2>
            <div class="gold-divider"></div>
            <p class="text-dark-600 mt-4 max-w-2xl mx-auto">Te acompañamos desde el primer contacto hasta que tengas las llaves en mano.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($procesos as $paso)
            <div class="flex flex-col items-center text-center group">
                <div class="w-16 h-16 bg-white border-2 border-gold-400 rounded-full flex items-center justify-center mb-4 shadow-md group-hover:bg-crimson-600 group-hover:border-crimson-600 transition-all duration-300 z-10">
                    <span class="font-serif font-bold text-gold-500 text-lg group-hover:text-white transition-colors">{{ $paso->numero }}</span>
                </div>
                <div class="bg-white rounded-sm p-4 shadow-sm border border-cream-300 group-hover:border-gold-300 transition-colors w-full flex-1">
                    <h4 class="font-serif font-semibold text-dark-800 text-sm mb-1">{{ $paso->titulo }}</h4>
                    <p class="text-dark-500 text-xs leading-relaxed">{{ $paso->descripcion }}</p>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-12 text-center">
            <a href="https://wa.me/527711910395?text=Quiero%20iniciar%20mi%20trámite" target="_blank" rel="noopener noreferrer" class="btn-gold">
                Iniciar mi trámite ahora
            </a>
        </div>
    </div>
</section>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "HowTo",
    "name": "Cómo gestionar tu crédito hipotecario INFONAVIT o FOVISSSTE",
    "description": "Proceso paso a paso para obtener tu crédito hipotecario con asesoría especializada en Consultoría Inmobiliaria.",
    "step": [
        @foreach($procesos as $index => $paso)
        {
            "@type": "HowToStep",
            "position": {{ $paso->numero }},
            "name": "{{ addslashes($paso->titulo) }}",
            "text": "{{ addslashes($paso->descripcion) }}"
        }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ]
}
</script>
