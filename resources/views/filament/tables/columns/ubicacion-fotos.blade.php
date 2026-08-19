@php
    $fotos = $getRecord()->fotos ?? collect();
@endphp

@if($fotos->isEmpty())
    <span class="text-gray-400 text-xs">Sin fotos</span>
@else
    <div class="flex flex-wrap gap-1">
        @php
            // Expiración redondeada a la hora: misma URL firmada en renders repetidos
            // dentro de la misma hora, así el navegador puede reutilizar la imagen cacheada.
            $expira = now()->addHour()->startOfHour();
        @endphp
        @foreach($fotos->take(3) as $foto)
            @php
                $urlCompleta = \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $foto->id], $expira);
                $urlThumb    = \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $foto->id, 'thumb' => 1], $expira);
            @endphp
            <a href="{{ $urlCompleta }}" target="_blank">
                <img src="{{ $urlThumb }}"
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
