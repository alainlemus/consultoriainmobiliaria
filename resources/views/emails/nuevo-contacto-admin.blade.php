@extends('emails.layouts.base')
@section('title', 'Nuevo mensaje de contacto')
@section('alert', '📬 Nuevo mensaje de contacto recibido')
@section('alert-class', 'dark')
@php $preheader = 'Se recibió un nuevo mensaje a través del formulario de contacto.'; @endphp

@section('content')
    <p>Se ha recibido un nuevo mensaje a través del formulario de contacto del sitio web.</p>

    <div class="card">
        <table>
            <tr>
                <td>Nombre:</td>
                <td><strong>{{ $contacto->nombre }}</strong></td>
            </tr>
            <tr>
                <td>Teléfono:</td>
                <td>
                    <a href="tel:{{ $contacto->telefono }}" style="color:#8B1A1A; text-decoration:none;">
                        {{ $contacto->telefono }}
                    </a>
                </td>
            </tr>
            <tr>
                <td>Correo:</td>
                <td>
                    <a href="mailto:{{ $contacto->email }}" style="color:#8B1A1A; text-decoration:none;">
                        {{ $contacto->email }}
                    </a>
                </td>
            </tr>
            @if($contacto->servicio)
            <tr>
                <td>Servicio:</td>
                <td>{{ ucfirst(strtolower($contacto->servicio)) }}</td>
            </tr>
            @endif
            @if($contacto->curp)
            <tr>
                <td>CURP:</td>
                <td style="font-family:monospace; letter-spacing:2px; color:#8B1A1A; font-weight:bold;">{{ $contacto->curp }}</td>
            </tr>
            @endif
            <tr>
                <td>Fecha:</td>
                <td>{{ $contacto->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if($contacto->mensaje)
            <tr>
                <td>Mensaje:</td>
                <td>
                    <div style="background:#fff8f0; border:1px solid #e8d8c0; padding:12px 16px; border-radius:2px; font-size:14px; line-height:1.7; white-space:pre-wrap;">{{ $contacto->mensaje }}</div>
                </td>
            </tr>
            @endif
        </table>
    </div>

    <div class="cta">
        <a href="{{ url('/admin/contactos') }}">Ver en el panel de administración</a>
    </div>

    <p class="small center">Este correo fue generado automáticamente por el sistema.</p>
@endsection
