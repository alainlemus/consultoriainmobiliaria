@extends('layouts.app')

@section('seo_title', 'Propiedades en venta — ' . setting('site_name', 'Consultoría Inmobiliaria'))
@section('seo_description', 'Encuentra propiedades en venta con crédito INFONAVIT y FOVISSSTE en Hidalgo, Veracruz y San Luis Potosí. Casas y terrenos con asesoría especializada.')
@section('canonical', route('propiedades.index'))

@section('content')
<section class="bg-dark-900 min-h-screen" style="padding-top: 100px;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        {{-- Encabezado --}}
        <div class="text-center mb-12">
            <p class="section-subtitle text-gold-400 mb-3">En venta</p>
            <h1 class="section-title text-white mb-4">Propiedades <span class="text-gold-400">Disponibles</span></h1>
            <div class="gold-divider"></div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('propiedades.index') }}"
              class="bg-dark-800 border border-dark-700 rounded-sm p-6 mb-10"
              x-data="{ expanded: false }">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                {{-- Búsqueda --}}
                <div class="lg:col-span-2">
                    <label class="block text-cream-300/70 text-xs uppercase tracking-wider mb-2">Buscar</label>
                    <div class="relative">
                        <input type="text" name="q" value="{{ request('q') }}"
                               placeholder="Título, colonia, municipio..."
                               class="input-field pr-10">
                        <svg style="position:absolute; right:12px; top:50%; transform:translateY(-50%);" class="w-4 h-4 text-dark-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-cream-300/70 text-xs uppercase tracking-wider mb-2">Tipo</label>
                    <select name="tipo" class="input-field">
                        <option value="">Todos los tipos</option>
                        @foreach(\App\Models\Propiedad::tipos() as $val => $label)
                            <option value="{{ $val }}" {{ request('tipo') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-cream-300/70 text-xs uppercase tracking-wider mb-2">Estado</label>
                    <select name="estado" class="input-field">
                        <option value="">Todos los estados</option>
                        @foreach($estados as $estado)
                            <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Municipio --}}
                <div>
                    <label class="block text-cream-300/70 text-xs uppercase tracking-wider mb-2">Municipio</label>
                    <input type="text" name="municipio" value="{{ request('municipio') }}"
                           placeholder="Ej. Pachuca"
                           class="input-field">
                </div>

                {{-- Precio mín --}}
                <div>
                    <label class="block text-cream-300/70 text-xs uppercase tracking-wider mb-2">Precio mínimo</label>
                    <input type="number" name="precio_min" value="{{ request('precio_min') }}"
                           placeholder="$0"
                           class="input-field">
                </div>

                {{-- Precio máx --}}
                <div>
                    <label class="block text-cream-300/70 text-xs uppercase tracking-wider mb-2">Precio máximo</label>
                    <input type="number" name="precio_max" value="{{ request('precio_max') }}"
                           placeholder="Sin límite"
                           class="input-field">
                </div>

                {{-- Créditos --}}
                <div>
                    <label class="block text-cream-300/70 text-xs uppercase tracking-wider mb-2">Crédito</label>
                    <div class="flex gap-4 mt-1">
                        <label class="flex items-center gap-2 text-cream-300 text-xs cursor-pointer">
                            <input type="checkbox" name="infonavit" value="1" {{ request('infonavit') ? 'checked' : '' }}
                                   class="accent-gold-400">
                            INFONAVIT
                        </label>
                        <label class="flex items-center gap-2 text-cream-300 text-xs cursor-pointer">
                            <input type="checkbox" name="fovissste" value="1" {{ request('fovissste') ? 'checked' : '' }}
                                   class="accent-gold-400">
                            FOVISSSTE
                        </label>
                    </div>
                </div>

            </div>

            <div style="margin-top:24px; padding-top:20px; border-top: 1px solid rgba(255,255,255,0.08);" class="flex items-center justify-between gap-4">
                <p class="text-cream-300 text-xs">
                    {{ $propiedades->total() }} propiedad(es) encontrada(s)
                </p>
                <div class="flex gap-3">
                    @if(request()->anyFilled(['q','tipo','estado','municipio','precio_min','precio_max','infonavit','fovissste']))
                        <a href="{{ route('propiedades.index') }}"
                           class="text-cream-300/70 hover:text-cream-300 text-xs underline transition-colors">
                            Limpiar filtros
                        </a>
                    @endif
                    <button type="submit" class="btn-gold text-xs px-5 py-2">
                        Buscar
                    </button>
                </div>
            </div>
        </form>

        {{-- Grid de propiedades --}}
        @if($propiedades->count())
            {{-- Barra superior: total + per_page --}}
            <div class="flex items-center justify-between mb-5">
                <p class="text-cream-300 text-xs">
                    Mostrando {{ $propiedades->firstItem() }}–{{ $propiedades->lastItem() }} de {{ $propiedades->total() }} propiedades
                </p>
                <form method="GET" action="{{ route('propiedades.index') }}" class="flex items-center gap-2">
                    {{-- Mantener filtros activos --}}
                    @foreach(request()->except('per_page') as $key => $val)
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endforeach
                    <label class="text-cream-300 text-xs">Mostrar</label>
                    <select name="per_page" onchange="this.form.submit()"
                            class="bg-dark-800 border border-dark-700 text-cream-300 text-xs rounded-sm px-2 py-1.5 cursor-pointer">
                        @foreach([10, 20, 50] as $n)
                            <option value="{{ $n }}" {{ request('per_page', 12) == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                    <label class="text-cream-300 text-xs">por página</label>
                </form>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($propiedades as $propiedad)
                <a href="{{ route('propiedades.show', $propiedad->slug) }}"
                   class="group bg-dark-800 border border-dark-700 hover:border-gold-500/50 rounded-sm overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-gold-500/10 flex flex-col">

                    <div class="relative h-48 bg-dark-700 overflow-hidden">
                        @if($propiedad->imagen_principal)
                            <img src="{{ Storage::url($propiedad->imagen_principal) }}"
                                 alt="{{ $propiedad->titulo }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                                </svg>
                            </div>
                        @endif
                        <span class="absolute top-3 left-3 bg-dark-900/80 text-gold-400 text-xs px-2 py-1 uppercase tracking-wider">
                            {{ $propiedad->tipo }}
                        </span>
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

                    <div class="p-4 flex flex-col flex-1">
                        <h2 class="text-white text-sm font-semibold leading-snug mb-2 group-hover:text-gold-400 transition-colors line-clamp-2">
                            {{ $propiedad->titulo }}
                        </h2>
                        <p class="text-cream-300/70 text-xs mb-3 flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $propiedad->municipio }}, {{ $propiedad->estado }}
                        </p>
                        @if($propiedad->recamaras || $propiedad->banos || $propiedad->metros_construccion)
                        <div class="flex items-center gap-3 text-xs text-cream-300 mb-3">
                            @if($propiedad->recamaras)
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12V7a1 1 0 011-1h16a1 1 0 011 1v5M3 12h18M3 12v5m18-5v5M3 17h18M7 12V9h4v3M13 12V9h4v3"/></svg>
                                {{ $propiedad->recamaras }}
                            </span>
                            @endif
                            @if($propiedad->banos)
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h16v4a4 4 0 01-4 4H8a4 4 0 01-4-4v-4zm0 0V7a2 2 0 012-2h1a2 2 0 012 2v5"/></svg>
                                {{ $propiedad->banos }}
                            </span>
                            @endif
                            @if($propiedad->metros_construccion)
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21l18-18M3 21h6m-6 0v-6m12-6h6m-6 0v6m6-6v6m-6 0h6"/></svg>
                                {{ number_format($propiedad->metros_construccion, 0) }} m²
                            </span>
                            @endif
                        </div>
                        @endif
                        <div class="mt-auto pt-3 border-t border-dark-700">
                            <p class="text-gold-400 font-bold">{{ $propiedad->precio_formateado }}</p>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $propiedades->withQueryString()->links() }}
            </div>

        @else
            <div class="text-center py-20">
                <svg class="w-16 h-16 mx-auto mb-4 text-dark-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                </svg>
                <p class="text-cream-300 text-sm">No encontramos propiedades con esos filtros.</p>
                <a href="{{ route('propiedades.index') }}" class="text-gold-400 hover:text-gold-300 text-sm mt-2 inline-block transition-colors">
                    Ver todas las propiedades
                </a>
            </div>
        @endif

    </div>
</section>
@endsection
