<section id="testimonios" class="py-24 bg-dark-800 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gold-500/5 via-transparent to-crimson-700/5"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16" x-reveal>
            <p class="section-subtitle text-gold-400 mb-3">Experiencias reales</p>
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-white mb-4">Lo que dicen <span class="text-gold-400">nuestros clientes</span></h2>
            <div class="gold-divider"></div>
        </div>

        {{-- Tarjetas de testimonios (carrusel) --}}
        @php
            $testimoniosData = (isset($testimonios) && $testimonios->count() > 0)
                ? $testimonios->map(fn($t) => ['nombre' => $t->nombre, 'ciudad' => $t->ciudad, 'texto' => $t->testimonio])
                : collect([
                    ['nombre' => 'María González', 'ciudad' => 'Pachuca, Hidalgo', 'texto' => 'Gracias a Consultoría Inmobiliaria pude ejercer mi crédito INFONAVIT. Me ayudaron en todo el proceso, desde la precalificación hasta la firma de escrituras. ¡100% recomendados!'],
                    ['nombre' => 'José Hernández', 'ciudad' => 'Huejutla de Reyes, Hgo.', 'texto' => 'Estaba perdido con los requisitos de mi crédito FOVISSSTE. El equipo me explicó todo con paciencia y me consiguieron el avalúo rápidamente. Excelente servicio.'],
                    ['nombre' => 'Ana Laura Martínez', 'ciudad' => 'Veracruz, Ver.', 'texto' => 'El proceso fue muy transparente. En todo momento supe qué estaba pasando con mi crédito. Ahora tengo mi casa escriturada. ¡Muchas gracias!'],
                ]);
        @endphp

        <div
            x-data="carousel({ total: {{ $testimoniosData->count() }}, visibleDesktop: 3, visibleTablet: 1, visibleMobile: 1 })"
            class="relative"
            @mouseenter="clearInterval(autoplay)"
            @mouseleave="startAutoplay()"
            x-reveal
        >
            <div class="overflow-hidden">
                <div class="flex transition-transform duration-500 ease-in-out" :style="`transform: ${offset()}`">
                    @foreach($testimoniosData as $t)
                    <div class="flex-shrink-0 px-2" :style="`width: calc(100% / ${visibles})`">
                        <div class="bg-dark-700 border border-dark-600 hover:border-gold-500/40 rounded-sm p-6 transition-all duration-300 relative overflow-hidden h-full">
                            <span class="absolute top-3 right-4 text-6xl font-serif text-crimson-600/30 leading-none select-none">"</span>
                            <div class="flex gap-1 mb-4">@for($i=0;$i<5;$i++)<svg class="w-4 h-4 text-gold-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>@endfor</div>
                            <p class="text-cream-300 text-sm leading-relaxed mb-5 italic">"{{ $t['texto'] }}"</p>
                            <div class="flex items-center gap-3 border-t border-dark-600 pt-4">
                                <div class="w-10 h-10 bg-gold-500/20 border border-gold-500/40 rounded-full flex items-center justify-center text-gold-400 font-serif font-bold text-sm">{{ strtoupper(substr($t['nombre'],0,1)) }}</div>
                                <div>
                                    <div class="text-white font-semibold text-sm">{{ $t['nombre'] }}</div>
                                    <div class="text-gold-300 text-xs">{{ $t['ciudad'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Botones prev/next --}}
            <button
                @click="prev()"
                class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-3 w-10 h-10 bg-dark-900 border border-gold-500/40 hover:border-gold-400 hover:bg-gold-400 text-gold-400 hover:text-dark-900 rounded-full flex items-center justify-center transition-all duration-200 shadow-lg z-10"
                aria-label="Anterior"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button
                @click="next()"
                class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-3 w-10 h-10 bg-dark-900 border border-gold-500/40 hover:border-gold-400 hover:bg-gold-400 text-gold-400 hover:text-dark-900 rounded-full flex items-center justify-center transition-all duration-200 shadow-lg z-10"
                aria-label="Siguiente"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>

            {{-- Dots --}}
            <div class="flex justify-center gap-2 mt-6" x-show="total > visibles">
                <template x-for="i in (maxIndex() + 1)" :key="i">
                    <button
                        type="button"
                        @click="goTo(i - 1)"
                        :class="current === i - 1 ? 'bg-gold-400 w-6' : 'bg-dark-600 hover:bg-gold-500/50 w-2'"
                        class="h-2 rounded-full transition-all duration-300"
                        :aria-label="'Ir al testimonio ' + i + ' de ' + (maxIndex() + 1)"
                        :aria-current="current === i - 1 ? 'true' : 'false'"
                    ></button>
                </template>
            </div>
        </div>

        {{-- Slider de fotos de clientes --}}
        @if(isset($fotosClientes) && $fotosClientes->count() > 0)
        <div class="mt-20">
            <div class="text-center mb-10">
                <p class="section-subtitle text-gold-400 mb-2">Familias con crédito aprobado</p>
                <h3 class="font-serif text-2xl font-bold text-white">Nuestros <span class="text-gold-400">clientes felices</span></h3>
                <div class="gold-divider"></div>
            </div>

            {{-- Slider con Alpine.js --}}
            <div
                x-data="carousel({ total: {{ $fotosClientes->count() }} })"
                class="relative"
                @mouseenter="clearInterval(autoplay)"
                @mouseleave="startAutoplay()"
            >
                {{-- Track --}}
                <div class="overflow-hidden rounded-sm">
                    <div
                        class="flex transition-transform duration-500 ease-in-out"
                        :style="`transform: ${offset()}`"
                    >
                        @foreach($fotosClientes as $foto)
                        <div
                            class="foto-cliente-slide flex-shrink-0 px-2"
                            :style="`width: calc(100% / ${visibles})`"
                        >
                            <div class="relative group overflow-hidden rounded-sm aspect-square bg-dark-700">
                                <img
                                    src="{{ Storage::url($foto->foto) }}"
                                    alt="{{ $foto->nombre ?? 'Cliente' }}"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    loading="lazy"
                                >
                                {{-- Overlay con info --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-dark-900/90 via-dark-900/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
                                    @if($foto->nombre)
                                    <p class="text-white font-semibold text-sm leading-tight">{{ $foto->nombre }}</p>
                                    @endif
                                    @if($foto->tipo_credito)
                                    <span class="inline-block mt-1 text-xs bg-gold-400 text-dark-900 font-bold px-2 py-0.5 self-start">{{ $foto->tipo_credito }}</span>
                                    @endif
                                    @if($foto->ciudad)
                                    <p class="text-cream-300 text-xs mt-1">{{ $foto->ciudad }}</p>
                                    @endif
                                </div>
                                {{-- Badge siempre visible --}}
                                @if($foto->tipo_credito)
                                <div class="absolute top-2 left-2 bg-crimson-600 text-white text-xs font-bold px-2 py-0.5 opacity-90">
                                    {{ $foto->tipo_credito }}
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Botones prev/next --}}
                <button
                    @click="prev()"
                    class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-3 w-10 h-10 bg-dark-900 border border-gold-500/40 hover:border-gold-400 hover:bg-gold-400 text-gold-400 hover:text-dark-900 rounded-full flex items-center justify-center transition-all duration-200 shadow-lg z-10"
                    aria-label="Anterior"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button
                    @click="next()"
                    class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-3 w-10 h-10 bg-dark-900 border border-gold-500/40 hover:border-gold-400 hover:bg-gold-400 text-gold-400 hover:text-dark-900 rounded-full flex items-center justify-center transition-all duration-200 shadow-lg z-10"
                    aria-label="Siguiente"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>

                {{-- Dots --}}
                <div class="flex justify-center gap-2 mt-6" x-show="total > visibles">
                    <template x-for="i in (maxIndex() + 1)" :key="i">
                        <button
                            type="button"
                            @click="goTo(i - 1)"
                            :class="current === i - 1 ? 'bg-gold-400 w-6' : 'bg-dark-600 hover:bg-gold-500/50 w-2'"
                            class="h-2 rounded-full transition-all duration-300"
                            :aria-label="'Ir al testimonio ' + i + ' de ' + (maxIndex() + 1)"
                            :aria-current="current === i - 1 ? 'true' : 'false'"
                        ></button>
                    </template>
                </div>
            </div>
        </div>
        @endif

    </div>
</section>
