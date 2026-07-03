{{--
    Modal carousel de fotos para anuncios.
    Recibe: $fotos — Collection de URLs firmadas.
    El carousel es Alpine.js puro para integrarse con el modal de Filament.
--}}
<div
    x-data="{
        urls: {{ $fotos->toJson() }},
        idx: 0,
        get total() { return this.urls.length; },
        prev() { this.idx = (this.idx - 1 + this.total) % this.total; },
        next() { this.idx = (this.idx + 1) % this.total; },
        goTo(i) { this.idx = i; },
    }"
    @keydown.arrow-left.window="prev()"
    @keydown.arrow-right.window="next()"
    class="flex flex-col items-center gap-4 py-2"
>
    {{-- Imagen principal --}}
    <div class="relative w-full flex items-center justify-center" style="min-height: 320px;">

        {{-- Flecha izquierda --}}
        <button
            x-show="total > 1"
            @click="prev()"
            type="button"
            class="absolute left-0 z-10 w-9 h-9 rounded-full bg-gray-900/60 hover:bg-gray-900/80 flex items-center justify-center text-white transition"
        >
            <x-heroicon-o-chevron-left class="w-5 h-5" />
        </button>

        {{-- Foto --}}
        <template x-for="(url, i) in urls" :key="i">
            <img
                x-show="idx === i"
                :src="url"
                alt="Foto del anuncio"
                class="max-w-full rounded-xl shadow-lg object-contain"
                style="max-height: 60vh;"
            />
        </template>

        {{-- Flecha derecha --}}
        <button
            x-show="total > 1"
            @click="next()"
            type="button"
            class="absolute right-0 z-10 w-9 h-9 rounded-full bg-gray-900/60 hover:bg-gray-900/80 flex items-center justify-center text-white transition"
        >
            <x-heroicon-o-chevron-right class="w-5 h-5" />
        </button>
    </div>

    {{-- Contador --}}
    <p x-show="total > 1" class="text-sm text-gray-500 dark:text-gray-400">
        <span x-text="idx + 1"></span> / <span x-text="total"></span>
    </p>

    {{-- Miniaturas --}}
    <div x-show="total > 1" class="flex flex-wrap gap-2 justify-center">
        <template x-for="(url, i) in urls" :key="i">
            <button
                type="button"
                @click="goTo(i)"
                :class="idx === i
                    ? 'ring-2 ring-amber-500 opacity-100'
                    : 'opacity-50 hover:opacity-80'"
                class="rounded-lg overflow-hidden transition focus:outline-none"
            >
                <img :src="url" class="w-14 h-14 object-cover" />
            </button>
        </template>
    </div>
</div>
