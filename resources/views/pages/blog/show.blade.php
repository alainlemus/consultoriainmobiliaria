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
        <div class="richtext richtext--dark">
            {!! $post->contenido !!}
        </div>

        {{-- Compartir en redes sociales --}}
        @php
            $shareUrl = urlencode(route('blog.show', $post->slug));
            $shareTitle = urlencode($post->titulo);
            $shareDescription = urlencode($post->resumen ?? Str::limit(strip_tags($post->contenido ?? ''), 155));
        @endphp
        <div class="mt-10 mb-4">
            <p class="text-xs uppercase tracking-wider text-white mb-4 font-semibold">Compartir artículo</p>
            <div class="flex flex-wrap gap-3">

                {{-- Facebook --}}
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-sm text-sm font-semibold transition-all"
                   style="background-color:#1877F2; color:#fff;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.413c0-3.025 1.791-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
                    </svg>
                    Facebook
                </a>

                {{-- X (Twitter) --}}
                <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-sm text-sm font-semibold transition-all"
                   style="background-color:#000; color:#fff;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.911-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    X
                </a>

                {{-- LinkedIn --}}
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-sm text-sm font-semibold transition-all"
                   style="background-color:#0A66C2; color:#fff;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                    LinkedIn
                </a>

                {{-- WhatsApp --}}
                <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-sm text-sm font-semibold transition-all"
                   style="background-color:#25D366; color:#fff;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp
                </a>

            </div>
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
