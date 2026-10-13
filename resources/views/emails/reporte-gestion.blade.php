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

    {{-- Prospectos --}}
    <h3>👤 Prospectos</h3>
    <table class="metrics-table">
        <tr>
            <th>Métrica</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Registrados en el período</td>
            <td>{{ $datos['prospectos']['nuevos_periodo'] }}</td>
        </tr>
        <tr>
            <td>Activos en total</td>
            <td>{{ $datos['prospectos']['activos_total'] }}</td>
        </tr>
    </table>

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
            <td>Expedientes en total</td>
            <td>{{ $datos['expedientes']['total'] }}</td>
        </tr>
    </table>

    {{-- Documentos --}}
    <h3>📄 Documentos</h3>
    <table class="metrics-table">
        <tr>
            <th>Métrica</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Subidos en el período</td>
            <td>{{ $datos['documentos']['subidos_periodo'] }}</td>
        </tr>
        <tr>
            <td>Por capturar en el panel</td>
            <td>{{ $datos['documentos']['por_capturar'] }}</td>
        </tr>
    </table>

    <hr class="divider">

    @if($datos['documentos']['por_capturar'] > 0)
    <div class="notice warning">
        ⚠️ Hay <strong>{{ $datos['documentos']['por_capturar'] }}</strong> documento(s) subidos desde la app
        que todavía no se capturan en el panel.
    </div>
    @else
    <div class="notice success">
        ✅ Todos los documentos subidos ya están capturados.
    </div>
    @endif

    <div class="cta">
        <a href="{{ config('app.url') }}/admin">Ver panel de administración</a>
    </div>

    <p class="small center">
        El reporte completo con todas las secciones y detalles se encuentra adjunto en PDF.
    </p>
@endsection
