@extends('emails.layouts.base')
@section('title', 'Recibimos tu mensaje')
@php $preheader = 'Confirmamos que recibimos tu solicitud. Te contactaremos pronto.'; @endphp

@section('content')
    <h2>Hola, {{ $contacto->nombre }}.</h2>
    <p>
        Recibimos tu mensaje correctamente. Uno de nuestros asesores revisará tu solicitud
        y se pondrá en contacto contigo a la brevedad, sin costo ni compromiso.
    </p>

    <div class="card">
        <table>
            <tr>
                <td>Nombre:</td>
                <td>{{ $contacto->nombre }}</td>
            </tr>
            <tr>
                <td>Teléfono:</td>
                <td>{{ $contacto->telefono }}</td>
            </tr>
            <tr>
                <td>Correo:</td>
                <td>{{ $contacto->email }}</td>
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
                <td style="font-family:monospace; letter-spacing:2px;">{{ $contacto->curp }}</td>
            </tr>
            @endif
            @if($contacto->mensaje)
            <tr>
                <td>Mensaje:</td>
                <td>{{ $contacto->mensaje }}</td>
            </tr>
            @endif
        </table>
    </div>

    <hr class="divider">

    <p>Si tienes alguna duda urgente, puedes contactarnos directamente por WhatsApp:</p>

    @if(setting('whatsapp_1'))
    <div class="cta">
        <a href="https://wa.me/{{ setting('whatsapp_1') }}"
           style="background:#25D366; color:#fff;">
            Escribir por WhatsApp
        </a>
    </div>
    @endif

    <p class="small center">Este correo es una confirmación automática. Por favor no respondas a este mensaje.</p>
@endsection
