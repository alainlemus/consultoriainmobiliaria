<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Gestión</title>
    {{-- Mismo lenguaje visual que el sitio y los correos: oscuro + dorado, títulos serif, tarjetas suaves.
         DomPDF no soporta flexbox: todo el maquetado es con tablas. --}}
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #4b4441; background: #ffffff; }
        .serif { font-family: 'DejaVu Serif', Georgia, serif; }

        /* ── Cabecera ── */
        .stripe { height: 5px; background: #cd9d36; border-bottom: 2px solid #9c1006; }
        .header { background: #222121; padding: 26px 40px 24px; }
        .header table { width: 100%; border-collapse: collapse; }
        .brand-name { font-family: 'DejaVu Serif', Georgia, serif; font-size: 18px; font-weight: bold; color: #cd9d36; letter-spacing: 3px; text-transform: uppercase; }
        .brand-sub  { font-size: 7.5px; color: #b8b2ac; letter-spacing: 4px; text-transform: uppercase; margin-top: 3px; }
        .brand-tag  { font-size: 7.5px; color: #debb5a; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 8px; }
        .pill { display: inline-block; background: #cd9d36; color: #222121; padding: 5px 14px; border-radius: 12px; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .period-label { color: #a59e98; font-size: 7.5px; letter-spacing: 1px; text-transform: uppercase; margin-top: 10px; }
        .period-dates { color: #ffffff; font-size: 11px; margin-top: 3px; }

        .content { padding: 26px 40px 20px; }
        .title { font-family: 'DejaVu Serif', Georgia, serif; font-size: 24px; font-weight: bold; color: #222121; letter-spacing: -0.5px; margin-bottom: 4px; }
        .lead { font-size: 10px; color: #888080; margin-bottom: 18px; }

        /* ── KPIs ── */
        .kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 22px; }
        .kpi { background: #faf8f3; border: 1px solid #ede8db; border-radius: 14px; padding: 14px 10px 12px; text-align: center; width: 25%; }
        .kpi-value { font-family: 'DejaVu Serif', Georgia, serif; font-size: 26px; font-weight: bold; color: #222121; line-height: 1; }
        .kpi-label { font-size: 7.5px; color: #9f7320; margin-top: 6px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .kpi.alert .kpi-value { color: #810f00; }
        .kpi.ok .kpi-value { color: #1f5a3a; }

        /* ── Secciones ── */
        .section { margin-bottom: 22px; }
        .section-title { font-family: 'DejaVu Serif', Georgia, serif; font-size: 13px; font-weight: bold; color: #222121; border-left: 4px solid #cd9d36; padding-left: 10px; margin-bottom: 10px; }

        /* ── Tablas ── */
        table.data { width: 100%; border-collapse: collapse; border: 1px solid #ede8db; }
        table.data th { background: #222121; color: #debb5a; padding: 8px 12px; text-align: left; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        table.data td { padding: 8px 12px; border-bottom: 1px solid #ede8db; font-size: 9.5px; color: #4b4441; }
        table.data tr:last-child td { border-bottom: none; }
        table.data tr.alt td { background: #faf8f3; }
        .text-right, table.data th.text-right { text-align: right; }
        .bold { font-weight: bold; color: #222121; }
        .empty { color: #a59e98; font-style: italic; font-size: 9px; padding: 8px 0; }
        .note { margin-top: 8px; font-size: 8.5px; color: #888080; }
        .note strong { color: #222121; }

        /* ── Pie ── */
        .footer { background: #222121; padding: 18px 40px; text-align: center; margin-top: 8px; }
        .footer .tagline { font-family: 'DejaVu Serif', Georgia, serif; font-style: italic; font-size: 11px; color: #cd9d36; }
        .footer .meta { font-size: 7.5px; color: #8f8983; margin-top: 8px; line-height: 1.6; }
    </style>
</head>
<body>

@php $logo = logo_data_uri(); @endphp

{{-- ════ CABECERA ════ --}}
<div class="stripe"></div>
<div class="header">
    <table>
        <tr>
            <td style="width: 62%; vertical-align: middle;">
                <table>
                    <tr>
                        @if($logo)
                        <td style="width: 58px; vertical-align: middle;"><img src="{{ $logo }}" alt="" style="height: 50px; width: auto;"></td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="brand-name">Consultoría</div>
                            <div class="brand-sub">Inmobiliaria</div>
                            <div class="brand-tag">FOVISSSTE · INFONAVIT · Avalúos</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 38%; text-align: right; vertical-align: middle;">
                <span class="pill">Reporte {{ $datos['tipo'] }}</span>
                <div class="period-label">Período del reporte</div>
                <div class="period-dates">
                    @if($datos['tipo'] === 'diario')
                        {{ $datos['desde']->isoFormat('dddd D [de] MMMM, YYYY') }}
                    @else
                        {{ $datos['desde']->isoFormat('D MMM YYYY') }} — {{ $datos['hasta']->isoFormat('D MMM YYYY') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="content">

    <div class="title">Resumen de gestión</div>
    <div class="lead">Actividad del período · generado el {{ $datos['generado_en']->format('d/m/Y H:i') }}</div>

    {{-- ════ KPIs ════ --}}
    <table class="kpis">
        <tr>
            <td class="kpi">
                <div class="kpi-value">{{ $datos['prospectos']['nuevos_periodo'] }}</div>
                <div class="kpi-label">Prospectos nuevos</div>
            </td>
            <td class="kpi ok">
                <div class="kpi-value">{{ $datos['documentos']['subidos_periodo'] }}</div>
                <div class="kpi-label">Documentos subidos</div>
            </td>
            <td class="kpi {{ $datos['documentos']['por_capturar'] > 0 ? 'alert' : '' }}">
                <div class="kpi-value">{{ $datos['documentos']['por_capturar'] }}</div>
                <div class="kpi-label">Por capturar</div>
            </td>
            <td class="kpi">
                <div class="kpi-value">{{ $datos['expedientes']['abiertos_periodo'] }}</div>
                <div class="kpi-label">Expedientes abiertos</div>
            </td>
        </tr>
    </table>

    {{-- ════ 1. PROSPECTOS ════ --}}
    <div class="section">
        <div class="section-title">1. Prospectos registrados en el período</div>
        @if(count($datos['prospectos']['lista_nuevos']) > 0)
        <table class="data">
            <tr><th>Prospecto</th><th>Asesor</th></tr>
            @foreach($datos['prospectos']['lista_nuevos'] as $p)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td>{{ $p['nombre'] }}</td>
                <td>{{ $p['asesor'] }}</td>
            </tr>
            @endforeach
        </table>
        @else
        <p class="empty">Sin prospectos nuevos en el período.</p>
        @endif
        <p class="note">Prospectos activos en total: <strong>{{ $datos['prospectos']['activos_total'] }}</strong>.</p>
    </div>

    {{-- ════ 2. POR ASESOR ════ --}}
    <div class="section">
        <div class="section-title">2. Actividad por asesor — período</div>
        @if(count($datos['por_asesor']) > 0)
        <table class="data">
            <tr>
                <th>Asesor</th>
                <th class="text-right">Prospectos nuevos</th>
                <th class="text-right">Expedientes abiertos</th>
                <th class="text-right">Documentos subidos</th>
            </tr>
            @foreach($datos['por_asesor'] as $a)
            <tr class="{{ $loop->even ? 'alt' : '' }}">
                <td class="bold">{{ $a['asesor'] }}</td>
                <td class="text-right">{{ $a['prospectos'] }}</td>
                <td class="text-right">{{ $a['expedientes'] }}</td>
                <td class="text-right" style="{{ $a['documentos'] > 0 ? 'color:#1f5a3a; font-weight:bold;' : '' }}">{{ $a['documentos'] }}</td>
            </tr>
            @endforeach
        </table>
        @else
        <p class="empty">Sin asesores activos registrados.</p>
        @endif
    </div>

</div>

{{-- ════ PIE ════ --}}
<div class="footer">
    <div class="tagline">Tu patrimonio, nuestra prioridad.</div>
    <div class="meta">
        Reporte generado automáticamente el {{ $datos['generado_en']->isoFormat('D [de] MMMM [de] YYYY [a las] H:mm') }}.
        Este documento es confidencial y de uso interno.
    </div>
</div>

</body>
</html>
