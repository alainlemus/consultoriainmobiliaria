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
        <div class="kpi-value">{{ $datos['prospectos']['nuevos_periodo'] }}</div>
        <div class="kpi-label">Prospectos nuevos</div>
    </div>
    <div class="kpi success">
        <div class="kpi-value">{{ $datos['documentos']['subidos_periodo'] }}</div>
        <div class="kpi-label">Documentos subidos</div>
    </div>
    <div class="kpi warning">
        <div class="kpi-value">{{ $datos['documentos']['por_capturar'] }}</div>
        <div class="kpi-label">Por capturar</div>
    </div>
    <div class="kpi warning">
        <div class="kpi-value">{{ $datos['prospectos']['sin_documentos'] }}</div>
        <div class="kpi-label">Prospectos sin documentos</div>
    </div>
</div>

{{-- ════ SECCIÓN 1: PROSPECTOS NUEVOS ════ --}}
<div class="section">
    <div class="section-title">1. Prospectos registrados en el período</div>
    @if(count($datos['prospectos']['lista_nuevos']) > 0)
    <table>
        <tr><th>Prospecto</th><th>Asesor</th><th class="text-right">Documentos</th></tr>
        @foreach($datos['prospectos']['lista_nuevos'] as $p)
        <tr>
            <td>{{ $p['nombre'] }}</td>
            <td>{{ $p['asesor'] }}</td>
            <td class="text-right">
                <span class="badge {{ $p['documentos'] > 0 ? 'badge-green' : 'badge-gray' }}">{{ $p['documentos'] }}</span>
            </td>
        </tr>
        @endforeach
    </table>
    @else
    <p style="color:#a0aec0; font-style:italic; font-size:9px;">Sin prospectos nuevos en el período.</p>
    @endif
    <p style="margin-top:6px; font-size:8.5px; color:#718096;">
        Prospectos activos en total: <strong>{{ $datos['prospectos']['activos_total'] }}</strong>.
    </p>
</div>

{{-- ════ SECCIÓN 2: ACTIVIDAD POR ASESOR ════ --}}
<div class="section">
    <div class="section-title">2. Actividad por asesor — período</div>
    @if(count($datos['por_asesor']) > 0)
    <table>
        <tr>
            <th>Asesor</th>
            <th class="text-right">Prospectos nuevos</th>
            <th class="text-right">Documentos subidos</th>
            <th class="text-right">Prospectos sin documentos</th>
        </tr>
        @foreach($datos['por_asesor'] as $a)
        <tr>
            <td class="bold">{{ $a['asesor'] }}</td>
            <td class="text-right">{{ $a['prospectos'] }}</td>
            <td class="text-right" style="{{ $a['documentos'] > 0 ? 'color:#276749;font-weight:bold' : '' }}">{{ $a['documentos'] }}</td>
            <td class="text-right">{{ $a['sin_documentos'] }}</td>
        </tr>
        @endforeach
    </table>
    @else
    <p style="color:#a0aec0; font-style:italic; font-size:9px;">Sin asesores activos registrados.</p>
    @endif
</div>

{{-- ════ FOOTER ════ --}}
<div class="footer">
    {{ config('app.name') }} · Reporte generado automáticamente el {{ $datos['generado_en']->isoFormat('D [de] MMMM [de] YYYY [a las] H:mm') }} ·
    Este documento es confidencial y de uso interno.
</div>

</body>
</html>
