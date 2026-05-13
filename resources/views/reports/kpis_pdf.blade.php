<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; background: #fff; }

        .header {
            background: #4f46e5;
            color: white;
            padding: 18px 24px;
            margin-bottom: 16px;
        }
        .header h1 { font-size: 18px; font-weight: bold; }
        .header p  { font-size: 11px; margin-top: 3px; opacity: 0.85; }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 3px;
            margin: 16px 0 8px;
        }

        /* KPI Cards */
        .kpi-grid { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .kpi-grid td { width: 25%; padding: 3px; vertical-align: top; }
        .kpi-card {
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 5px;
            padding: 8px 10px;
            text-align: center;
        }
        .kpi-card .label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.04em; }
        .kpi-card .value { font-size: 14px; font-weight: bold; color: #4f46e5; margin-top: 3px; }
        .kpi-card .sub   { font-size: 8px; color: #9ca3af; margin-top: 2px; }
        .kpi-card.green  { background: #f0fdf4; border-color: #bbf7d0; }
        .kpi-card.green .value { color: #16a34a; }
        .kpi-card.red    { background: #fef2f2; border-color: #fecaca; }
        .kpi-card.red .value  { color: #dc2626; }
        .kpi-card.yellow { background: #fffbeb; border-color: #fde68a; }
        .kpi-card.yellow .value { color: #d97706; }
        .kpi-card.purple { background: #faf5ff; border-color: #e9d5ff; }
        .kpi-card.purple .value { color: #7c3aed; }

        /* Resumen financiero destacado */
        .flujo-box {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        .flujo-box td { padding: 3px 6px; }
        .flujo-row { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        .flujo-row td:first-child { font-weight: bold; color: #374151; width: 55%; }
        .flujo-row td:last-child  { text-align: right; font-weight: bold; }
        .flujo-ingreso td:last-child { color: #16a34a; }
        .flujo-egreso  td:last-child { color: #dc2626; }
        .flujo-neto    { background: #ede9fe; }
        .flujo-neto td { font-size: 13px; font-weight: bold; padding: 6px 6px; }
        .flujo-neto td:first-child { color: #4f46e5; }
        .flujo-neto td:last-child  { text-align: right; }
        .flujo-neto.positivo td:last-child { color: #16a34a; }
        .flujo-neto.negativo td:last-child { color: #dc2626; }

        /* Tables */
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9.5px; }
        table.data th {
            background: #4f46e5;
            color: white;
            padding: 4px 6px;
            text-align: left;
            font-weight: bold;
        }
        table.data td { padding: 3px 6px; border-bottom: 1px solid #e5e7eb; }
        table.data tr:nth-child(even) td { background: #f9fafb; }

        .footer {
            margin-top: 20px;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- PÁGINA 1: Resumen Ejecutivo                               --}}
{{-- ══════════════════════════════════════════════════════════ --}}

<div class="header">
    <h1>Reporte de KPIs — {{ $periodo }}</h1>
    <p>Consultoría Inmobiliaria &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}</p>
</div>

{{-- ── Flujo de Dinero ── --}}
<div class="section-title">💰 Flujo de Dinero</div>
<table class="flujo-box">
    <tr class="flujo-row flujo-ingreso">
        <td>✅ Ingresos Cobrados (honorarios pagados)</td>
        <td>${{ number_format($ingresosCobrados, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row flujo-ingreso" style="background:#f0fdf4;">
        <td style="padding-left:16px; color:#16a34a;">↳ Honorarios por cobrar (cerrados sin pagar)</td>
        <td style="text-align:right; color:#d97706;">${{ number_format($honPendientes, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row flujo-egreso">
        <td>❌ Egresos: Comisiones Pagadas a Asesores</td>
        <td>- ${{ number_format($comPag, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row flujo-egreso">
        <td>❌ Egresos: Gastos Financiados a Trámites</td>
        <td>- ${{ number_format($gastosPagados, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-row" style="background:#fff3cd;">
        <td style="color:#d97706;">⏳ Comisiones Pendientes de Pago</td>
        <td style="text-align:right; color:#d97706;">${{ number_format($comPend, 2, '.', ',') }}</td>
    </tr>
    <tr class="flujo-neto {{ $flujoNeto >= 0 ? 'positivo' : 'negativo' }}">
        <td>💹 FLUJO NETO (Cobrado − Egresos Reales)</td>
        <td>{{ $flujoNeto >= 0 ? '+' : '' }}${{ number_format($flujoNeto, 2, '.', ',') }}
            &nbsp;<span style="font-size:10px;">(Margen {{ $margen }}%)</span>
        </td>
    </tr>
</table>

{{-- ── KPIs de Expedientes ── --}}
<div class="section-title">📂 Expedientes</div>
<table class="kpi-grid">
    <tr>
        <td><div class="kpi-card"><div class="label">Abiertos</div><div class="value">{{ $abiertos }}</div><div class="sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card green"><div class="label">Cerrados</div><div class="value">{{ $cerrados }}</div><div class="sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card red"><div class="label">Cancelados</div><div class="value">{{ $cancelados }}</div><div class="sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card"><div class="label">Activos Hoy</div><div class="value">{{ $activos }}</div><div class="sub">En proceso/firmados</div></div></td>
    </tr>
    <tr>
        <td><div class="kpi-card yellow"><div class="label">Tasa de Cierre</div><div class="value">{{ $tasaCierre }}%</div></div></td>
        <td><div class="kpi-card purple"><div class="label">Ticket Promedio</div><div class="value">${{ number_format($ticket, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card"><div class="label">Prospectos Captados</div><div class="value">{{ $prospectos }}</div><div class="sub">{{ $periodo }}</div></div></td>
        <td><div class="kpi-card {{ $prospNuevos > 0 ? 'yellow' : 'green' }}"><div class="label">Sin Atender</div><div class="value">{{ $prospNuevos }}</div></div></td>
    </tr>
</table>

{{-- ── KPIs de Honorarios ── --}}
<div class="section-title">💵 Honorarios</div>
<table class="kpi-grid">
    <tr>
        <td><div class="kpi-card green"><div class="label">Total Generado</div><div class="value">${{ number_format($honTotal, 0, '.', ',') }}</div><div class="sub">Exp. cerrados</div></div></td>
        <td><div class="kpi-card green"><div class="label">Cobrados</div><div class="value">${{ number_format($ingresosCobrados, 0, '.', ',') }}</div><div class="sub">Pagados confirmados</div></div></td>
        <td><div class="kpi-card yellow"><div class="label">Por Cobrar</div><div class="value">${{ number_format($honPendientes, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card"><div class="label">Comisiones Generadas</div><div class="value">${{ number_format($comGen, 0, '.', ',') }}</div></div></td>
    </tr>
    <tr>
        <td><div class="kpi-card green"><div class="label">Comisiones Pagadas</div><div class="value">${{ number_format($comPag, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card red"><div class="label">Comisiones Pendientes</div><div class="value">${{ number_format($comPend, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card red"><div class="label">Gastos Financiados</div><div class="value">${{ number_format($gastosPagados, 0, '.', ',') }}</div></div></td>
        <td><div class="kpi-card purple"><div class="label">Total Egresos Reales</div><div class="value">${{ number_format($egresoTotal, 0, '.', ',') }}</div></div></td>
    </tr>
</table>

{{-- ── Resumen Mensual (solo anual) ── --}}
@if(!$mes)
<div class="section-title" style="margin-top:14px;">📅 Resumen por Mes — {{ $anio }}</div>
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
            <th>Flujo Neto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($mensual as $fila)
        @php $neto = $fila['honorarios'] - $fila['comisiones'] - $fila['gastos']; @endphp
        <tr>
            <td><strong>{{ $fila['mes'] }}</strong></td>
            <td>{{ $fila['abiertos'] }}</td>
            <td>{{ $fila['cerrados'] }}</td>
            <td>{{ $fila['cancelados'] }}</td>
            <td style="color:#16a34a;">${{ number_format($fila['honorarios'], 0, '.', ',') }}</td>
            <td style="color:#dc2626;">${{ number_format($fila['comisiones'], 0, '.', ',') }}</td>
            <td style="color:#dc2626;">${{ number_format($fila['gastos'], 0, '.', ',') }}</td>
            <td style="color:{{ $neto >= 0 ? '#16a34a' : '#dc2626' }}; font-weight:bold;">
                {{ $neto >= 0 ? '+' : '' }}${{ number_format($neto, 0, '.', ',') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Consultoría Inmobiliaria &nbsp;·&nbsp; Reporte generado automáticamente &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}
</div>

<div class="page-break"></div>

{{-- ══════════════════════════════════════════════════════════ --}}
{{-- PÁGINA 2: Detalle de Expedientes                          --}}
{{-- ══════════════════════════════════════════════════════════ --}}

<div class="header">
    <h1>Expedientes — {{ $periodo }}</h1>
    <p>Consultoría Inmobiliaria &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="section-title">Detalle de Expedientes</div>
@if($expedientes->count())
<table class="data">
    <thead>
        <tr>
            <th>Folio</th>
            <th>Acreditado</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Asesor</th>
            <th>Honorarios</th>
            <th>Cobrado</th>
            <th>Apertura</th>
            <th>Cierre</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expedientes as $e)
        <tr>
            <td><strong>{{ $e->folio }}</strong></td>
            <td>{{ $e->acreditado_nombre }}</td>
            <td>{{ $e->tipoTramite?->nombre }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $e->estado)) }}</td>
            <td>{{ $e->asesor?->name }}</td>
            <td>${{ number_format($e->honorarios_monto, 0, '.', ',') }}</td>
            <td style="color:{{ $e->honorarios_pagados ? '#16a34a' : '#d97706' }}">
                {{ $e->honorarios_pagados ? 'Sí' : 'No' }}
            </td>
            <td>{{ $e->fecha_apertura?->format('d/m/Y') }}</td>
            <td>{{ $e->fecha_cierre?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#6b7280; font-style:italic;">Sin expedientes en el periodo.</p>
@endif

{{-- ── Comisiones ── --}}
@if($comisiones->count())
<div class="section-title" style="margin-top:16px;">Comisiones del Periodo</div>
<table class="data">
    <thead>
        <tr>
            <th>Expediente</th>
            <th>Asesor</th>
            <th>Monto Base</th>
            <th>%</th>
            <th>Comisión</th>
            <th>Estado</th>
            <th>Generada</th>
            <th>Pagada</th>
        </tr>
    </thead>
    <tbody>
        @foreach($comisiones as $c)
        <tr>
            <td><strong>{{ $c->expediente?->folio }}</strong></td>
            <td>{{ $c->asesor?->name }}</td>
            <td>${{ number_format($c->monto_base, 0, '.', ',') }}</td>
            <td>{{ $c->porcentaje_comision }}%</td>
            <td style="font-weight:bold;">${{ number_format($c->monto_comision, 0, '.', ',') }}</td>
            <td style="color:{{ $c->estado === 'pagada' ? '#16a34a' : ($c->estado === 'aprobada' ? '#d97706' : '#dc2626') }}">
                {{ ucfirst($c->estado) }}
            </td>
            <td>{{ $c->fecha_generacion?->format('d/m/Y') }}</td>
            <td>{{ $c->fecha_pago?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Consultoría Inmobiliaria &nbsp;·&nbsp; Reporte generado automáticamente &nbsp;·&nbsp; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
