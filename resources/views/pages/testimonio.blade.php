@extends('layouts.app')

@section('seo_title', 'Deja tu testimonio — ' . setting('seo_titulo', 'Consultoría Inmobiliaria'))
@section('seo_description', 'Comparte tu experiencia con nosotros. Tu opinión nos ayuda a mejorar y a otros clientes a tomar decisiones.')
@section('robots', 'noindex, nofollow')

@section('content')
<section class="min-h-screen bg-dark-800 flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-lg">

        {{-- Cabecera --}}
        <div class="text-center mb-10">
            <p class="section-subtitle text-gold-400 mb-2">Tu opinión importa</p>
            <h1 class="font-serif text-3xl font-bold text-white mb-3">
                Hola, <span class="text-gold-400">{{ $nombre }}</span>
            </h1>
            <div class="gold-divider"></div>
            <p class="text-cream-400 text-sm mt-4">
                Cuéntanos cómo fue tu experiencia. Tu testimonio ayuda a otras familias<br>
                a encontrar la asesoría que necesitan.
            </p>
        </div>

        {{-- Errores --}}
        @if($errors->any())
        <div class="bg-red-900/40 border border-red-500/50 rounded-sm px-5 py-4 mb-6">
            <ul class="text-red-300 text-sm space-y-1 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Formulario --}}
        <form method="POST" action="{{ route('testimonio.store', $token) }}"
              class="bg-dark-700 border border-dark-600 rounded-sm p-8 space-y-6"
              x-data="{ estrellas: {{ old('estrellas', 5) }}, hover: 0, chars: {{ strlen(old('testimonio', '')) }} }">
            @csrf

            {{-- Calificación --}}
            <div>
                <label class="block text-cream-300 text-sm font-medium mb-3">
                    Calificación <span class="text-gold-400">*</span>
                </label>
                <div class="flex gap-2">
                    @for($i = 1; $i <= 5; $i++)
                    <button type="button"
                        @click="estrellas = {{ $i }}"
                        @mouseenter="hover = {{ $i }}"
                        @mouseleave="hover = 0"
                        class="text-3xl transition-transform duration-100 hover:scale-110 focus:outline-none"
                        :class="(hover || estrellas) >= {{ $i }} ? 'text-gold-400' : 'text-dark-500'">
                        ★
                    </button>
                    @endfor
                </div>
                <input type="hidden" name="estrellas" :value="estrellas">
                <p class="text-xs text-cream-500 mt-1"
                   x-text="['','Malo','Regular','Bueno','Muy bueno','Excelente'][estrellas]"></p>
            </div>

            {{-- Testimonio --}}
            <div>
                <label for="testimonio" class="block text-cream-300 text-sm font-medium mb-2">
                    Tu experiencia <span class="text-gold-400">*</span>
                </label>
                <textarea
                    id="testimonio"
                    name="testimonio"
                    rows="5"
                    maxlength="1000"
                    placeholder="Cuéntanos cómo fue el proceso, qué servicio usaste y cómo te ayudamos…"
                    @input="chars = $event.target.value.length"
                    class="w-full bg-dark-600 border border-dark-500 focus:border-gold-500/60 focus:ring-0 rounded-sm px-4 py-3 text-cream-200 placeholder-dark-400 text-sm resize-none outline-none transition">{{ old('testimonio') }}</textarea>
                <p class="text-xs text-cream-600 text-right mt-1">
                    <span x-text="chars"></span>/1000
                </p>
                @error('testimonio')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nombre --}}
            <div>
                <label for="nombre" class="block text-cream-300 text-sm font-medium mb-2">
                    Tu nombre <span class="text-gold-400">*</span>
                </label>
                <input type="text" id="nombre" name="nombre"
                    value="{{ old('nombre', $nombre) }}"
                    placeholder="Ej. María González"
                    class="w-full bg-dark-600 border border-dark-500 focus:border-gold-500/60 rounded-sm px-4 py-3 text-cream-200 placeholder-dark-400 text-sm outline-none transition">
                @error('nombre')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Ciudad --}}
            <div>
                <label for="ciudad" class="block text-cream-300 text-sm font-medium mb-2">
                    Ciudad / Estado
                </label>
                <input type="text" id="ciudad" name="ciudad" value="{{ old('ciudad') }}"
                    placeholder="Ej. Pachuca, Hidalgo"
                    class="w-full bg-dark-600 border border-dark-500 focus:border-gold-500/60 rounded-sm px-4 py-3 text-cream-200 placeholder-dark-400 text-sm outline-none transition">
            </div>

            {{-- Servicio --}}
            <div>
                <label for="servicio" class="block text-cream-300 text-sm font-medium mb-2">
                    Servicio que usaste
                </label>
                <select id="servicio" name="servicio"
                    class="w-full bg-dark-600 border border-dark-500 focus:border-gold-500/60 rounded-sm px-4 py-3 text-cream-200 text-sm outline-none transition">
                    <option value="">— Seleccionar —</option>
                    @foreach(['INFONAVIT','FOVISSSTE','Combo FOVISSSTE+INFONAVIT','Avalúo','Escrituras','Asesoría general'] as $srv)
                    <option value="{{ $srv }}"
                        {{ (old('servicio', $servicio) == $srv) ? 'selected' : '' }}>
                        {{ $srv }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Submit --}}
            <button type="submit"
                class="w-full bg-gold-500 hover:bg-gold-400 text-dark-900 font-semibold py-3 rounded-sm transition-colors duration-200 text-sm tracking-wide">
                Enviar mi testimonio
            </button>

            <p class="text-xs text-center text-cream-600">
                Tu testimonio será revisado antes de publicarse. ¡Gracias por tu confianza!
            </p>
        </form>

    </div>
</section>
@endsection
