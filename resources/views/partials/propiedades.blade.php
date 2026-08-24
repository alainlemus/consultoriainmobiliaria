{{-- Sección Propiedades en venta - últimas 4 destacadas --}}
@if(isset($propiedades) && $propiedades->count())
<section id="propiedades" class="py-20 bg-dark-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ filtro: 'todas' }">

        <div class="text-center mb-14" x-reveal>
            <p class="section-subtitle text-gold-400 mb-3">En venta</p>
            <h2 class="section-title text-white mb-4">Propiedades <span class="text-gold-400">Disponibles</span></h2>
            <div class="gold-divider"></div>
            <p class="text-cream-300 max-w-xl mx-auto mt-4 text-sm">
                Encuentra tu hogar ideal. Te acompañamos en cada paso del proceso de compra.
            </p>
        </div>

        {{-- Tabs de filtro (client-side, sobre las propiedades ya cargadas) --}}
        <div class="flex justify-center gap-2 mb-8">
            <button type="button" @click="filtro = 'todas'"
                :class="filtro === 'todas' ? 'bg-gold-400 text-dark-900' : 'bg-dark-800 text-cream-300 hover:text-gold-400'"
                class="px-4 py-2 text-xs uppercase tracking-wider font-semibold rounded-sm transition-colors duration-200">
                Todas
            </button>
            <button type="button" @click="filtro = 'infonavit'"
                :class="filtro === 'infonavit' ? 'bg-gold-400 text-dark-900' : 'bg-dark-800 text-cream-300 hover:text-gold-400'"
                class="px-4 py-2 text-xs uppercase tracking-wider font-semibold rounded-sm transition-colors duration-200">
                INFONAVIT
            </button>
            <button type="button" @click="filtro = 'fovissste'"
                :class="filtro === 'fovissste' ? 'bg-gold-400 text-dark-900' : 'bg-dark-800 text-cream-300 hover:text-gold-400'"
                class="px-4 py-2 text-xs uppercase tracking-wider font-semibold rounded-sm transition-colors duration-200">
                FOVISSSTE
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($propiedades as $propiedad)
            <a href="{{ route('propiedades.show', $propiedad->slug) }}"
               x-reveal.delay.{{ ($loop->index % 4) * 100 }}
               x-show="filtro === 'todas' || (filtro === 'infonavit' && {{ $propiedad->acepta_infonavit ? 'true' : 'false' }}) || (filtro === 'fovissste' && {{ $propiedad->acepta_fovissste ? 'true' : 'false' }})"
               x-transition
               class="group bg-dark-800 border border-dark-700 hover:border-gold-500/50 rounded-sm overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-gold-500/10 flex flex-col">

                {{-- Imagen --}}
                <div class="relative h-48 bg-dark-700 overflow-hidden">
                    @if($propiedad->imagen_principal)
                        <img src="{{ Storage::url($propiedad->imagen_principal) }}"
                             alt="{{ $propiedad->titulo }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                      d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                            </svg>
                        </div>
                    @endif

                    {{-- Badge tipo --}}
                    <span class="absolute top-3 left-3 bg-dark-900/80 text-gold-400 text-xs px-2 py-1 uppercase tracking-wider">
                        {{ $propiedad->tipo }}
                    </span>

                    {{-- Créditos --}}
                    @if($propiedad->acepta_infonavit || $propiedad->acepta_fovissste)
                    <div class="absolute top-3 right-3 flex gap-1">
                        @if($propiedad->acepta_infonavit)
                            <span class="bg-crimson-700 text-white text-[9px] px-1.5 py-0.5 font-bold uppercase">INF</span>
                        @endif
                        @if($propiedad->acepta_fovissste)
                            <span class="bg-crimson-700 text-white text-[9px] px-1.5 py-0.5 font-bold uppercase">FOV</span>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="p-4 flex flex-col flex-1">
                    <h3 class="text-white text-sm font-semibold leading-snug mb-2 group-hover:text-gold-400 transition-colors line-clamp-2">
                        {{ $propiedad->titulo }}
                    </h3>

                    <p class="text-cream-300/60 text-xs mb-3 flex items-center gap-1">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $propiedad->municipio }}, {{ $propiedad->estado }}
                    </p>

                    {{-- Características --}}
                    @if($propiedad->recamaras || $propiedad->banos || $propiedad->metros_construccion)
                    <div class="flex items-center gap-3 text-xs text-cream-300/50 mb-3">
                        @if($propiedad->recamaras)
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            {{ $propiedad->recamaras }} rec.
                        </span>
                        @endif
                        @if($propiedad->banos)
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                            </svg>
                            {{ $propiedad->banos }} baños
                        </span>
                        @endif
                        @if($propiedad->metros_construccion)
                        <span>{{ number_format($propiedad->metros_construccion, 0) }} m²</span>
                        @endif
                    </div>
                    @endif

                    <div class="mt-auto pt-3 border-t border-dark-700">
                        <p class="text-gold-400 font-bold text-base">{{ $propiedad->precio_formateado }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('propiedades.index') }}" class="btn-gold">
                Ver todas las propiedades
            </a>
        </div>

    </div>
</section>
@endif
