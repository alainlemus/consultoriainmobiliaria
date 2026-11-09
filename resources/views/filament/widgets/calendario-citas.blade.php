<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $colores = [
                'solicitada' => ['bg' => '#fef3c7', 'fg' => '#92400e', 'bd' => '#f59e0b'],
                'confirmada' => ['bg' => '#dcfce7', 'fg' => '#166534', 'bd' => '#22c55e'],
                'cancelada'  => ['bg' => '#fee2e2', 'fg' => '#991b1b', 'bd' => '#ef4444'],
                'realizada'  => ['bg' => '#e5e7eb', 'fg' => '#374151', 'bd' => '#9ca3af'],
                'no_asistio' => ['bg' => '#fee2e2', 'fg' => '#7f1d1d', 'bd' => '#b91c1c'],
            ];
        @endphp

        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:8px;">
                <x-filament::icon-button icon="heroicon-m-chevron-left" wire:click="anterior" label="Mes anterior" color="gray" />
                <span style="font-weight:700; font-size:16px; min-width:160px; text-align:center;">{{ $titulo }}</span>
                <x-filament::icon-button icon="heroicon-m-chevron-right" wire:click="siguiente" label="Mes siguiente" color="gray" />
                <x-filament::button size="xs" color="gray" wire:click="hoy">Hoy</x-filament::button>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; font-size:11px;">
                @foreach ($leyenda as $k => $label)
                    <span style="display:inline-flex; align-items:center; gap:4px;">
                        <span style="width:10px; height:10px; border-radius:3px; background:{{ $colores[$k]['bd'] }};"></span>{{ $label }}
                    </span>
                @endforeach
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(7, minmax(0, 1fr)); gap:4px;">
            @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d)
                <div style="text-align:center; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; padding:4px 0;">{{ $d }}</div>
            @endforeach

            @foreach ($celdas as $c)
                <div style="min-height:92px; border:1px solid {{ $c['esHoy'] ? '#B8960C' : 'rgba(128,128,128,.25)' }}; border-radius:8px; padding:4px; background:{{ $c['esHoy'] ? 'rgba(184,150,12,.08)' : 'transparent' }}; opacity:{{ $c['delMes'] ? 1 : .45 }};">
                    <div style="font-size:12px; font-weight:{{ $c['esHoy'] ? 700 : 500 }}; color:{{ $c['esHoy'] ? '#B8960C' : 'inherit' }}; margin-bottom:4px;">{{ $c['dia'] }}</div>
                    <div style="display:flex; flex-direction:column; gap:3px;">
                        @foreach ($c['citas'] as $cita)
                            @php $col = $colores[$cita['estado']] ?? $colores['realizada']; @endphp
                            <a href="{{ $cita['url'] }}" title="{{ $cita['hora'] }} · {{ $cita['nombre'] }} · {{ $cita['lugar'] }}"
                               style="display:block; font-size:11px; line-height:1.25; padding:3px 5px; border-radius:5px; background:{{ $col['bg'] }}; color:{{ $col['fg'] }}; border-left:3px solid {{ $col['bd'] }}; text-decoration:none; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">
                                <strong>{{ $cita['hora'] }}</strong> {{ $cita['tipo'] === 'domicilio' ? '🏠' : '🏢' }} {{ $cita['nombre'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
