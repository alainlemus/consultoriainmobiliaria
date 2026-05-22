@extends('emails.layouts.base')
@section('title', '¿Cómo fue tu experiencia?')
@section('alert', '⭐ Cuéntanos tu experiencia')
@section('alert-class', 'gold')
@php $preheader = 'Tu opinión nos ayuda a seguir mejorando. Solo toma un momento.'; @endphp

@section('content')
    <h2>Hola, {{ $nombreCliente }}.</h2>
    <p>
        Gracias por confiar en <strong>{{ $nombreEmpresa }}</strong> para gestionar tu trámite inmobiliario.
    </p>
    <p>
        Tu opinión es muy valiosa para nosotros y ayuda a que otras familias conozcan nuestro servicio.
        ¿Nos regalas un momento para contarnos cómo fue tu experiencia?
    </p>

    <div class="cta">
        <a href="{{ $link }}">Dejar mi testimonio</a>
    </div>

    <div class="notice">
        Este enlace es <strong>personal e intransferible</strong>, solo funciona una vez y estará
        disponible durante <strong>{{ $expiraDias }} días</strong>.
    </div>

    <hr class="divider">

    <p class="small">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
    <p style="font-size:13px; word-break:break-all; background:#f5f0e8; padding:10px 14px; border-radius:2px; font-family:monospace;">{{ $link }}</p>

    <p class="small center">
        Si no realizaste ningún trámite con nosotros o crees que este correo es un error,
        puedes ignorarlo con total seguridad.
    </p>
@endsection
