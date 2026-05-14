<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        /* ── HEADER ─────────────────────────────────────────────────── */
        .header {
            background: #1a1a1a;
            padding: 16px 24px 0 24px;
        }
        .header-top { display: table; width: 100%; }
        .header-logo { display: table-cell; vertical-align: middle; width: 90px; }
        .header-logo img { width: 72px; height: auto; }
        .header-text { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .header-empresa {
            font-size: 13pt;
            font-weight: bold;
            color: #d4af37;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .header-slogan { font-size: 8pt; color: #a0936a; margin-top: 2px; }
        .header-meta {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 180px;
        }
        .header-meta .periodo {
            font-size: 8pt;
            color: #d4af37;
        }
        .header-meta .fecha {
            font-size: 7pt;
            color: #666;
            margin-top: 2px;
        }
        .header-divider {
            height: 4px;
            background: linear-gradient(to right, #d4af37, #9b2335, #d4af37);
            margin-top: 12px;
        }
        .doc-titulo-bar {
            background: #9b2335;
            padding: 6px 24px;
            text-align: center;
        }
        .doc-titulo-bar span {
            font-size: 10pt;
            font-weight: bold;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* ── SECTION TITLES ─────────────────────────────────────────── */
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #9b2335;
            border-bottom: 1.5px solid #d4af37;
            padding-bottom: 3px;
            margin: 14px 0 7px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ── FLUJO DE DINERO ─────────────────────────────────────────── */
        .flujo-box { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .flujo-box td { padding: 4px 8px; font-size: 9.5px; }
        .flujo-row { border-bottom: 1px solid #ede8db; }
        .flujo-row td:first-child { font-weight: bold; color: #2a2a2a; width: 58%; }
        .flujo-row td:last-child  { text-align: right; font-weight: bold; }
        .flujo-row.ingreso { background: #fdf9ee; }
        .flujo-row.ingreso td:last-child { color: #2d6a4f; }
        .flujo-row.sub-ingreso { background: #f0fdf4; }
        .flujo-row.sub-ingreso td:first-child { padding-left: 20px; color: #2d6a4f; font-weight: normal; }
        .flujo-row.sub-ingreso td:last-child { color: #96760f; }
        .flujo-row.egreso { background: #fff5f5; }
        .flujo-row.egreso td:last-child { color: #9b2335; }
        .flujo-row.pendiente { background: #fffbeb; }
        .flujo-row.pendiente td:last-child { color: #96760f; }
        .flujo-neto {
            background: #1a1a1a;
        }
        .flujo-neto td {
            font-size: 11px;
            font-weight: bold;
            padding: 7px 8px;
            color: #d4af37;
        }
        .flujo-neto td:last-child { text-align: right; }
        .flujo-neto.positivo td:last-child { color: #4ade80; }
        .flujo-neto.negativo td:last-child { color: #f87171; }

        /* ── KPI CARDS ───────────────────────────────────────────────── */
        .kpi-grid { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .kpi-grid td { width: 25%; padding: 3px; vertical-align: top; }
        .kpi-card {
            border: 1px solid #ede8db;
            border-radius: 4px;
            padding: 7px 10px;
            text-align: center;
            background: #faf8f3;
        }
        .kpi-card .kpi-label {
            font-size: 7px;
            color: #7a5f0e;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .kpi-card .kpi-value {
            font-size: 13px;
            font-weight: bold;
            color: #1a1a1a;
            margin-top: 3px;
        }
        .kpi-card .kpi-sub { font-size: 7px; color: #a0936a; margin-top: 2px; }

        .kpi-card.gold   { background: #fdf9ee; border-color: #d4af37; }
        .kpi-card.gold   .kpi-value { color: #96760f; }
        .kpi-card.green  { background: #f0fdf4; border-color: #86efac; }
        .kpi-card.green  .kpi-value { color: #2d6a4f; }
        .kpi-card.red    { background: #fff5f5; border-color: #fca5a5; }
        .kpi-card.red    .kpi-value { color: #9b2335; }
        .kpi-card.amber  { background: #fffbeb; border-color: #fcd34d; }
        .kpi-card.amber  .kpi-value { color: #92400e; }
        .kpi-card.dark   { background: #2a2a2a; border-color: #3a3a3a; }
        .kpi-card.dark   .kpi-label { color: #a0936a; }
        .kpi-card.dark   .kpi-value { color: #d4af37; }
        .kpi-card.dark   .kpi-sub   { color: #666; }

        /* ── TABLES ──────────────────────────────────────────────────── */
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8.5px; }
        table.data th {
            background: #1a1a1a;
            color: #d4af37;
            padding: 5px 6px;
            text-align: left;
            font-weight: bold;
            letter-spacing: 0.05em;
            font-size: 8px;
            text-transform: uppercase;
        }
        table.data td { padding: 4px 6px; border-bottom: 1px solid #ede8db; }
        table.data tr:nth-child(even) td { background: #faf8f3; }

        /* ── FOOTER ──────────────────────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 0.6cm;
            left: 0;
            right: 0;
            padding: 0 24px;
        }
        .footer-inner {
            border-top: 1px solid #d4af37;
            padding-top: 4px;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; font-size: 7px; color: #96760f; }
        .footer-right { display: table-cell; font-size: 7px; color: #9b2335; text-align: right; }

        .page-break { page-break-after: always; }
        .text-green  { color: #2d6a4f; }
        .text-red    { color: #9b2335; }
        .text-gold   { color: #96760f; }
        .text-bold   { font-weight: bold; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

@php
    $siteName = \App\Models\Configuracion::get('site_name') ?? 'Consultoría Inmobiliaria';
    $logoPath = \App\Models\Configuracion::get('logo');
    $logoAbs  = $logoPath ? storage_path('app/public/' . $logoPath) : null;
@endphp

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- PÁGINA 1: Resumen Ejecutivo                                    --}}
{{-- ══════════════════════════════════════════════════════════════ --}}

{{-- HEADER --}}
<div class="header">
    <div class="header-top">
        <div class="header-logo">
            @if($logoAbs && file_exists($logoAbs))
                <img src="{{ $logoAbs }}" alt="Logo">
            @endif
        </div>
        <div class="header-text">
            <div class="header-empresa">{{ $siteName }}</div>
            <div class="header-slogan">Gestión de trámites hipotecarios y patrimoniales</div>
        </div>
        <div class="header-meta">
            <div class="periodo">{{ $periodo }}</div>
            <div class="fecha">Generado el {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>
    <div class="header-divider"></div>
</div>
<div class="doc-titulo-bar">
    <span>Reporte de KPIs — Resumen Ejecutivo</span>
</div>

<div style="padding: 4px 24px 0 24px;">

{{-- ── Flujo de Dinero ── --}}
<div class="section-title">Flujo de Dinero</div>
<table class="flujo-box">
    <tr class="flujo-row ingreso">
        <td>Ingresos Cobrados (honorarios pagados)</td>
        <td>${{ number_format($ingresosCobrados, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row sub-ingreso">
        <td>Honorarios por cobrar (cerrados sin pagar)</td>
        <td>${{ number_format($honPendientes, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row egreso">
        <td>Egresos: Comisiones Pagadas a Asesores</td>
        <td>- ${{ number_format($comPag, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row egreso">
        <td>Egresos: Gastos Financiados a Trámites</td>
        <td>- ${{ number_format($gastosPagados, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row pendiente">
        <td>Comisiones Pendientes de Pago</td>
        <td>${{ number_format($comPend, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-neto {{ $flujoNeto >= 0 ? 'positivo' : 'negativo' }}">
        <td>FLUJO NETO (Cobrado − Egresos Reales)</td>
        <td>{{ $flujoNeto >= 0 ? '+' : '' }}${{ number_format($flujoNeto, 2, '.', ',') }}
            &nbsp;<span style="font-size:8px; color:#a0936a;">(Margen {{ $margen }}%)</span>
        </td>
    </tr>
</table>

{{-- ── KPIs de Expedientes ── --}}
<div class="section-title">Expedientes</div>
<table class="kpi-grid">
    <tr>
        <td><div class="kpi-card gold"><div class="kpi-label">Abiertos</div><div class="kpi-value">{{ $abiertos }}</div><div class="kpi-sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card green"><div class="kpi-label">Cerrados</div><div class="kpi-value">{{ $cerrados }}</div><div class="kpi-sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card red"><div class="kpi-label">Cancelados</div><div class="kpi-value">{{ $cancelados }}</div><div class="kpi-sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card dark"><div class="kpi-label">Activos Hoy</div><div class="kpi-value">{{ $activos }}</div><div class="kpi-sub">En proceso / firmados</div></div></td>
    </tr>
    <tr>
        <td><div class="kpi-card amber"><div class="kpi-label">Tasa de Cierre</div><div class="kpi-value">{{ $tasaCierre }}%</div></div></td>
        <td><div class="kpi-card gold"><div class="kpi-label">Ticket Promedio</div><div class="kpi-value">${{ number_format($ticket, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card"><div class="kpi-label">Prospectos Captados</div><div class="kpi-value">{{ $prospectos }}</div><div class="kpi-sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card {{ $prospNuevos > 0 ? 'amber' : 'green' }}"><div class="kpi-label">Sin Atender</div><div class="kpi-value">{{ $prospNuevos }}</div></div></td>
    </tr>
</table>

{{-- ── KPIs de Honorarios ── --}}
<div class="section-title">Honorarios y Comisiones</div>
<table class="kpi-grid">
    <tr>
        <td><div class="kpi-card gold"><div class="kpi-label">Total Generado</div><div class="kpi-value">${{ number_format($honTotal, 0, '.', ',') }}</div><div class="kpi-sub">Exp. cerrados</div></div></td>
        <td><div class="kpi-card green"><div class="kpi-label">Cobrados</div><div class="kpi-value">${{ number_format($ingresosCobrados, 0, '.', ',') }}</div><div class="kpi-sub">Confirmados</div></div></td>
        <td><div class="kpi-card amber"><div class="kpi-label">Por Cobrar</div><div class="kpi-value">${{ number_format($honPendientes, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card"><div class="kpi-label">Comisiones Generadas</div><div class="kpi-value">${{ number_format($comGen, 0, '.', ',') }}</div></div></td>
    </tr>
    <tr>
        <td><div class="kpi-card green"><div class="kpi-label">Comisiones Pagadas</div><div class="kpi-value">${{ number_format($comPag, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card red"><div class="kpi-label">Comisiones Pendientes</div><div class="kpi-value">${{ number_format($comPend, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card red"><div class="kpi-label">Gastos Financiados</div><div class="kpi-value">${{ number_format($gastosPagados, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card dark"><div class="kpi-label">Total Egresos Reales</div><div class="kpi-value">${{ number_format($egresoTotal, 0, '.', ',') }}</div></div></td>
    </tr>
</table>

{{-- ── Resumen Mensual (solo vista anual) ── --}}
@if(!$mes)
<div class="section-title" style="margin-top:12px;">Resumen Mensual — {{ $anio }}</div>
<table class="data">
    <thead>
        <tr>
            <th>Mes</th>
            <th>Abiertos</th>
            <th>Cerrados</th>
            <th>Cancelados</th>
            <th>Ingresos Cobrados</th>
            <th>Comisiones Pagadas</th>
            <th>Gastos Financiados</th>
            <th class="text-right">Flujo Neto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($mensual as $fila)
        @php $neto = $fila['honorarios'] - $fila['comisiones'] - $fila['gastos']; @endphp
        <tr>
            <td class="text-bold">{{ $fila['mes'] }}</td>
            <td>{{ $fila['abiertos'] }}</td>
            <td>{{ $fila['cerrados'] }}</td>
            <td>{{ $fila['cancelados'] }}</td>
            <td class="text-green">${{ number_format($fila['honorarios'], 0, '.', ',') }}</td>
            <td class="text-red">${{ number_format($fila['comisiones'], 0, '.', ',') }}</td>
            <td class="text-red">${{ number_format($fila['gastos'], 0, '.', ',') }}</td>
            <td class="text-bold text-right {{ $neto >= 0 ? 'text-green' : 'text-red' }}">
                {{ $neto >= 0 ? '+' : '' }}${{ number_format($neto, 0, '.', ',') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</div>{{-- /padding --}}

<div class="footer">
    <div class="footer-inner">
        <div class="footer-left">{{ $siteName }} &bull; Reporte generado el {{ now()->format('d/m/Y H:i') }}</div>
        <div class="footer-right">{{ $periodo }} &bull; Página 1</div>
    </div>
</div>

<div class="page-break"></div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- PÁGINA 2: Detalle de Expedientes y Comisiones                 --}}
{{-- ══════════════════════════════════════════════════════════════ --}}

<div class="header">
    <div class="header-top">
        <div class="header-logo">
            @if($logoAbs && file_exists($logoAbs))
                <img src="{{ $logoAbs }}" alt="Logo">
            @endif
        </div>
        <div class="header-text">
            <div class="header-empresa">{{ $siteName }}</div>
            <div class="header-slogan">Gestión de trámites hipotecarios y patrimoniales</div>
        </div>
        <div class="header-meta">
            <div class="periodo">{{ $periodo }}</div>
            <div class="fecha">Generado el {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>
    <div class="header-divider"></div>
</div>
<div class="doc-titulo-bar">
    <span>Detalle de Expedientes y Comisiones</span>
</div>

<div style="padding: 4px 24px 0 24px;">

{{-- ── Expedientes ── --}}
<div class="section-title">Expedientes del Periodo</div>
@if($expedientes->count())
<table class="data">
    <thead>
        <tr>
            <th>Folio</th>
            <th>Acreditado</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Asesor</th>
            <th class="text-right">Honorarios</th>
            <th class="text-center">Cobrado</th>
            <th>Apertura</th>
            <th>Cierre</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expedientes as $e)
        <tr>
            <td class="text-bold" style="color:#96760f;">{{ $e->folio }}</td>
            <td>{{ $e->acreditado_nombre }}</td>
            <td>{{ $e->tipoTramite?->nombre }}</td>
            <td style="color:{{ match($e->estado) {
                'cerrado'    => '#2d6a4f',
                'cancelado'  => '#9b2335',
                'aprobado'   => '#96760f',
                'firmado'    => '#1a1a1a',
                default      => '#2a2a2a',
            } }}; font-weight:bold;">
                {{ ucfirst(str_replace('_', ' ', $e->estado)) }}
            </td>
            <td>{{ $e->asesor?->name }}</td>
            <td class="text-right text-bold">${{ number_format($e->honorarios_monto, 0, '.', ',') }}</td>
            <td class="text-center text-bold" style="color:{{ $e->honorarios_pagados ? '#2d6a4f' : '#92400e' }}">
                {{ $e->honorarios_pagados ? 'Sí' : 'No' }}
            </td>
            <td>{{ $e->fecha_apertura?->format('d/m/Y') }}</td>
            <td>{{ $e->fecha_cierre?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#666; font-style:italic; padding:8px 0;">Sin expedientes en el periodo.</p>
@endif

{{-- ── Comisiones ── --}}
@if($comisiones->count())
<div class="section-title" style="margin-top:14px;">Comisiones del Periodo</div>
<table class="data">
    <thead>
        <tr>
            <th>Expediente</th>
            <th>Asesor</th>
            <th class="text-right">Monto Base</th>
            <th class="text-center">%</th>
            <th class="text-right">Comisión</th>
            <th>Estado</th>
            <th>Generada</th>
            <th>Pagada</th>
        </tr>
    </thead>
    <tbody>
        @foreach($comisiones as $c)
        <tr>
            <td class="text-bold" style="color:#96760f;">{{ $c->expediente?->folio }}</td>
            <td>{{ $c->asesor?->name }}</td>
            <td class="text-right">${{ number_format($c->monto_base, 0, '.', ',') }}</td>
            <td class="text-center">{{ $c->porcentaje_comision }}%</td>
            <td class="text-right text-bold">${{ number_format($c->monto_comision, 0, '.', ',') }}</td>
            <td class="text-bold" style="color:{{ match($c->estado) {
                'pagada'   => '#2d6a4f',
                'aprobada' => '#92400e',
                default    => '#9b2335',
            } }};">
                {{ ucfirst($c->estado) }}
            </td>
            <td>{{ $c->fecha_generacion?->format('d/m/Y') }}</td>
            <td>{{ $c->fecha_pago?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</div>{{-- /padding --}}

<div class="footer">
    <div class="footer-inner">
        <div class="footer-left">{{ $siteName }} &bull; Reporte generado el {{ now()->format('d/m/Y H:i') }}</div>
        <div class="footer-right">{{ $periodo }} &bull; Página 2</div>
    </div>
</div>

</body>
</html>
