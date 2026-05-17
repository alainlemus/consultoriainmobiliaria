<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Gestión</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            background: #ffffff;
        }
        /* ── Encabezado ── */
        .header {
            background: #1a1a2e;
            color: #ffffff;
            padding: 20px 24px 16px;
            margin-bottom: 20px;
        }
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .brand { font-size: 16px; font-weight: bold; color: #f5c842; letter-spacing: 0.5px; }
        .report-type {
            background: #f5c842;
            color: #1a1a2e;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .period-label { color: #aab4c8; font-size: 9px; margin-top: 4px; }
        .period-dates { color: #ffffff; font-size: 11px; margin-top: 2px; }

        /* ── KPI Cards ── */
        .kpis { display: flex; gap: 10px; margin: 0 0 18px; }
        .kpi {
            flex: 1;
            background: #f8f9fc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
        }
        .kpi-value { font-size: 20px; font-weight: bold; color: #1a1a2e; line-height: 1; }
        .kpi-label { font-size: 8px; color: #718096; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.4px; }
        .kpi.success .kpi-value { color: #276749; }
        .kpi.warning .kpi-value { color: #b7791f; }
        .kpi.danger  .kpi-value { color: #c53030; }
        .kpi.info    .kpi-value { color: #2b6cb0; }

        /* ── Secciones ── */
        .section { margin-bottom: 20px; }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a2e;
            border-bottom: 2px solid #f5c842;
            padding-bottom: 4px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Tablas ── */
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #1a1a2e;
            color: #ffffff;
            padding: 5px 8px;
            text-align: left;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        td { padding: 5px 8px; border-bottom: 1px solid #edf2f7; font-size: 9px; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) td { background: #f7fafc; }

        /* ── Insignias de estado ── */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-green  { background: #c6f6d5; color: #276749; }
        .badge-yellow { background: #fefcbf; color: #b7791f; }
        .badge-red    { background: #fed7d7; color: #c53030; }
        .badge-blue   { background: #bee3f8; color: #2b6cb0; }
        .badge-gray   { background: #e2e8f0; color: #4a5568; }

        /* ── Alerta vacío ── */
        .empty-row td { text-align: center; color: #a0aec0; font-style: italic; padding: 12px; }

        /* ── Grid de dos columnas ── */
        .two-col { display: flex; gap: 14px; }
        .two-col .col { flex: 1; }

        /* ── Footer ── */
        .footer {
            margin-top: 24px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            color: #a0aec0;
            font-size: 8px;
            text-align: center;
        }

        .money { font-variant-numeric: tabular-nums; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

{{-- ════ ENCABEZADO ════ --}}
<div class="header">
    <div class="header-top">
        <div>
            <div class="brand">{{ config('app.name') }}</div>
            <div class="period-label">Período del reporte</div>
            <div class="period-dates">
                @if($datos['tipo'] === 'diario')
                    {{ $datos['desde']->isoFormat('dddd D [de] MMMM, YYYY') }}
                @else
                    {{ $datos['desde']->isoFormat('D MMM YYYY') }} — {{ $datos['hasta']->isoFormat('D MMM YYYY') }}
                @endif
            </div>
        </div>
        <div class="text-right">
            <span class="report-type">Reporte {{ $datos['tipo'] }}</span>
            <div class="period-label" style="margin-top:6px;">Generado: {{ $datos['generado_en']->format('d/m/Y H:i') }}</div>
        </div>
    </div>
</div>

{{-- ════ KPIs RÁPIDOS ════ --}}
<div class="kpis">
    <div class="kpi info">
        <div class="kpi-value">{{ $datos['expedientes']['abiertos_periodo'] }}</div>
        <div class="kpi-label">Expedientes abiertos</div>
    </div>
    <div class="kpi success">
        <div class="kpi-value">{{ $datos['expedientes']['cerrados_periodo'] }}</div>
        <div class="kpi-label">Expedientes cerrados</div>
    </div>
    <div class="kpi info">
        <div class="kpi-value">{{ $datos['prospectos']['nuevos_periodo'] }}</div>
        <div class="kpi-label">Prospectos nuevos</div>
    </div>
    <div class="kpi success">
        <div class="kpi-value">{{ $datos['prospectos']['convertidos_periodo'] }}</div>
        <div class="kpi-label">Convertidos</div>
    </div>
    <div class="kpi success">
        <div class="kpi-value">$ {{ number_format($datos['comisiones']['generadas_monto'], 0) }}</div>
        <div class="kpi-label">Comisiones generadas</div>
    </div>
    <div class="kpi warning">
        <div class="kpi-value">{{ $datos['sin_movimiento']['total'] }}</div>
        <div class="kpi-label">Sin movimiento ({{ $datos['sin_movimiento']['umbral_dias'] }}d)</div>
    </div>
</div>

{{-- ════ SECCIÓN 1: EXPEDIENTES ════ --}}
<div class="two-col">
    <div class="col">
        <div class="section">
            <div class="section-title">1. Expedientes del período</div>
            <table>
                <tr>
                    <th>Estado</th>
                    <th class="text-right">Total acumulado</th>
                </tr>
                @forelse($datos['expedientes']['por_estado'] as $estado => $total)
                <tr>
                    <td>
                        @php
                            $badge = match($estado) {
                                'cerrado'    => 'badge-green',
                                'en_proceso' => 'badge-blue',
                                'aprobado'   => 'badge-yellow',
                                'firmado'    => 'badge-yellow',
                                'cancelado'  => 'badge-red',
                                default      => 'badge-gray',
                            };
                            $label = match($estado) {
                                'en_proceso' => 'En proceso',
                                'pausado'    => 'Pausado',
                                'aprobado'   => 'Aprobado',
                                'firmado'    => 'Firmado',
                                'cerrado'    => 'Cerrado',
                                'cancelado'  => 'Cancelado',
                                default      => ucfirst($estado),
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ $label }}</span>
                    </td>
                    <td class="text-right bold">{{ $total }}</td>
                </tr>
                @empty
                <tr class="empty-row"><td colspan="2">Sin expedientes registrados</td></tr>
                @endforelse
                <tr>
                    <td class="bold">Activos en total</td>
                    <td class="text-right bold">{{ $datos['expedientes']['activos_total'] }}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col">
        <div class="section">
            <div class="section-title">2. Prospectos</div>
            <table>
                <tr><th>Métrica</th><th class="text-right">Valor</th></tr>
                <tr>
                    <td>Nuevos en el período</td>
                    <td class="text-right bold">{{ $datos['prospectos']['nuevos_periodo'] }}</td>
                </tr>
                <tr>
                    <td>Convertidos en el período</td>
                    <td class="text-right bold" style="color:#276749;">{{ $datos['prospectos']['convertidos_periodo'] }}</td>
                </tr>
                <tr>
                    <td>Pendientes de cierre (acumulado)</td>
                    <td class="text-right bold" style="color:#b7791f;">{{ $datos['prospectos']['pendientes_cierre'] }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>

{{-- ════ SECCIÓN 3: COMISIONES ════ --}}
<div class="section">
    <div class="section-title">3. Comisiones</div>
    <div class="two-col">
        <div class="col">
            <table>
                <tr><th>Métrica</th><th class="text-right">Cant.</th><th class="text-right">Monto</th></tr>
                <tr>
                    <td>Generadas en el período</td>
                    <td class="text-right">{{ $datos['comisiones']['generadas_count'] }}</td>
                    <td class="text-right money bold" style="color:#276749;">$ {{ number_format($datos['comisiones']['generadas_monto'], 2) }}</td>
                </tr>
                <tr>
                    <td>Pagadas en el período</td>
                    <td class="text-right">{{ $datos['comisiones']['pagadas_count'] }}</td>
                    <td class="text-right money bold" style="color:#2b6cb0;">$ {{ number_format($datos['comisiones']['pagadas_monto'], 2) }}</td>
                </tr>
                <tr>
                    <td>Pendientes de pago (acumulado)</td>
                    <td class="text-right">{{ $datos['comisiones']['pendientes_count'] }}</td>
                    <td class="text-right money bold" style="color:#b7791f;">$ {{ number_format($datos['comisiones']['pendientes_monto'], 2) }}</td>
                </tr>
            </table>
        </div>
        <div class="col">
            @if(count($datos['comisiones']['lista_generadas']) > 0)
            <table>
                <tr><th>Folio</th><th>Asesor</th><th class="text-right">Monto</th><th>Estado</th></tr>
                @foreach($datos['comisiones']['lista_generadas'] as $c)
                <tr>
                    <td>{{ $c['folio'] }}</td>
                    <td>{{ $c['asesor'] }}</td>
                    <td class="text-right money">$ {{ number_format($c['monto'], 2) }}</td>
                    <td>
                        <span class="badge {{ $c['estado'] === 'pagada' ? 'badge-green' : ($c['estado'] === 'aprobada' ? 'badge-blue' : 'badge-yellow') }}">
                            {{ ucfirst($c['estado']) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </table>
            @else
            <p style="color:#a0aec0; font-style:italic; font-size:9px; padding:10px 0;">Sin comisiones generadas en este período.</p>
            @endif
        </div>
    </div>
</div>

{{-- ════ SECCIÓN 4: SEGUIMIENTOS ════ --}}
<div class="two-col">
    <div class="col">
        <div class="section">
            <div class="section-title">4. Seguimientos registrados</div>
            <table>
                <tr><th>Asesor</th><th class="text-right">Registros</th></tr>
                @forelse($datos['seguimientos']['por_asesor'] as $s)
                <tr>
                    <td>{{ $s['asesor'] }}</td>
                    <td class="text-right bold">{{ $s['total'] }}</td>
                </tr>
                @empty
                <tr class="empty-row"><td colspan="2">Sin seguimientos en este período</td></tr>
                @endforelse
                <tr style="border-top:2px solid #e2e8f0;">
                    <td class="bold">Total</td>
                    <td class="text-right bold">{{ $datos['seguimientos']['total_periodo'] }}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col">
        <div class="section">
            <div class="section-title">5. Expedientes sin movimiento (+{{ $datos['sin_movimiento']['umbral_dias'] }} días)</div>
            @if(count($datos['sin_movimiento']['lista']) > 0)
            <table>
                <tr><th>Folio</th><th>Cliente</th><th>Asesor</th></tr>
                @foreach($datos['sin_movimiento']['lista'] as $e)
                <tr>
                    <td>{{ $e['folio'] }}</td>
                    <td>{{ $e['cliente'] }}</td>
                    <td>{{ $e['asesor'] }}</td>
                </tr>
                @endforeach
            </table>
            @else
            <p style="color:#276749; font-size:9px; padding:8px 0;">✓ Todos los expedientes activos tienen seguimiento reciente.</p>
            @endif
        </div>
    </div>
</div>

{{-- ════ SECCIÓN 6: DOCUMENTOS PENDIENTES ════ --}}
<div class="section">
    <div class="section-title">6. Documentos pendientes por entregar</div>
    @if(count($datos['documentos']['lista']) > 0)
    <table>
        <tr><th>Folio</th><th>Cliente</th><th>Asesor</th><th class="text-right">Docs pendientes</th></tr>
        @foreach($datos['documentos']['lista'] as $d)
        <tr>
            <td>{{ $d['folio'] }}</td>
            <td>{{ $d['cliente'] }}</td>
            <td>{{ $d['asesor'] }}</td>
            <td class="text-right">
                <span class="badge badge-red">{{ $d['pendientes'] }} faltantes</span>
            </td>
        </tr>
        @endforeach
    </table>
    <p style="margin-top:6px; font-size:8.5px; color:#718096;">
        Total: <strong>{{ $datos['documentos']['expedientes_con_pendientes'] }}</strong> expedientes con
        <strong>{{ $datos['documentos']['documentos_total'] }}</strong> documentos pendientes en total.
    </p>
    @else
    <p style="color:#276749; font-size:9px; padding:8px 0;">✓ No hay documentos pendientes en expedientes activos.</p>
    @endif
</div>

{{-- ════ SECCIÓN 7: ACTIVIDAD POR ASESOR ════ --}}
<div class="section">
    <div class="section-title">7. Actividad por asesor — período</div>
    @if(count($datos['por_asesor']) > 0)
    <table>
        <tr>
            <th>Asesor</th>
            <th class="text-right">Exp. abiertos</th>
            <th class="text-right">Exp. cerrados</th>
            <th class="text-right">Seguimientos</th>
            <th class="text-right">Prospectos</th>
            <th class="text-right">Comisiones</th>
        </tr>
        @foreach($datos['por_asesor'] as $a)
        <tr>
            <td class="bold">{{ $a['asesor'] }}</td>
            <td class="text-right">{{ $a['expedientes_abiertos'] }}</td>
            <td class="text-right" style="{{ $a['expedientes_cerrados'] > 0 ? 'color:#276749;font-weight:bold' : '' }}">
                {{ $a['expedientes_cerrados'] }}
            </td>
            <td class="text-right">{{ $a['seguimientos'] }}</td>
            <td class="text-right">{{ $a['prospectos'] }}</td>
            <td class="text-right money" style="{{ $a['comisiones_monto'] > 0 ? 'color:#276749;font-weight:bold' : '' }}">
                $ {{ number_format($a['comisiones_monto'], 2) }}
            </td>
        </tr>
        @endforeach
    </table>
    @else
    <p style="color:#a0aec0; font-style:italic; font-size:9px;">Sin asesores activos registrados.</p>
    @endif
</div>

{{-- ════ EXPEDIENTES CERRADOS EN EL PERÍODO ════ --}}
@if(count($datos['expedientes']['lista_cerrados']) > 0)
<div class="section">
    <div class="section-title">Detalle — Expedientes cerrados en el período</div>
    <table>
        <tr><th>Folio</th><th>Cliente</th><th>Asesor</th><th class="text-right">Honorarios</th></tr>
        @foreach($datos['expedientes']['lista_cerrados'] as $e)
        <tr>
            <td>{{ $e['folio'] }}</td>
            <td>{{ $e['cliente'] }}</td>
            <td>{{ $e['asesor'] }}</td>
            <td class="text-right money bold" style="color:#276749;">
                {{ $e['monto'] ? '$ ' . number_format($e['monto'], 2) : '—' }}
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endif

{{-- ════ FOOTER ════ --}}
<div class="footer">
    {{ config('app.name') }} · Reporte generado automáticamente el {{ $datos['generado_en']->isoFormat('D [de] MMMM [de] YYYY [a las] H:mm') }} ·
    Este documento es confidencial y de uso interno.
</div>

</body>
</html>
