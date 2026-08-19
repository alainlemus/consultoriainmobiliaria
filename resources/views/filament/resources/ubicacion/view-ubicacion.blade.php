<x-filament-panels::page>

    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />

    @php
        $record  = $this->record;
        $fotos   = $this->getFotosConUrl();
        $latitud = $record->latitud;
        $longitud= $record->longitud;
        $tipo    = $record->tipo === 'visita_cliente' ? 'Visita cliente' : 'Propiedad';
        $icon    = $record->tipo === 'visita_cliente' ? '🏠' : '🏢';
    @endphp

    <div class="space-y-6">

        {{-- Tarjetas de datos --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Tipo</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $icon }} {{ $tipo }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Asesor</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $record->user?->name ?? '—' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Cliente</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $record->contacto?->nombre ?? '—' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Fecha</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $record->visitado_en?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            @if($record->municipio || $record->estado)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Municipio</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $record->municipio ?? '—' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Estado</p>
                <p class="text-base font-bold text-gray-900 dark:text-white">{{ $record->estado ?? '—' }}</p>
            </div>
            @endif
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-1">Coordenadas</p>
                <p class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $latitud }}, {{ $longitud }}</p>
            </div>
        </div>

        {{-- Notas --}}
        @if($record->notas)
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold mb-2">Notas</p>
            <p class="text-gray-800 dark:text-gray-200 italic">"{{ $record->notas }}"</p>
        </div>
        @endif

        {{-- Mapa --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden">
            <div class="px-5 pt-4 pb-2">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Ubicación en mapa</p>
            </div>
            <div id="mapa-detalle" style="height:420px;width:100%;"></div>
        </div>

        {{-- Fotos --}}
        @if($fotos->isNotEmpty())
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                Fotos ({{ $fotos->count() }})
            </p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @foreach($fotos as $foto)
                    <a href="{{ $foto['url'] }}" target="_blank" class="block group">
                        <img src="{{ $foto['url'] }}"
                             alt="Foto visita"
                             class="w-full aspect-square object-cover rounded-lg border border-gray-200 dark:border-white/10 group-hover:opacity-90 transition-opacity"
                             loading="lazy" />
                    </a>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-5 text-center text-gray-400 text-sm">
            Sin fotos registradas para esta visita.
        </div>
        @endif

    </div>

    {{-- Leaflet JS --}}
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lat = {{ $latitud }};
            const lng = {{ $longitud }};
            const tipo = '{{ $record->tipo }}';

            const mapa = L.map('mapa-detalle').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(mapa);

            const color = tipo === 'visita_cliente' ? '#f59e0b' : '#7c3aed';
            const emoji = tipo === 'visita_cliente' ? '🏠' : '🏢';

            const icon = L.divIcon({
                className: '',
                html: `<div style="width:40px;height:40px;border-radius:50%;background:white;border:3px solid ${color};
                             display:flex;align-items:center;justify-content:center;font-size:20px;
                             box-shadow:0 2px 8px rgba(0,0,0,0.25)">${emoji}</div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            });

            L.marker([lat, lng], { icon }).addTo(mapa);
        });
    </script>

</x-filament-panels::page>
