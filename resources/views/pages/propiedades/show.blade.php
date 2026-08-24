@extends('layouts.app')

@php
    $ogDescripcion = collect([
        $propiedad->tipo,
        $propiedad->precio_formateado,
        $propiedad->recamaras ? $propiedad->recamaras . ' recámaras' : null,
        $propiedad->banos ? $propiedad->banos . ' baños' : null,
        collect([$propiedad->colonia, $propiedad->municipio, $propiedad->estado])->filter()->implode(', '),
    ])->filter()->implode(' · ');

    $ogImagen = $propiedad->imagen_principal
        ? asset('storage/' . $propiedad->imagen_principal)
        : (setting('seo_og_imagen') ? asset('storage/' . setting('seo_og_imagen')) : '');
@endphp

@section('seo_title', $propiedad->titulo . ' — ' . setting('site_name', 'Consultoría Inmobiliaria'))
@section('seo_description', $ogDescripcion)
@section('og_title', $propiedad->titulo . ' — ' . setting('site_name', 'Consultoría Inmobiliaria'))
@section('og_description', $ogDescripcion)
@section('og_type', 'website')
@section('og_url', route('propiedades.show', $propiedad->slug))
@section('og_image', $ogImagen)
@section('canonical', route('propiedades.show', $propiedad->slug))

@push('jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "RealEstateListing",
    "name": "{{ $propiedad->titulo }}",
    "description": "{{ addslashes($ogDescripcion) }}",
    "url": "{{ route('propiedades.show', $propiedad->slug) }}",
    @if($ogImagen)
    "image": "{{ $ogImagen }}",
    @endif
    @if($propiedad->precio)
    "offers": {
        "@@type": "Offer",
        "price": "{{ $propiedad->precio }}",
        "priceCurrency": "MXN",
        "availability": "{{ $propiedad->estatus === 'en_venta' ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut' }}"
    },
    @endif
    "address": {
        "@@type": "PostalAddress",
        "addressLocality": "{{ $propiedad->municipio }}",
        "addressRegion": "{{ $propiedad->estado }}",
        "addressCountry": "MX"
    },
    "numberOfRooms": {{ $propiedad->recamaras ?? 'null' }},
    "floorSize": {
        "@@type": "QuantitativeValue",
        "value": {{ $propiedad->metros_construccion ?? 'null' }},
        "unitCode": "MTK"
    }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        { "@@type": "ListItem", "position": 1, "name": "Inicio", "item": "{{ route('home') }}" },
        { "@@type": "ListItem", "position": 2, "name": "Propiedades", "item": "{{ route('propiedades.index') }}" },
        { "@@type": "ListItem", "position": 3, "name": "{{ $propiedad->titulo }}", "item": "{{ route('propiedades.show', $propiedad->slug) }}" }
    ]
}
</script>
@endpush

@section('content')
<section class="bg-dark-900 min-h-screen" style="padding-top: 100px;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        {{-- Breadcrumb --}}
        <nav class="text-xs text-cream-300/50 mb-8 flex items-center gap-2">
            <a href="{{ route('home') }}" class="hover:text-gold-400 transition-colors">Inicio</a>
            <span>/</span>
            <a href="{{ route('propiedades.index') }}" class="hover:text-gold-400 transition-colors">Propiedades</a>
            <span>/</span>
            <span class="text-cream-300/80">{{ $propiedad->titulo }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            {{-- Columna principal --}}
            <div class="lg:col-span-2">

                {{-- Galería --}}
                @if($propiedad->imagenes && count($propiedad->imagenes))
                <div x-data="{ activa: 0, imagenes: {{ json_encode($propiedad->imagenes) }}, lightboxOpen: false }" class="mb-8">
                    {{-- Imagen principal --}}
                    <div class="relative h-72 sm:h-96 mb-3">
                        <div class="absolute inset-0 bg-dark-800 rounded-sm overflow-hidden">
                            <template x-for="(img, i) in imagenes" :key="i">
                                <img :src="'/storage/' + img"
                                     :alt="'Imagen ' + (i+1)"
                                     x-show="activa === i"
                                     x-transition:enter="transition-opacity duration-300"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     class="absolute inset-0 w-full h-full object-cover">
                            </template>
                            {{-- Contador --}}
                            <span x-show="imagenes.length > 1"
                                  class="absolute bottom-3 right-3 bg-dark-900/70 text-cream-300 text-xs px-2 py-1 rounded">
                                <span x-text="activa + 1"></span> / <span x-text="imagenes.length"></span>
                            </span>
                        </div>
                        {{-- Flechas fuera del overflow-hidden --}}
                        <button @click="activa = (activa - 1 + imagenes.length) % imagenes.length"
                                :class="imagenes.length <= 1 ? 'hidden' : ''"
                                style="position:absolute; left:12px; top:50%; transform:translateY(-50%); z-index:10;"
                                class="w-10 h-10 bg-dark-900/80 hover:bg-gold-500 text-white rounded-full flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button @click="activa = (activa + 1) % imagenes.length"
                                :class="imagenes.length <= 1 ? 'hidden' : ''"
                                style="position:absolute; right:12px; top:50%; transform:translateY(-50%); z-index:10;"
                                class="w-10 h-10 bg-dark-900/80 hover:bg-gold-500 text-white rounded-full flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        {{-- Ver en pantalla completa --}}
                        <button @click="lightboxOpen = true"
                                style="position:absolute; top:12px; right:12px; z-index:10;"
                                class="w-10 h-10 bg-dark-900/80 hover:bg-gold-500 text-white rounded-full flex items-center justify-center transition-colors"
                                aria-label="Ver en pantalla completa">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            </svg>
                        </button>
                    </div>
                    {{-- Miniaturas --}}
                    <div x-show="imagenes.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                        <template x-for="(img, i) in imagenes" :key="i">
                            <button @click="activa = i"
                                    :style="activa === i ? 'border: 2px solid #C9A84C; opacity:1;' : 'border: 2px solid transparent; opacity:0.5;'"
                                    class="shrink-0 w-16 h-12 rounded-sm overflow-hidden transition-all hover:opacity-80">
                                <img :src="'/storage/' + img" class="w-full h-full object-cover">
                            </button>
                        </template>
                    </div>

                    {{-- Lightbox de pantalla completa --}}
                    <template x-teleport="body">
                        <div x-show="lightboxOpen"
                             x-transition.opacity
                             @keydown.escape.window="lightboxOpen = false"
                             x-effect="document.body.style.overflow = lightboxOpen ? 'hidden' : ''"
                             @click.self="lightboxOpen = false"
                             class="fixed inset-0 z-[100] bg-black/95 flex items-center justify-center p-4"
                             style="display: none;">

                            <button @click="lightboxOpen = false"
                                    style="position:absolute; top:16px; right:16px; z-index:10;"
                                    class="w-11 h-11 bg-dark-900/80 hover:bg-gold-500 text-white rounded-full flex items-center justify-center transition-colors"
                                    aria-label="Cerrar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <div class="relative w-full max-w-5xl h-[80vh]">
                                <template x-for="(img, i) in imagenes" :key="i">
                                    <img :src="'/storage/' + img"
                                         :alt="'Imagen ' + (i+1)"
                                         x-show="activa === i"
                                         x-transition:enter="transition-opacity duration-300"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         class="absolute inset-0 w-full h-full object-contain">
                                </template>
                            </div>

                            <button @click="activa = (activa - 1 + imagenes.length) % imagenes.length"
                                    :class="imagenes.length <= 1 ? 'hidden' : ''"
                                    style="position:absolute; left:16px; top:50%; transform:translateY(-50%); z-index:10;"
                                    class="w-12 h-12 bg-dark-900/80 hover:bg-gold-500 text-white rounded-full flex items-center justify-center transition-colors"
                                    aria-label="Anterior">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <button @click="activa = (activa + 1) % imagenes.length"
                                    :class="imagenes.length <= 1 ? 'hidden' : ''"
                                    style="position:absolute; right:16px; top:50%; transform:translateY(-50%); z-index:10;"
                                    class="w-12 h-12 bg-dark-900/80 hover:bg-gold-500 text-white rounded-full flex items-center justify-center transition-colors"
                                    aria-label="Siguiente">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>

                            <span x-show="imagenes.length > 1"
                                  style="position:absolute; bottom:16px; left:50%; transform:translateX(-50%);"
                                  class="bg-dark-900/70 text-cream-300 text-xs px-3 py-1 rounded">
                                <span x-text="activa + 1"></span> / <span x-text="imagenes.length"></span>
                            </span>
                        </div>
                    </template>
                </div>
                @else
                <div class="h-72 sm:h-96 bg-dark-800 rounded-sm flex items-center justify-center mb-8">
                    <svg class="w-16 h-16 text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                    </svg>
                </div>
                @endif

                {{-- Título --}}
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-dark-700 text-gold-400 text-xs px-4 py-1.5 uppercase tracking-wider">{{ $propiedad->tipo }}</span>
                        <span class="text-xs px-4 py-1.5 uppercase tracking-wider
                            {{ $propiedad->estatus === 'en_venta' ? 'bg-green-900/40 text-green-400' : ($propiedad->estatus === 'pausada' ? 'bg-yellow-900/40 text-yellow-400' : 'bg-red-900/40 text-red-400') }}">
                            {{ \App\Models\Propiedad::estatuses()[$propiedad->estatus] ?? $propiedad->estatus }}
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-serif font-bold text-white leading-snug">
                        {{ $propiedad->titulo }}
                    </h1>
                    <p class="text-cream-300/60 text-sm mt-2 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ collect([$propiedad->colonia, $propiedad->municipio, $propiedad->estado])->filter()->implode(', ') }}
                    </p>
                </div>

                {{-- Características --}}
                @if($propiedad->recamaras || $propiedad->banos || $propiedad->metros_construccion || $propiedad->metros_terreno)
                <div class="flex flex-row gap-3 mb-8 flex-wrap">
                    @if($propiedad->recamaras)
                    <div style="width:96px;min-width:96px;" class="bg-dark-800 border border-dark-700 rounded-sm p-3 text-center">
                        <svg class="w-5 h-5 text-gold-400 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12V7a1 1 0 011-1h16a1 1 0 011 1v5M3 12h18M3 12v5m18-5v5M3 17h18M7 12V9h4v3M13 12V9h4v3"/>
                        </svg>
                        <p class="text-white font-bold text-base leading-none">{{ $propiedad->recamaras }}</p>
                        <p class="text-cream-300/50 text-xs mt-1">Recámaras</p>
                    </div>
                    @endif
                    @if($propiedad->banos)
                    <div style="width:96px;min-width:96px;" class="bg-dark-800 border border-dark-700 rounded-sm p-3 text-center">
                        <svg class="w-5 h-5 text-gold-400 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h16v4a4 4 0 01-4 4H8a4 4 0 01-4-4v-4zm0 0V7a2 2 0 012-2h1a2 2 0 012 2v5"/>
                        </svg>
                        <p class="text-white font-bold text-base leading-none">{{ $propiedad->banos }}</p>
                        <p class="text-cream-300/50 text-xs mt-1">Baños</p>
                    </div>
                    @endif
                    @if($propiedad->metros_construccion)
                    <div style="width:96px;min-width:96px;" class="bg-dark-800 border border-dark-700 rounded-sm p-3 text-center">
                        <svg class="w-5 h-5 text-gold-400 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21l18-18M3 21h6m-6 0v-6m12-6h6m-6 0v6m6-6v6m-6 0h6"/>
                        </svg>
                        <p class="text-white font-bold text-base leading-none">{{ number_format($propiedad->metros_construccion, 0) }}</p>
                        <p class="text-cream-300/50 text-xs mt-1">m² const.</p>
                    </div>
                    @endif
                    @if($propiedad->metros_terreno)
                    <div style="width:96px;min-width:96px;" class="bg-dark-800 border border-dark-700 rounded-sm p-3 text-center">
                        <svg class="w-5 h-5 text-gold-400 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 13l4.553 2.276A1 1 0 0021 21.382V10.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 4"/>
                        </svg>
                        <p class="text-white font-bold text-base leading-none">{{ number_format($propiedad->metros_terreno, 0) }}</p>
                        <p class="text-cream-300/50 text-xs mt-1">m² terreno</p>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Descripción --}}
                @if($propiedad->descripcion)
                <div class="mb-8">
                    <h2 class="text-white font-semibold text-lg mb-3">Descripción</h2>
                    <p class="text-cream-300/80 text-sm leading-relaxed">{{ $propiedad->descripcion }}</p>
                </div>
                @endif

                {{-- Compartir en redes sociales --}}
                @php
                    $shareUrl = urlencode(route('propiedades.show', $propiedad->slug));
                    $shareTitle = urlencode($propiedad->titulo . ' — ' . setting('site_name', 'Consultoría Inmobiliaria'));
                @endphp
                <div class="mb-8">
                    <p class="text-xs uppercase tracking-wider text-white mb-4 font-semibold">Compartir propiedad</p>
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

                {{-- Dirección --}}
                @if($propiedad->direccion)
                <div class="bg-dark-800 border border-dark-700 rounded-sm p-4 mb-8">
                    <p class="text-gold-400 text-xs uppercase tracking-wider mb-1">Dirección de referencia</p>
                    <p class="text-cream-300 text-sm">{{ $propiedad->direccion }}</p>
                </div>
                @endif

                {{-- Mapa --}}
                @if($propiedad->mapa_iframe)
                <div class="mb-8">
                    <h2 class="text-white font-semibold text-lg mb-3">Ubicación</h2>
                    <div class="rounded-sm overflow-hidden border border-dark-700" style="height:300px;">
                        {!! $propiedad->mapa_iframe !!}
                    </div>
                </div>
                @elseif($propiedad->latitud && $propiedad->longitud)
                <div class="mb-8">
                    <h2 class="text-white font-semibold text-lg mb-3">Ubicación</h2>
                    <div class="rounded-sm overflow-hidden border border-dark-700" style="height:300px;">
                        <iframe
                            src="https://maps.google.com/maps?q={{ $propiedad->latitud }},{{ $propiedad->longitud }}&z=16&output=embed"
                            width="100%" height="300" style="border:0;" allowfullscreen loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                @endif

            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1">
                <div class="sticky top-28 flex flex-col gap-6">

                    {{-- Precio y CTA --}}
                    <div class="bg-dark-800 border border-gold-500/30 rounded-sm p-6">
                        <p class="text-cream-300/60 text-xs uppercase tracking-wider mb-1">Precio</p>
                        <p class="text-gold-400 text-3xl font-bold mb-1">{{ $propiedad->precio_formateado }}</p>
                        @if($propiedad->precio && $propiedad->metros_construccion)
                        <p class="text-cream-300/40 text-xs mb-5">
                            ${{ number_format($propiedad->precio / $propiedad->metros_construccion, 0) }} MXN / m²
                        </p>
                        @elseif($propiedad->precio && $propiedad->metros_terreno)
                        <p class="text-cream-300/40 text-xs mb-5">
                            ${{ number_format($propiedad->precio / $propiedad->metros_terreno, 0) }} MXN / m²
                        </p>
                        @else
                        <div class="mb-5"></div>
                        @endif

                        @if($propiedad->estatus === 'en_venta')
                            <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text={{ urlencode('Hola, me interesa la propiedad: ' . $propiedad->titulo . ' — ' . route('propiedades.show', $propiedad->slug)) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="btn-gold w-full text-center block mb-3">
                                Solicitar información
                            </a>
                            <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text={{ urlencode('Hola, quiero agendar una visita para: ' . $propiedad->titulo) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="block w-full text-center border border-gold-400/40 text-gold-400 hover:bg-gold-400/10 text-sm py-2.5 px-4 uppercase tracking-wider transition-all duration-300">
                                Agendar visita
                            </a>
                        @else
                            <div class="text-center py-3 bg-dark-700 rounded-sm text-cream-300/50 text-sm">
                                Esta propiedad ya no está disponible
                            </div>
                        @endif
                    </div>

                    {{-- Créditos --}}
                    @if($propiedad->acepta_infonavit || $propiedad->acepta_fovissste)
                    <div class="bg-dark-800 border border-dark-700 rounded-sm px-6 py-5">
                        <p class="text-white text-sm font-semibold mb-4">Acepta créditos</p>
                        <div class="space-y-3">
                            @if($propiedad->acepta_infonavit)
                            <div class="flex items-center gap-3 text-sm text-cream-300">
                                <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Crédito INFONAVIT
                            </div>
                            @endif
                            @if($propiedad->acepta_fovissste)
                            <div class="flex items-center gap-3 text-sm text-cream-300">
                                <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Crédito FOVISSSTE
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Volver --}}
                    <a href="{{ route('propiedades.index') }}"
                       class="block text-center text-cream-300/50 hover:text-gold-400 text-xs transition-colors">
                        ← Ver todas las propiedades
                    </a>

                </div>
            </div>

        </div>

    </div>
</section>
@endsection
