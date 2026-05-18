@php
    $fotos = $getRecord()->fotos ?? collect();
@endphp

@if($fotos->isEmpty())
    <span class="text-gray-400 text-xs">Sin fotos</span>
@else
    <div class="flex flex-wrap gap-1">
        @foreach($fotos->take(3) as $foto)
            @php
                $url = \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $foto->id], now()->addMinutes(30));
            @endphp
            <a href="{{ $url }}" target="_blank">
                <img src="{{ $url }}"
                     class="w-10 h-10 object-cover rounded"
                     loading="lazy" />
            </a>
        @endforeach
        @if($fotos->count() > 3)
            <span class="w-10 h-10 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-500 font-semibold">
                +{{ $fotos->count() - 3 }}
            </span>
        @endif
    </div>
@endif
