@extends('layouts.app')

@section('title', $propiedad->titulo . ' — ' . setting('site_name', 'Consultoría Inmobiliaria'))

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

@section('og_title', $propiedad->titulo . ' — ' . setting('site_name', 'Consultoría Inmobiliaria'))
@section('og_description', $ogDescripcion)
@section('og_type', 'article')
@section('og_url', route('propiedades.show', $propiedad->slug))
@section('og_image', $ogImagen)

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
                <div x-data="{ activa: 0, imagenes: {{ json_encode($propiedad->imagenes) }} }" class="mb-8">
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
                               target="_blank"
                               class="btn-gold w-full text-center block mb-3">
                                Solicitar información
                            </a>
                            <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text={{ urlencode('Hola, quiero agendar una visita para: ' . $propiedad->titulo) }}"
                               target="_blank"
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
