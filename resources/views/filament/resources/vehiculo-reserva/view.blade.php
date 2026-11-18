<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

    @php($d = $this->datos())
    @php($r = $d['r'])

    <style>
        .vr-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .vr-two { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .vr-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; padding: 1rem 1.25rem; }
        .dark .vr-card { background: #18181b; border-color: rgba(255,255,255,.1); }
        .vr-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; color: #6b7280; margin-bottom: .25rem; }
        .dark .vr-label { color: #9ca3af; }
        .vr-value { font-size: 1rem; font-weight: 700; }
        .vr-muted { color: #6b7280; font-size: .875rem; }
        .dark .vr-muted { color: #9ca3af; }
        .vr-fotos { display: grid; gap: .5rem; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); margin-top: .75rem; }
        .vr-fotos a { display: block; }
        .vr-fotos img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: .5rem; border: 1px solid #e5e7eb; }
        .vr-fotos span { display: block; font-size: .75rem; text-align: center; margin-top: .25rem; }
        .vr-warn { background: #fef3c7; color: #92400e; border-radius: .5rem; padding: .5rem .75rem; font-size: .875rem; }
        .dark .vr-warn { background: rgba(245,158,11,.15); color: #fcd34d; }
        .vr-row { display: flex; justify-content: space-between; gap: 1rem; padding: .35rem 0; border-bottom: 1px solid rgba(127,127,127,.15); font-size: .9rem; }
        .vr-row:last-child { border-bottom: 0; }
    </style>

    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <div class="vr-grid">
            <div class="vr-card"><p class="vr-label">Estado</p>
                <x-filament::badge :color="$d['color']">{{ $d['estado'] }}{{ $d['fila'] ? ' #' . $d['fila'] : '' }}</x-filament::badge></div>
            <div class="vr-card"><p class="vr-label">Cuándo</p><p class="vr-value">{{ $d['cuando'] }}</p></div>
            <div class="vr-card"><p class="vr-label">Vehículo</p><p class="vr-value">{{ $r->vehiculo->etiqueta() }}</p><p class="vr-muted">{{ $r->vehiculo->tipo->nombre }}</p></div>
            <div class="vr-card"><p class="vr-label">Asesor</p><p class="vr-value">{{ $r->asesor->name }}</p>
                @if($d['licencia']['vigente'])
                    <p class="vr-muted">Licencia vigente hasta {{ $d['licencia']['vence'] }}@if($d['licencia']['url']) · <a href="{{ $d['licencia']['url'] }}" target="_blank" style="text-decoration:underline">ver</a>@endif</p>
                @else
                    <p class="vr-warn" style="margin-top:.35rem">⚠️ Sin licencia vigente{{ $d['licencia']['vence'] ? ' (venció ' . $d['licencia']['vence'] . ')' : '' }}</p>
                @endif
            </div>
        </div>

        @if($d['alertas'])
        <div class="vr-card" style="border-color:#f59e0b">
            <p class="vr-label" style="color:#b45309">⚠️ Alertas por revisar</p>
            @foreach($d['alertas'] as $a)<p class="vr-warn" style="margin-top:.35rem">{{ $a }}</p>@endforeach
        </div>
        @endif

        @if($d['desplazada'])
        <div class="vr-card">
            <p class="vr-label">Prioridad</p>
            <p>🔀 Se desplazó el {{ $d['desplazada']['cuando'] }}{{ $d['desplazada']['por'] ? ' (' . $d['desplazada']['por'] . ')' : '' }} · Motivo: {{ $d['desplazada']['motivo'] }}</p>
        </div>
        @endif

        <div class="vr-card">
            <p class="vr-label">Motivo</p>
            <p>{{ $r->motivo }}</p>
            @if($r->destino)<p class="vr-muted" style="margin-top:.25rem">Destino: {{ $r->destino }}</p>@endif
            <p class="vr-muted" style="margin-top:.25rem">
                {{ $r->resguardo_casa ? '🏠 Se lo lleva a su casa (resguardo nocturno)' : '🏢 Regresa a la oficina' }}
                @if($r->cita) · Cita: {{ $r->cita->nombre }} @endif
                @if($r->contacto) · Prospecto: {{ $r->contacto->nombre }} @endif
                @if($r->expediente) · Expediente {{ $r->expediente->folio }} @endif
            </p>
            @if($r->motivo_decision)<p class="vr-muted" style="margin-top:.25rem">Motivo de {{ $r->estado === 'cancelada' ? 'cancelación' : 'la decisión' }}: {{ $r->motivo_decision }}</p>@endif
            @if($r->decididoPor)<p class="vr-muted" style="margin-top:.25rem">Decidió: {{ $r->decididoPor->name }} · {{ $r->decidido_at?->setTimezone(\App\Services\VehiculoService::TZ)->format('d/m/Y g:i a') }}</p>@endif
        </div>

        @if($d['km'] !== null || $d['recorrido']['km_gps'] !== null)
        <div class="vr-grid">
            @if($d['km'] !== null)<div class="vr-card"><p class="vr-label">Km recorridos (tablero)</p><p class="vr-value">{{ number_format($d['km']) }} km</p></div>@endif
            @if($d['recorrido']['km_gps'] !== null)<div class="vr-card"><p class="vr-label">Km según GPS</p><p class="vr-value">{{ $d['recorrido']['km_gps'] }} km</p></div>@endif
        </div>
        @endif

        <div class="vr-two">
            @foreach(['salida' => 'Salida', 'entrega' => 'Entrega'] as $clave => $titulo)
                <div class="vr-card">
                    <p class="vr-value" style="margin-bottom:.5rem">{{ $titulo }}</p>
                    @if($m = $d[$clave])
                        <div class="vr-row"><span class="vr-muted">Fecha</span><span>{{ $m['cuando'] }}</span></div>
                        <div class="vr-row"><span class="vr-muted">Km</span><span>{{ number_format($m['km']) }}</span></div>
                        <div class="vr-row"><span class="vr-muted">Gasolina</span><span>{{ $m['gasolina'] }}</span></div>
                        <div class="vr-row"><span class="vr-muted">Llaves</span><span>{{ $m['llaves'] }}</span></div>
                        <div class="vr-row"><span class="vr-muted">Checklist</span><span>{{ $m['checklist'] ? implode(', ', $m['checklist']) : '—' }}</span></div>
                        @if($m['notas_danos'])<p class="vr-warn" style="margin-top:.5rem">Daños: {{ $m['notas_danos'] }}</p>@endif
                        <div class="vr-fotos">
                            @foreach($m['fotos'] as $f)
                                <a href="{{ $f['url'] }}" target="_blank"><img src="{{ $f['thumb'] }}" alt="{{ $f['etiqueta'] }}" loading="lazy"><span>{{ $f['etiqueta'] }}</span></a>
                            @endforeach
                        </div>
                    @else
                        <p class="vr-muted">Todavía no se registra.</p>
                    @endif
                </div>
            @endforeach
        </div>

        @if($d['eventos'])
        <div class="vr-card">
            <p class="vr-label">Resguardo en casa</p>
            @foreach($d['eventos'] as $e)
                <div class="vr-row"><span><b>{{ $e['titulo'] }}</b> · {{ number_format($e['km']) }} km{{ $e['gasolina'] ? ' · ' . $e['gasolina'] : '' }}@if($e['ubicacion']) · <a href="{{ $e['ubicacion'] }}" target="_blank" style="text-decoration:underline">ubicación</a>@endif</span><span class="vr-muted">{{ $e['cuando'] }}</span></div>
                <div class="vr-fotos">@foreach($e['fotos'] as $f)<a href="{{ $f['url'] }}" target="_blank"><img src="{{ $f['thumb'] }}" alt="{{ $f['etiqueta'] }}" loading="lazy"><span>{{ $f['etiqueta'] }}</span></a>@endforeach</div>
            @endforeach
        </div>
        @endif

        @if($d['cargas'])
        <div class="vr-card">
            <p class="vr-label">Cargas de gasolina</p>
            @foreach($d['cargas'] as $c)
                <div class="vr-row"><span><b>{{ $c['texto'] }}</b> · {{ $c['pago'] }}@if($c['reembolso']) · <x-filament::badge :color="$c['reembolso'] === 'pagado' ? 'success' : 'danger'" style="display:inline-flex">{{ $c['reembolso'] === 'pagado' ? 'Reembolsado' : 'Reembolso pendiente' }}</x-filament::badge>@endif</span><span class="vr-muted">{{ $c['cuando'] }}</span></div>
                <div class="vr-fotos">@foreach($c['fotos'] as $f)<a href="{{ $f['url'] }}" target="_blank"><img src="{{ $f['thumb'] }}" alt="{{ $f['etiqueta'] }}" loading="lazy"><span>{{ $f['etiqueta'] }}</span></a>@endforeach</div>
            @endforeach
        </div>
        @endif

        @if($d['llaves'])
        <div class="vr-card">
            <p class="vr-label">Cambios de llaves durante la reserva</p>
            @foreach($d['llaves'] as $l)
                <div class="vr-row"><span>{{ $l['texto'] }}</span><span class="vr-muted">{{ $l['cuando'] }}</span></div>
            @endforeach
        </div>
        @endif

        @if(count($d['recorrido']['puntos']) > 1)
        <div class="vr-card" style="padding:0;overflow:hidden">
            <p class="vr-label" style="padding:1rem 1.25rem .5rem">Recorrido (GPS de la app)</p>
            <div wire:ignore
                 x-data="{ puntos: @js($d['recorrido']['puntos']) }"
                 x-init="$nextTick(() => {
                     const mapa = L.map($el).setView(puntos[0], 14);
                     L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(mapa);
                     const linea = L.polyline(puntos, { color: '#B8960C', weight: 4 }).addTo(mapa);
                     L.circleMarker(puntos[0], { radius: 7, color: '#16a34a', fillOpacity: 1 }).addTo(mapa).bindTooltip('Salida');
                     L.circleMarker(puntos[puntos.length - 1], { radius: 7, color: '#dc2626', fillOpacity: 1 }).addTo(mapa).bindTooltip('Último punto');
                     mapa.fitBounds(linea.getBounds(), { padding: [24, 24] });
                 })"
                 style="height:420px;width:100%"></div>
        </div>
        @elseif($r->salida)
        <div class="vr-card"><p class="vr-muted">No hay puntos GPS de este recorrido (el asesor no tenía activada la ruta en la app).</p></div>
        @endif
    </div>
</x-filament-panels::page>
