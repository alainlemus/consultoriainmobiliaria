@extends('layouts.app')

@section('seo_title', $post->titulo . ' — Blog Inmobiliario')
@section('seo_description', $post->resumen ?? Str::limit(strip_tags($post->contenido ?? ''), 155))
@section('og_title', $post->titulo)
@section('og_description', $post->resumen ?? Str::limit(strip_tags($post->contenido ?? ''), 155))
@section('og_type', 'article')
@section('og_url', route('blog.show', $post->slug))
@section('og_image', $post->imagen ? asset('storage/' . $post->imagen) : (setting('seo_og_imagen') ? asset('storage/' . setting('seo_og_imagen')) : ''))
@section('canonical', route('blog.show', $post->slug))

@push('jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ $post->titulo }}",
    "description": "{{ $post->resumen ?? Str::limit(strip_tags($post->contenido ?? ''), 155) }}",
    "url": "{{ route('blog.show', $post->slug) }}",
    "datePublished": "{{ ($post->published_at ?? $post->created_at)->toIso8601String() }}",
    "dateModified": "{{ $post->updated_at->toIso8601String() }}",
    @if($post->imagen)
    "image": "{{ asset('storage/' . $post->imagen) }}",
    @endif
    "author": {
        "@@type": "Organization",
        "name": "{{ setting('site_name', 'Consultoría Inmobiliaria') }}",
        "url": "{{ config('app.url') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "{{ setting('site_name', 'Consultoría Inmobiliaria') }}",
        "url": "{{ config('app.url') }}",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ setting('logo') ? asset('storage/' . setting('logo')) : asset('favicon.ico') }}"
        }
    },
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": "{{ route('blog.show', $post->slug) }}"
    }
}
</script>

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Inicio", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Blog", "item": "{{ route('blog.index') }}" },
        { "@@type": "ListItem", "position": 3, "name": "{{ $post->titulo }}", "item": "{{ route('blog.show', $post->slug) }}" }
    ]
}
</script>
@endpush

@section('content')

{{-- Hero header (dark, igual que blog index) --}}
<div class="bg-dark-900" style="padding-top: 120px; padding-bottom: 56px;">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="text-xs uppercase tracking-wider mb-6 flex items-center gap-2">
            <a href="{{ route('home') }}" class="text-white hover:text-gold-400 transition-colors">Inicio</a>
            <span class="text-gold-400">›</span>
            <a href="{{ route('blog.index') }}" class="text-white hover:text-gold-400 transition-colors">Blog</a>
            <span class="text-gold-400">›</span>
            <span class="text-gold-400">{{ Str::limit($post->titulo, 50) }}</span>
        </nav>

        @if($post->categoria)
            <span class="inline-block text-xs font-semibold text-gold-400 bg-gold-400/10 rounded-sm px-3 py-1 mb-4 uppercase tracking-wider">
                {{ $post->categoria }}
            </span>
        @endif

        <h1 class="font-serif text-3xl md:text-4xl font-bold text-white mb-4 leading-tight">
            {{ $post->titulo }}
        </h1>

        <div class="flex items-center gap-3">
            <div class="w-8 h-0.5 bg-gold-400"></div>
            <p class="text-cream-300 text-xs uppercase tracking-wider">
                {{ $post->published_at?->isoFormat('D [de] MMMM [de] YYYY') ?? $post->created_at->isoFormat('D [de] MMMM [de] YYYY') }}
            </p>
        </div>

    </div>
</div>

{{-- Contenido del artículo --}}
<article class="bg-dark-900 py-14" style="padding-bottom: 100px;">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($post->imagen)
            <div class="rounded-sm overflow-hidden mb-10 h-72">
                <img src="{{ Storage::url($post->imagen) }}"
                     alt="{{ $post->titulo }}"
                     loading="eager"
                     class="w-full h-full object-cover">
            </div>
        @endif

        {{-- Contenido --}}
        <div class="prose prose-lg prose-invert max-w-none text-cream-200 leading-relaxed
                    prose-headings:font-serif prose-headings:text-white
                    prose-a:text-gold-400 prose-a:no-underline hover:prose-a:underline
                    prose-strong:text-white
                    prose-li:text-cream-200
                    prose-hr:border-dark-600">
            {!! $post->contenido !!}
        </div>

        {{-- Separador --}}
        <div class="h-0.5 bg-gradient-to-r from-transparent via-gold-400 to-transparent my-12"></div>

        {{-- Relacionados --}}
        @if($relacionados->count())
            <div>
                <h3 class="font-serif text-xl font-bold text-white mb-6">También te puede interesar</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($relacionados as $rel)
                        <a href="{{ route('blog.show', $rel->slug) }}"
                           class="bg-dark-800 border border-dark-600 rounded-sm p-4 hover:border-gold-400 hover:shadow-md transition-all group">
                            @if($rel->categoria)
                                <span class="text-xs text-gold-400 uppercase tracking-wider font-semibold">{{ $rel->categoria }}</span>
                            @endif
                            <p class="text-sm font-bold text-cream-200 group-hover:text-gold-400 transition-colors line-clamp-2 mt-1">
                                {{ $rel->titulo }}
                            </p>
                            <p class="text-xs text-cream-300 mt-2">
                                {{ $rel->published_at?->format('d M Y') }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-10 mb-20">
            <a href="{{ route('blog.index') }}" class="btn-gold">
                ← Ver todos los artículos
            </a>
        </div>

    </div>
</article>
@endsection
