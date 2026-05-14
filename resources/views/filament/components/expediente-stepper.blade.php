@php
    if (!isset($record) || !$record) return;

    $etapas  = \App\Models\EtapaTramite::where('tipo_tramite_id', $record->tipo_tramite_id)
                ->orderBy('orden')->get();
    $current = $record->etapa?->orden ?? 0;
    $total   = $etapas->count();

    $estadoConfig = [
        'en_proceso' => ['label' => 'En proceso', 'bg' => '#1d4ed8', 'text' => '#eff6ff'],
        'aprobado'   => ['label' => 'Aprobado',   'bg' => '#15803d', 'text' => '#f0fdf4'],
        'firmado'    => ['label' => 'Firmado',     'bg' => '#7e22ce', 'text' => '#faf5ff'],
        'pausado'    => ['label' => 'Pausado',     'bg' => '#b45309', 'text' => '#fffbeb'],
        'cerrado'    => ['label' => 'Cerrado',     'bg' => '#374151', 'text' => '#f9fafb'],
    ];
    $cfg = $estadoConfig[$record->estado] ?? ['label' => ucfirst($record->estado), 'bg' => '#6b7280', 'text' => '#fff'];
@endphp

<div style="padding: 16px 0 8px;">
    {{-- Stepper --}}
    <div style="display:flex; align-items:flex-start; position:relative;">
        @foreach($etapas as $i => $etapa)
            @php
                $isDone    = $etapa->orden < $current;
                $isCurrent = $etapa->orden === $current;
                $isPending = $etapa->orden > $current;

                $circleStyle = match(true) {
                    $isDone    => 'background:#16a34a; border-color:#16a34a; color:#fff;',
                    $isCurrent => 'background:#2563eb; border-color:#2563eb; color:#fff; box-shadow:0 0 0 3px #bfdbfe;',
                    default    => 'background:#f9fafb; border-color:#d1d5db; color:#9ca3af;',
                };
                $labelStyle = match(true) {
                    $isCurrent => 'font-weight:700; color:#1d4ed8;',
                    $isDone    => 'font-weight:500; color:#15803d;',
                    default    => 'color:#9ca3af;',
                };
            @endphp

            <div style="flex:1; display:flex; flex-direction:column; align-items:center; position:relative;">
                {{-- Connector line before --}}
                @if(!$loop->first)
                    <div style="position:absolute; top:16px; left:-50%; right:50%; height:2px;
                        background:{{ $isDone || $isCurrent ? '#16a34a' : '#e5e7eb' }};
                        z-index:0;"></div>
                @endif

                {{-- Circle --}}
                <div style="position:relative; z-index:1; width:32px; height:32px; border-radius:50%;
                    border:2px solid; display:flex; align-items:center; justify-content:center;
                    font-size:0.8rem; font-weight:700; {{ $circleStyle }}">
                    @if($isDone)
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        {{ $etapa->orden }}
                    @endif
                </div>

                {{-- Label --}}
                <div style="margin-top:8px; font-size:0.72rem; text-align:center; line-height:1.3;
                    max-width:80px; {{ $labelStyle }}">
                    {{ preg_replace('/^\d+\.\s*/', '', $etapa->nombre) }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Summary bar --}}
    <div style="margin-top:16px; display:flex; align-items:center; gap:12px; font-size:0.8rem; color:#6b7280;">
        <span style="padding:2px 10px; border-radius:9999px; font-weight:600; font-size:0.72rem;
            background:{{ $cfg['bg'] }}; color:{{ $cfg['text'] }};">
            {{ $cfg['label'] }}
        </span>
        <span>Etapa <strong>{{ $current }}</strong> de <strong>{{ $total }}</strong></span>
        <span>·</span>
        <span>{{ $record->etapa ? preg_replace('/^\d+\.\s*/', '', $record->etapa->nombre) : '—' }}</span>
        @if($record->tipoTramite)
            <span>·</span>
            <span>{{ $record->tipoTramite->nombre }}</span>
        @endif
    </div>
</div>
