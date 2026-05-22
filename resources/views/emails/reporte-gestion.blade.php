@extends('emails.layouts.base')
@section('title', 'Reporte ' . ucfirst($tipo) . ' de Gestión')
@section('alert', '📊 Reporte ' . ucfirst($tipo) . ' de Gestión')
@section('alert-class', 'blue')
@php $preheader = 'Resumen de actividad del período: ' . $periodo; @endphp

@section('content')
    <h2>Reporte {{ ucfirst($tipo) }}</h2>
    <p>
        <span class="badge">{{ $periodo }}</span>
    </p>
    <p>
        A continuación encontrarás el resumen de actividad del período.
        El reporte completo con todos los detalles se encuentra adjunto en PDF.
    </p>

    {{-- Expedientes --}}
    <h3>📁 Expedientes</h3>
    <table class="metrics-table">
        <tr>
            <th>Métrica</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Abiertos en el período</td>
            <td>{{ $datos['expedientes']['abiertos_periodo'] }}</td>
        </tr>
        <tr>
            <td>Cerrados en el período</td>
            <td>{{ $datos['expedientes']['cerrados_periodo'] }}</td>
        </tr>
        <tr>
            <td>Activos en total</td>
            <td>{{ $datos['expedientes']['activos_total'] }}</td>
        </tr>
    </table>

    {{-- Prospectos --}}
    <h3>👤 Prospectos</h3>
    <table class="metrics-table">
        <tr>
            <th>Métrica</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Nuevos en el período</td>
            <td>{{ $datos['prospectos']['nuevos_periodo'] }}</td>
        </tr>
        <tr>
            <td>Convertidos a expediente</td>
            <td>{{ $datos['prospectos']['convertidos_periodo'] }}</td>
        </tr>
        <tr>
            <td>Pendientes de cierre</td>
            <td>{{ $datos['prospectos']['pendientes_cierre'] }}</td>
        </tr>
    </table>

    {{-- Comisiones --}}
    <h3>💰 Comisiones</h3>
    <table class="metrics-table">
        <tr>
            <th>Métrica</th>
            <th>Monto</th>
        </tr>
        <tr>
            <td>Generadas en el período</td>
            <td>$ {{ number_format($datos['comisiones']['generadas_monto'], 2) }}</td>
        </tr>
        <tr>
            <td>Pagadas en el período</td>
            <td>$ {{ number_format($datos['comisiones']['pagadas_monto'], 2) }}</td>
        </tr>
        <tr>
            <td>Pendientes de pago</td>
            <td>$ {{ number_format($datos['comisiones']['pendientes_monto'], 2) }}</td>
        </tr>
    </table>

    {{-- Actividad --}}
    <h3>📋 Actividad</h3>
    <table class="metrics-table">
        <tr>
            <th>Métrica</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Seguimientos registrados</td>
            <td>{{ $datos['seguimientos']['total_periodo'] }}</td>
        </tr>
        <tr>
            <td>Expedientes sin movimiento (+{{ $datos['sin_movimiento']['umbral_dias'] }} días)</td>
            <td>{{ $datos['sin_movimiento']['total'] }}</td>
        </tr>
        <tr>
            <td>Expedientes con documentos pendientes</td>
            <td>{{ $datos['documentos']['expedientes_con_pendientes'] }}</td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Alerta expedientes sin movimiento --}}
    @if($datos['sin_movimiento']['total'] > 0)
    <div class="notice warning">
        ⚠️ <strong>Atención:</strong> Hay <strong>{{ $datos['sin_movimiento']['total'] }}</strong>
        expediente(s) sin movimiento en los últimos {{ $datos['sin_movimiento']['umbral_dias'] }} días.
        Consulta el PDF adjunto para el detalle.
    </div>
    @else
    <div class="notice success">
        ✅ Todos los expedientes activos tienen seguimiento reciente.
    </div>
    @endif

    <div class="cta">
        <a href="{{ config('app.url') }}/admin">Ver panel de administración</a>
    </div>

    <p class="small center">
        El reporte completo con todas las secciones y detalles se encuentra adjunto en PDF.
    </p>
@endsection
