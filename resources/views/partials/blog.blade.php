{{-- Blog / Blog preview --}}
<section id="blog" class="py-20 bg-cream-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14" x-reveal>
            <p class="section-subtitle text-gold-400 mb-3">Blog</p>
            <h2 class="section-title mb-4">Consejos y Noticias <span class="text-gold-400">Inmobiliarias</span></h2>
            <div class="gold-divider"></div>
            <p class="text-dark-400 max-w-xl mx-auto mt-4 text-sm">
                Información útil para tomar las mejores decisiones sobre tu patrimonio.
            </p>
        </div>

        @if(isset($posts) && $posts->count())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($posts as $post)
                    <article class="bg-white border border-cream-200 rounded-sm overflow-hidden group hover:shadow-lg hover:border-gold-400 transition-all duration-300" x-reveal.delay.{{ ($loop->index % 3) * 100 }}>
                        @if($post->imagen)
                            <div class="overflow-hidden h-48">
                             <img src="{{ Storage::url($post->imagen) }}"
                                      alt="{{ $post->titulo }}"
                                      loading="lazy"
                                      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                        @else
                            <div class="h-48 bg-gradient-to-br from-gold-100 to-cream-200 flex items-center justify-center">
                                <svg class="w-14 h-14 text-gold-400/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="p-6">
                            @if($post->categoria)
                                <span class="inline-block text-xs font-semibold text-crimson-500 bg-crimson-500/10 rounded-sm px-3 py-1 mb-3 uppercase tracking-wider">
                                    {{ $post->categoria }}
                                </span>
                            @endif

                            <h3 class="font-serif text-lg font-bold text-dark-800 mb-2 line-clamp-2 group-hover:text-gold-500 transition-colors">
                                {{ $post->titulo }}
                            </h3>

                            <p class="text-dark-400 text-sm line-clamp-3 mb-4">
                                {{ $post->resumen ?? Str::limit(strip_tags($post->contenido), 120) }}
                            </p>

                            <div class="flex items-center justify-between border-t border-cream-200 pt-4">
                                <span class="text-xs text-dark-400">
                                    {{ $post->published_at?->format('d M Y') ?? $post->created_at->format('d M Y') }}
                                </span>
                                <a href="{{ route('blog.show', $post->slug) }}"
                                   class="text-sm font-semibold text-gold-500 hover:text-dark-800 transition-colors inline-flex items-center gap-1 uppercase tracking-wider">
                                    Leer más
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('blog.index') }}" class="btn-dark">
                    Ver todos los artículos
                </a>
            </div>

        @else
            {{-- Fallback con artículos de ejemplo --}}
            @php
            $ejemplos = [
                [
                    'categoria' => 'INFONAVIT',
                    'titulo'    => '¿Cómo usar tu crédito INFONAVIT para comprar casa en Huejutla?',
                    'resumen'   => 'Conoce los requisitos, montos disponibles y el proceso paso a paso para ejercer tu crédito INFONAVIT en la región Huasteca.',
                    'fecha'     => '10 Abr 2026',
                ],
                [
                    'categoria' => 'Avalúos',
                    'titulo'    => 'Avalúo comercial vs. fiscal: ¿cuál necesitas y para qué sirve?',
                    'resumen'   => 'Descubre las diferencias entre los tipos de avalúo, cuándo es obligatorio cada uno y cómo protege tu patrimonio.',
                    'fecha'     => '02 Abr 2026',
                ],
                [
                    'categoria' => 'Escrituras',
                    'titulo'    => '5 errores comunes al escriturar una propiedad (y cómo evitarlos)',
                    'resumen'   => 'Antes de firmar ante notario, conoce estos puntos clave para que tu escrituración sea segura y sin sorpresas.',
                    'fecha'     => '25 Mar 2026',
                ],
            ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($ejemplos as $item)
                    <article class="bg-white border border-cream-200 rounded-sm overflow-hidden group hover:shadow-lg hover:border-gold-400 transition-all duration-300" x-reveal.delay.{{ $loop->index * 100 }}>
                        <div class="h-48 bg-gradient-to-br from-gold-100 to-cream-200 flex items-center justify-center">
                            <svg class="w-14 h-14 text-gold-400/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="p-6">
                            <span class="inline-block text-xs font-semibold text-crimson-500 bg-crimson-500/10 rounded-sm px-3 py-1 mb-3 uppercase tracking-wider">
                                {{ $item['categoria'] }}
                            </span>
                            <h3 class="font-serif text-lg font-bold text-dark-800 mb-2 line-clamp-2 group-hover:text-gold-500 transition-colors">
                                {{ $item['titulo'] }}
                            </h3>
                            <p class="text-dark-400 text-sm line-clamp-3 mb-4">
                                {{ $item['resumen'] }}
                            </p>
                            <div class="flex items-center justify-between border-t border-cream-200 pt-4">
                                <span class="text-xs text-dark-400">{{ $item['fecha'] }}</span>
                                <span class="text-xs text-gold-500 uppercase tracking-wider font-semibold">Próximamente</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

    </div>
</section>
