@extends('emails.layouts.base')
@section('title', 'Mensaje de Consultoría Inmobiliaria')
@php $preheader = $nombreContacto ? 'Hola ' . $nombreContacto . ', tenemos un mensaje para ti.' : 'Mensaje de Consultoría Inmobiliaria.'; @endphp

@section('content')
    @if($nombreContacto)
    <h2>Hola, {{ $nombreContacto }}.</h2>
    @endif

    <div style="font-size:15px; line-height:1.8; color:#444;">
        {!! nl2br(e($cuerpoHtml)) !!}
    </div>

    <hr class="divider">

    <div class="cta">
        <a href="{{ config('app.url') }}">Visitar sitio web</a>
    </div>

    <p class="small center">
        Este mensaje fue enviado por {{ config('app.name') }}.
        Si crees que lo recibiste por error, puedes ignorarlo.
    </p>
@endsection
