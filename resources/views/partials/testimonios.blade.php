<section id="testimonios" class="py-24 bg-dark-800 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gold-500/5 via-transparent to-crimson-700/5"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16">
            <p class="section-subtitle text-gold-400 mb-3">Experiencias reales</p>
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-white mb-4">Lo que dicen <span class="text-gold-400">nuestros clientes</span></h2>
            <div class="gold-divider"></div>
        </div>

        @if(isset($testimonios) && $testimonios->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($testimonios as $t)
            <div class="bg-dark-700 border border-dark-600 hover:border-gold-500/40 rounded-sm p-6 transition-all duration-300">
                <div class="flex gap-1 mb-4">@for($i=0;$i<5;$i++)<svg class="w-4 h-4 text-gold-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor</div>
                <p class="text-cream-300 text-sm leading-relaxed mb-5 italic">"{{ $t->testimonio }}"</p>
                <div class="flex items-center gap-3 border-t border-dark-600 pt-4">
                    <div class="w-10 h-10 bg-gold-500/20 border border-gold-500/40 rounded-full flex items-center justify-center text-gold-400 font-serif font-bold text-sm">{{ strtoupper(substr($t->nombre,0,1)) }}</div>
                    <div>
                        <div class="text-white font-semibold text-sm">{{ $t->nombre }}</div>
                        <div class="text-gold-500/70 text-xs">{{ $t->ciudad }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php $tDefault = [['nombre'=>'María González','ubicacion'=>'Pachuca, Hidalgo','texto'=>'Gracias a Consultoría Inmobiliaria pude ejercer mi crédito INFONAVIT. Me ayudaron en todo el proceso, desde la precalificación hasta la firma de escrituras. ¡100% recomendados!'],['nombre'=>'José Hernández','ubicacion'=>'Huejutla de Reyes, Hgo.','texto'=>'Estaba perdido con los requisitos de mi crédito FOVISSSTE. El equipo me explicó todo con paciencia y me consiguieron el avalúo rápidamente. Excelente servicio.'],['nombre'=>'Ana Laura Martínez','ubicacion'=>'Veracruz, Ver.','texto'=>'El proceso fue muy transparente. En todo momento supe qué estaba pasando con mi crédito. Ahora tengo mi casa escriturada. ¡Muchas gracias!']]; @endphp
            @foreach($tDefault as $t)
            <div class="bg-dark-700 border border-dark-600 hover:border-gold-500/40 rounded-sm p-6 transition-all duration-300">
                <div class="flex gap-1 mb-4">@for($i=0;$i<5;$i++)<svg class="w-4 h-4 text-gold-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor</div>
                <p class="text-cream-300 text-sm leading-relaxed mb-5 italic">"{{ $t['texto'] }}"</p>
                <div class="flex items-center gap-3 border-t border-dark-600 pt-4">
                    <div class="w-10 h-10 bg-gold-500/20 border border-gold-500/40 rounded-full flex items-center justify-center text-gold-400 font-serif font-bold text-sm">{{ strtoupper(substr($t['nombre'],0,1)) }}</div>
                    <div>
                        <div class="text-white font-semibold text-sm">{{ $t['nombre'] }}</div>
                        <div class="text-gold-500/70 text-xs">{{ $t['ubicacion'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
