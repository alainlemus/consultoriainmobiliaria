@php
    $fotos = $getRecord()->fotos ?? collect();
@endphp

@if($fotos->isEmpty())
    <span class="text-gray-400 text-xs italic">Sin fotos</span>
@else
    {{-- Generar URLs firmadas (30 min) --}}
    @php
        $urls = $fotos->map(fn ($f) => \URL::signedRoute('api.anuncio.foto', ['fotoId' => $f->id], now()->addMinutes(30)))->values();
    @endphp

    <div class="flex flex-wrap gap-1 items-center">
        {{-- Miniaturas (máximo 3 visibles) --}}
        @foreach($urls->take(3) as $url)
            <button
                type="button"
                onclick="abrirCarouselAnuncio({{ $urls->toJson() }}, {{ $loop->index }})"
                class="focus:outline-none focus:ring-2 focus:ring-amber-400 rounded"
            >
                <img src="{{ $url }}"
                     class="w-10 h-10 object-cover rounded hover:opacity-80 transition-opacity cursor-zoom-in"
                     loading="lazy" />
            </button>
        @endforeach

        {{-- Badge "+N" si hay más de 3 --}}
        @if($urls->count() > 3)
            <button
                type="button"
                onclick="abrirCarouselAnuncio({{ $urls->toJson() }}, 3)"
                class="w-10 h-10 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-500 font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors cursor-pointer"
            >
                +{{ $urls->count() - 3 }}
            </button>
        @endif
    </div>
@endif
