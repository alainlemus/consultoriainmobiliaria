@extends('layouts.app')

@section('title', $post->titulo . ' — ConsulTips')

@section('content')
<article class="py-20 bg-cream-50 min-h-screen">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="text-xs text-dark-400 uppercase tracking-wider mb-8 flex items-center gap-2">
            <a href="{{ route('home') }}" class="hover:text-gold-500 transition-colors">Inicio</a>
            <span class="text-gold-400">›</span>
            <a href="{{ route('blog.index') }}" class="hover:text-gold-500 transition-colors">ConsulTips</a>
            <span class="text-gold-400">›</span>
            <span class="text-dark-800">{{ Str::limit($post->titulo, 40) }}</span>
        </nav>

        @if($post->categoria)
            <span class="inline-block text-xs font-semibold text-crimson-500 bg-crimson-500/10 rounded-sm px-3 py-1 mb-4 uppercase tracking-wider">
                {{ $post->categoria }}
            </span>
        @endif

        <h1 class="font-serif text-3xl md:text-4xl font-bold text-dark-800 mb-4 leading-tight">
            {{ $post->titulo }}
        </h1>

        <div class="flex items-center gap-3 mb-8">
            <div class="w-8 h-0.5 bg-gold-400"></div>
            <p class="text-dark-400 text-xs uppercase tracking-wider">
                {{ $post->published_at?->isoFormat('D [de] MMMM [de] YYYY') ?? $post->created_at->isoFormat('D [de] MMMM [de] YYYY') }}
            </p>
        </div>

        @if($post->imagen)
            <div class="rounded-sm overflow-hidden mb-10 h-72">
                <img src="{{ Storage::url($post->imagen) }}"
                     alt="{{ $post->titulo }}"
                     class="w-full h-full object-cover">
            </div>
        @endif

        {{-- Contenido --}}
        <div class="prose prose-lg max-w-none text-dark-700 leading-relaxed
                    prose-headings:font-serif prose-headings:text-dark-800
                    prose-a:text-gold-500 prose-a:no-underline hover:prose-a:underline
                    prose-strong:text-dark-800">
            {!! $post->contenido !!}
        </div>

        {{-- Separador --}}
        <div class="h-0.5 bg-gradient-to-r from-transparent via-gold-400 to-transparent my-12"></div>

        {{-- Relacionados --}}
        @if($relacionados->count())
            <div>
                <h3 class="font-serif text-xl font-bold text-dark-800 mb-6">También te puede interesar</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($relacionados as $rel)
                        <a href="{{ route('blog.show', $rel->slug) }}"
                           class="bg-white border border-cream-200 rounded-sm p-4 hover:border-gold-400 hover:shadow-md transition-all group">
                            @if($rel->categoria)
                                <span class="text-xs text-gold-500 uppercase tracking-wider font-semibold">{{ $rel->categoria }}</span>
                            @endif
                            <p class="text-sm font-bold text-dark-800 group-hover:text-gold-500 transition-colors line-clamp-2 mt-1">
                                {{ $rel->titulo }}
                            </p>
                            <p class="text-xs text-dark-400 mt-2">
                                {{ $rel->published_at?->format('d M Y') }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-10">
            <a href="{{ route('blog.index') }}" class="btn-dark">
                ← Ver todos los artículos
            </a>
        </div>

    </div>
</article>
@endsection
