<x-filament-panels::page>

    {{ $this->form }}

    @php
        $m = $this->metricas();
        $t = $m->totales();
        $porAsesor = $m->porAsesor();
        $porMedio  = $m->porMedio();
        $p = $this->parametrosGrafica();
    @endphp

    {{-- KPIs del rango --}}
    <div style="display:grid; grid-template-columns: repeat(5,1fr); gap:16px;">
        @foreach([
            ['label'=>'Escaneos',    'value'=>$t['escaneos'],          'color'=>'#B8960C', 'icon'=>'🔳'],
            ['label'=>'Registros',   'value'=>$t['registros'],         'color'=>'#0e7490', 'icon'=>'📱'],
            ['label'=>'Solicitudes', 'value'=>$t['solicitudes'],       'color'=>'#7e22ce', 'icon'=>'👥'],
            ['label'=>'Expedientes', 'value'=>$t['expedientes'],       'color'=>'#15803d', 'icon'=>'📁'],
            ['label'=>'Conversión',  'value'=>$t['conversion'] . '%',  'color'=>'#1a1a1a', 'icon'=>'🎯'],
        ] as $kpi)
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px 16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <div style="font-size:22px;">{{ $kpi['icon'] }}</div>
            <div style="font-size:28px; font-weight:700; color:{{ $kpi['color'] }}; line-height:1.1; margin:6px 0 4px;">{{ $kpi['value'] }}</div>
            <div style="font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">{{ $kpi['label'] }}</div>
        </div>
        @endforeach
    </div>
    <p style="font-size:12px; color:#6b7280; margin-top:-8px;">
        Conversión = expedientes ÷ escaneos. Cada paso cuenta por su propia fecha: una lona pegada hace un mes sigue sumando expedientes hoy. {{ $t['qrs'] }} QR en el filtro.
    </p>

    {{-- Gráfica semanal: la clave cambia con los filtros para que se vuelva a montar --}}
    @livewire(\App\Filament\Widgets\EscaneosQrSemanaChart::class, $p, key('qr-chart-' . md5(json_encode($p))))

    @php
        $tabla = function ($filas, string $primera) {
            $th = 'padding:10px 12px; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; border-bottom:1px solid #e5e7eb; text-align:center;';
            $td = 'padding:10px 12px; border-bottom:1px solid #f3f4f6; text-align:center; font-variant-numeric: tabular-nums;';
            return compact('th', 'td');
        };
    @endphp

    <div style="display:grid; grid-template-columns: 3fr 2fr; gap:16px;">
        @foreach([['Por asesor', 'Asesor', $porAsesor], ['Por medio', 'Medio', $porMedio]] as [$titulo, $primera, $filas])
            @php $s = $tabla($filas, $primera); @endphp
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <div style="padding:14px 16px; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ $titulo }}</div>
                <table style="width:100%; border-collapse:collapse; font-size:14px;">
                    <thead>
                        <tr>
                            <th style="{{ $s['th'] }} text-align:left;">{{ $primera }}</th>
                            <th style="{{ $s['th'] }}">QR</th>
                            <th style="{{ $s['th'] }}">Escaneos</th>
                            <th style="{{ $s['th'] }}">Registros</th>
                            <th style="{{ $s['th'] }}">Solicitudes</th>
                            <th style="{{ $s['th'] }}">Expedientes</th>
                            <th style="{{ $s['th'] }}">Conv.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($filas as $f)
                            <tr>
                                <td style="{{ $s['td'] }} text-align:left; font-weight:600;">{{ $f['nombre'] }}</td>
                                <td style="{{ $s['td'] }} color:#6b7280;">{{ $f['qrs'] }}</td>
                                <td style="{{ $s['td'] }}">{{ $f['escaneos'] }}</td>
                                <td style="{{ $s['td'] }}">{{ $f['registros'] }}</td>
                                <td style="{{ $s['td'] }}">{{ $f['solicitudes'] }}</td>
                                <td style="{{ $s['td'] }} font-weight:700; color:{{ $f['expedientes'] > 0 ? '#15803d' : '#9ca3af' }};">{{ $f['expedientes'] }}</td>
                                <td style="{{ $s['td'] }}">{{ $f['conversion'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="padding:24px; text-align:center; color:#9ca3af;">Sin QR en este filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

</x-filament-panels::page>
