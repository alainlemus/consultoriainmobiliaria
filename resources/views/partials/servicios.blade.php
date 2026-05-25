<section id="servicios" class="py-24 bg-cream-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <p class="section-subtitle mb-3">Lo que ofrecemos</p>
            <h2 class="section-title mb-4">Nuestros <span class="text-crimson-600">Servicios</span></h2>
            <div class="gold-divider"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            @foreach($servicios as $s)
            <div class="card-service group flex flex-col">
                <div class="icon-gold group-hover:bg-gold-500 transition-colors">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $s->icon_path }}"/>
                    </svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-dark-800 mb-2">{{ $s->titulo }}</h3>
                <div class="w-8 h-0.5 bg-gold-400 mx-auto mb-3"></div>
                <ul class="text-xs text-dark-500 space-y-1 text-left mb-5 flex-1">
                    @foreach($s->items as $item)
                    <li class="flex items-center gap-2"><span class="text-gold-500">✦</span> {{ $item }}</li>
                    @endforeach
                </ul>
                <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text=Quiero%20información%20sobre%20{{ urlencode($s->wa_texto) }}"
                   target="_blank" rel="noopener noreferrer" class="btn-gold text-xs w-full justify-center">Consultar ahora</a>
            </div>
            @endforeach

            <div class="bg-dark-900 rounded-sm p-6 flex flex-col items-center justify-center text-center border border-gold-500/30 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-gold-500/10 to-transparent"></div>
                <div class="relative z-10">
                    <p class="text-gold-400 font-serif text-lg font-semibold mb-2">¿No sabes por dónde empezar?</p>
                    <p class="text-cream-300 text-sm mb-4">Un asesor experto te orienta sin costo.</p>
                    <p class="font-script text-gold-400 text-xl mb-5">¡Estamos para ayudarte!</p>
                    <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}" target="_blank" rel="noopener noreferrer" class="btn-gold text-xs">Escribir al WhatsApp</a>
                    <div class="mt-4 text-cream-300/60 text-xs">771 191 0395 · 771 781 8005</div>
                </div>
            </div>
        </div>
    </div>
</section>
