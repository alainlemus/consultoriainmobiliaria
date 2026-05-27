<section class="relative min-h-screen flex items-center overflow-hidden">
    @php
        $heroImage = setting('hero_image')
            ? asset('storage/' . setting('hero_image'))
            : 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1600&q=80';
    @endphp
    <div class="absolute inset-0 z-0 bg-dark-800" style="background-image:url('{{ $heroImage }}');background-size:cover;background-position:center;"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-dark-900 via-dark-900/85 to-dark-800/50 z-10"></div>

    <div class="relative z-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 lg:py-44">
        <div class="max-w-3xl">
            <p class="section-subtitle text-gold-400 mb-4">Consultoría Inmobiliaria en Huejutla de Reyes, Hidalgo</p>
            {{-- Badge rojo ladrillo — resalta INFONAVIT/FOVISSSTE como servicio principal --}}
            <div class="inline-flex items-center gap-2 bg-crimson-600 text-white text-xs uppercase tracking-widest px-3 py-1 mb-5 font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-gold-400 inline-block"></span>
                Especialistas en Crédito Hipotecario INFONAVIT y FOVISSSTE
            </div>
            <h1 class="font-serif text-5xl sm:text-6xl lg:text-7xl font-bold text-white leading-tight mb-6">
                Tu <span class="text-gold-400">Patrimonio,</span><br>
                Nuestra <span class="text-gold-400">Prioridad</span>
            </h1>
            <div class="w-20 h-0.5 bg-gold-400 mb-6"></div>
            <p class="text-cream-200 text-lg sm:text-xl leading-relaxed mb-2 max-w-xl">
                Tramitamos tu crédito <strong class="text-gold-400">INFONAVIT</strong> o <strong class="text-gold-400">FOVISSSTE</strong> en Hidalgo, Veracruz y San Luis Potosí. Avalúos, escrituras y gestoría inmobiliaria sin costo inicial.
            </p>
            <p class="font-script text-gold-400 text-xl mb-10">Confianza • Transparencia • Resultados</p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text=Hola,%20quiero%20información" target="_blank" class="btn-gold">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Hablar con un asesor
                </a>
                <a href="#servicios" class="btn-dark">Ver servicios</a>
            </div>
        </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 z-20 bg-dark-900/90 backdrop-blur-sm border-t border-gold-500/20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-3 divide-x divide-gold-500/20">
                <div class="py-5 text-center"><div class="text-gold-400 font-serif font-bold text-2xl sm:text-3xl">+500</div><div class="text-cream-300 text-xs uppercase tracking-wider mt-1">Familias asesoradas</div></div>
                <div class="py-5 text-center"><div class="text-gold-400 font-serif font-bold text-2xl sm:text-3xl">3</div><div class="text-cream-300 text-xs uppercase tracking-wider mt-1">Estados</div></div>
                <div class="py-5 text-center"><div class="text-gold-400 font-serif font-bold text-2xl sm:text-3xl">100%</div><div class="text-cream-300 text-xs uppercase tracking-wider mt-1">Trámite financiado</div></div>
            </div>
        </div>
    </div>
</section>
