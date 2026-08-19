@php
    $fotos = $getRecord()->fotos ?? collect();
@endphp

@if($fotos->isEmpty())
    <span class="text-gray-400 text-xs italic">Sin fotos</span>
@else
    @php
        // Expiración redondeada a la hora: misma URL firmada en renders repetidos
        // dentro de la misma hora, así el navegador puede reutilizar la imagen cacheada.
        $expira = now()->addHour()->startOfHour();
        $urls = $fotos->map(fn ($f) => \URL::signedRoute('api.anuncio.foto', ['fotoId' => $f->id, 'thumb' => 1], $expira))->values();
    @endphp

    <div class="flex flex-wrap gap-1 items-center">
        @foreach($urls->take(3) as $url)
            <img src="{{ $url }}"
                 class="w-10 h-10 object-cover rounded"
                 loading="lazy" />
        @endforeach

        @if($urls->count() > 3)
            <span class="w-10 h-10 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-500 font-semibold">
                +{{ $urls->count() - 3 }}
            </span>
        @endif
    </div>
@endif
