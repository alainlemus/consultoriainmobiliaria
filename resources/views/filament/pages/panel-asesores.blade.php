<x-filament-panels::page>

    {{-- KPIs globales --}}
    @php $r = $this->getResumenGlobal(); @endphp
    <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:16px; margin-bottom:24px;">

        @foreach([
            ['label'=>'Prospectos',        'value'=>$r['prospectos'],     'color'=>'#1a1a1a', 'icon'=>'👥'],
            ['label'=>'Expedientes',       'value'=>$r['expedientes'],    'color'=>'#d97706', 'icon'=>'📁'],
            ['label'=>'Documentos subidos','value'=>$r['documentos'],     'color'=>'#16a34a', 'icon'=>'📄'],
            ['label'=>'Por capturar',      'value'=>$r['por_capturar'],   'color'=>'#dc2626', 'icon'=>'📝'],
        ] as $kpi)
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px 16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <div style="font-size:22px;">{{ $kpi['icon'] }}</div>
            <div style="font-size:28px; font-weight:700; color:{{ $kpi['color'] }}; line-height:1.1; margin:6px 0 4px;">{{ $kpi['value'] }}</div>
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">{{ $kpi['label'] }}</div>
        </div>
        @endforeach

    </div>

    {{-- Tabla de asesores --}}
    {{ $this->table }}

</x-filament-panels::page>
