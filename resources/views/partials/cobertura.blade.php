<section id="cobertura" class="py-24 bg-cream-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <p class="section-subtitle mb-3">Dónde operamos</p>
            <h2 class="section-title mb-4">Nuestra <span class="text-crimson-600">Cobertura</span></h2>
            <div class="gold-divider"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            @foreach($coberturas as $e)
            <div class="bg-white rounded-sm p-8 text-center border border-cream-300 hover:border-gold-400 hover:shadow-xl transition-all duration-300 group">
                <div class="w-20 h-20 mx-auto mb-4 bg-gold-500/10 rounded-full flex items-center justify-center group-hover:bg-gold-500/20 transition-colors">
                    <svg class="w-10 h-10 text-gold-500" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-2.013 3.5-4.6 3.5-7.827a8 8 0 10-16 0c0 3.227 1.556 5.814 3.5 7.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h3 class="font-serif text-2xl font-bold text-dark-800 mb-2">{{ $e->nombre }}</h3>
                <div class="w-8 h-0.5 bg-gold-400 mx-auto mb-3"></div>
                <p class="text-dark-500 text-sm leading-relaxed mb-4">{{ $e->descripcion }}</p>
                <div class="bg-cream-50 rounded-sm p-3 text-xs text-dark-500">{{ $e->detalle }}</div>
            </div>
            @endforeach
        </div>
        <p class="text-center text-dark-500 text-sm mt-10">¿No ves tu estado? Contáctanos, es posible que podamos atenderte igualmente.</p>
    </div>
</section>
