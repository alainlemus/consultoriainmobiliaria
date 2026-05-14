@extends('layouts.app')

@section('title', 'Blog — Blog Inmobiliario')

@section('content')
<section class="bg-dark-900 min-h-screen" style="padding-top: 100px; padding-bottom: 80px;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <p class="section-subtitle text-gold-400 mb-3">Blog</p>
            <h1 class="section-title text-white mb-4">Blog <span class="text-gold-400">Inmobiliario</span></h1>
            <div class="gold-divider"></div>
            <p class="text-cream-300 max-w-xl mx-auto mt-4 text-sm">
                Consejos, noticias y guías para tomar las mejores decisiones sobre tu patrimonio.
            </p>
        </div>

        {{-- Filtro por categoría --}}
        @if(isset($categorias) && $categorias->count())
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            <a href="{{ route('blog.index') }}"
               class="px-4 py-2 text-xs uppercase tracking-wider font-semibold transition-colors rounded-sm
                                             {{ !request('categoria') ? 'bg-gold-500 text-dark-900' : 'bg-dark-800 border border-dark-600 text-cream-300 hover:border-gold-400 hover:text-gold-400' }}">
                Todos
            </a>
            @foreach($categorias as $cat)
            <a href="{{ route('blog.index', ['categoria' => $cat]) }}"
               class="px-4 py-2 text-xs uppercase tracking-wider font-semibold transition-colors rounded-sm
                                             {{ request('categoria') === $cat ? 'bg-gold-500 text-dark-900' : 'bg-dark-800 border border-dark-600 text-cream-300 hover:border-gold-400 hover:text-gold-400' }}">
                {{ $cat }}
            </a>
            @endforeach
        </div>
        @endif

        @if($posts->count())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($posts as $post)
                <article class="bg-dark-800 border border-dark-700 rounded-sm overflow-hidden group hover:shadow-lg hover:border-gold-500/50 transition-all duration-300">
                    @if($post->imagen)
                        <div class="overflow-hidden h-48">
                            <img src="{{ Storage::url($post->imagen) }}"
                                 alt="{{ $post->titulo }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                    @else
                        <div class="h-48 bg-gradient-to-br from-dark-700 to-dark-800 flex items-center justify-center">
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
                        <h2 class="font-serif text-lg font-bold text-white mb-2 line-clamp-2 group-hover:text-gold-400 transition-colors">
                            {{ $post->titulo }}
                        </h2>
                        <p class="text-cream-300 text-sm line-clamp-3 mb-4">
                            {{ $post->resumen ?? Str::limit(strip_tags($post->contenido), 120) }}
                        </p>
                        <div class="flex items-center justify-between border-t border-dark-600 pt-4">
                            <span class="text-xs text-cream-400">
                                {{ $post->published_at?->format('d M Y') ?? $post->created_at->format('d M Y') }}
                            </span>
                            <a href="{{ route('blog.show', $post->slug) }}"
                               class="text-xs font-semibold text-gold-400 hover:text-white transition-colors uppercase tracking-wider inline-flex items-center gap-1">
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

            <div class="mt-12 flex justify-center">
                {{ $posts->links() }}
            </div>
        @else
            <div class="text-center py-20 text-cream-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-gold-400/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-lg font-serif text-white mb-2">Próximamente</p>
                <p class="text-sm mb-6">Publicaremos nuestros primeros artículos de Blog muy pronto.</p>
                <a href="{{ route('home') }}" class="btn-dark">← Volver al inicio</a>
            </div>
        @endif

    </div>
</section>
@endsection
