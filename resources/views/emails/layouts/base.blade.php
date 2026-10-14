{{--
  Layout base para todos los correos del sistema — mismo lenguaje visual que el sitio público:
  cabecera oscura con dorado, tipografía grande y apretada, tarjetas redondeadas y botones tipo píldora.

  Uso:
    @extends('emails.layouts.base')
    @section('title', 'Asunto del correo')
    @section('alert', 'Texto de la etiqueta superior (opcional)')
    @section('alert-class', 'gold|dark|blue')
    @section('content') ... @endsection

  Variables opcionales:
    $preheader  — texto de previsualización (oculto, para clientes de correo)

  Clases disponibles dentro de @section('content'):
    h2, h3, p, p.small, p.center, .card (tabla de datos), .metrics-table, .notice(.warning|.success),
    .cta (botón principal), .divider, .badge
--}}
@php
    $logoPath = setting('logo');
    $logoUrl  = $logoPath ? asset('storage/' . $logoPath) : null;
    $correoContacto = setting_email_list('correo_contacto')[0] ?? null;
@endphp
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { margin:0; padding:0; background:#efe9dc; -webkit-text-size-adjust:100%; }
        body, td, p, a, li { font-family:'Helvetica Neue', Helvetica, Arial, sans-serif; }
        img { border:0; outline:none; text-decoration:none; }
        a { color:#9f7320; }

        .outer  { background:#efe9dc; padding:32px 12px; }
        .container { max-width:600px; width:100%; margin:0 auto; }
        .card-shell { text-align:left; background:#ffffff; border-radius:28px; overflow:hidden; box-shadow:0 24px 60px -28px rgba(34,33,33,0.45); }

        /* Cabecera: oscura, con brillo dorado */
        .stripe { height:4px; background:#cd9d36; background-image:linear-gradient(90deg,#9c1006 0%,#cd9d36 55%,#debb5a 100%); font-size:0; line-height:0; }
        .header { background:#222121; background-image:radial-gradient(120% 140% at 50% -20%, rgba(205,157,54,0.28), rgba(34,33,33,0) 62%); padding:38px 40px 34px; text-align:center; }
        .brand-name { margin:12px 0 0; color:#cd9d36; font-family:Georgia,'Times New Roman',serif; font-size:24px; font-weight:bold; letter-spacing:4px; text-transform:uppercase; line-height:1.1; }
        .brand-sub  { margin:6px 0 0; color:#ffffff; opacity:0.7; font-size:10px; letter-spacing:5px; text-transform:uppercase; }
        .brand-tag  { margin:16px 0 0; color:#debb5a; font-size:11px; letter-spacing:2px; text-transform:uppercase; }

        /* Etiqueta superior (antes "barra de alerta") */
        .chip-row { padding:34px 44px 0; }
        .chip { display:inline-block; padding:7px 16px; border-radius:999px; font-size:12px; font-weight:bold; letter-spacing:0.4px; }
        .chip.gold { background:#f7e9c0; color:#7e5a18; }
        .chip.dark { background:#f6dedb; color:#810f00; }
        .chip.blue { background:#222121; color:#debb5a; }

        /* Cuerpo */
        .body { padding:26px 44px 44px; }
        .body h2 { font-family:Georgia,'Times New Roman',serif; font-size:32px; line-height:1.1; letter-spacing:-0.8px; color:#222121; margin:0 0 18px; }
        .body h3 { font-family:Georgia,'Times New Roman',serif; font-size:19px; letter-spacing:-0.3px; color:#222121; margin:30px 0 12px; }
        .body p  { font-size:16px; line-height:1.65; color:#4b4441; margin:0 0 18px; }
        .body p.small  { font-size:13px; color:#888080; line-height:1.55; }
        .body p.center { text-align:center; }
        .body strong { color:#222121; }

        /* Tarjeta de datos */
        .card { background:#faf8f3; border:1px solid #ede8db; border-radius:20px; padding:10px 24px; margin:26px 0; }
        .card table { width:100%; border-collapse:collapse; font-size:14px; }
        .card table td { padding:12px 0; vertical-align:top; color:#4b4441; border-bottom:1px solid #ede8db; }
        .card table tr:last-child td { border-bottom:none; }
        .card table td:first-child { font-size:11px; font-weight:bold; letter-spacing:1.2px; text-transform:uppercase; color:#9f7320; width:130px; padding-right:14px; }

        /* Tabla de métricas */
        .metrics-table { width:100%; border-collapse:separate; border-spacing:0; font-size:14px; margin:14px 0 6px; border:1px solid #ede8db; border-radius:16px; overflow:hidden; }
        .metrics-table th { background:#222121; color:#debb5a; padding:12px 18px; text-align:left; font-size:11px; letter-spacing:1.4px; text-transform:uppercase; }
        .metrics-table th:last-child { text-align:right; }
        .metrics-table td { padding:12px 18px; border-bottom:1px solid #ede8db; color:#4b4441; }
        .metrics-table td:last-child { text-align:right; font-weight:bold; color:#222121; font-size:16px; }
        .metrics-table tr:last-child td { border-bottom:none; }
        .metrics-table tr:nth-child(even) td { background:#faf8f3; }

        /* Avisos */
        .notice { background:#fdf8ec; border:1px solid #f1e0b0; border-radius:16px; padding:16px 20px; margin:22px 0; font-size:14px; line-height:1.6; color:#5e4210; }
        .notice.warning { background:#fcefed; border-color:#f1c9c4; color:#650c00; }
        .notice.success { background:#eef8f1; border-color:#c4e5cf; color:#1f5a3a; }

        /* Botón principal: píldora dorada */
        .cta { text-align:center; margin:32px 0; }
        .cta a { display:inline-block; background:#cd9d36; background-image:linear-gradient(180deg,#debb5a,#cd9d36); color:#222121 !important; font-size:15px; font-weight:bold;
                 padding:16px 36px; border-radius:999px; text-decoration:none; box-shadow:0 10px 24px -10px rgba(159,115,32,0.8); }

        .divider { border:none; border-top:1px solid #ede8db; margin:30px 0; }
        .badge { display:inline-block; background:#222121; color:#debb5a; font-size:11px; font-weight:bold; letter-spacing:1.2px; text-transform:uppercase; padding:6px 14px; border-radius:999px; }

        /* Pie */
        .footer { background:#222121; padding:30px 40px 34px; text-align:center; }
        .footer p { margin:5px 0; color:#a59e98; font-size:12px; line-height:1.6; }
        .footer a { color:#debb5a; text-decoration:none; }
        .footer .tagline { color:#cd9d36; font-family:Georgia,'Times New Roman',serif; font-style:italic; font-size:16px; margin:0 0 14px; }
        .footer .copy { margin-top:16px; color:#6f6a66; font-size:11px; }

        @media only screen and (max-width:620px) {
            .outer { padding:0 !important; }
            .card-shell { border-radius:0 !important; }
            .header { padding:30px 22px 26px !important; }
            .chip-row { padding:26px 24px 0 !important; }
            .body { padding:22px 24px 34px !important; }
            .body h2 { font-size:27px !important; }
            .footer { padding:26px 22px 30px !important; }
            .card table td:first-child { width:96px !important; }
        }
    </style>
</head>
<body>

{{-- Preheader oculto para clientes de correo --}}
@isset($preheader)
<span style="display:none;font-size:1px;color:#efe9dc;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $preheader }}</span>
@endisset

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="outer" style="background:#efe9dc;">
<tr><td align="center" class="outer">
<div class="container">
<div class="card-shell">

    {{-- Cabecera --}}
    <div class="stripe">&nbsp;</div>
    <div class="header">
        @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ setting('site_name', config('app.name')) }}" height="64" style="height:64px; max-width:220px; width:auto; object-fit:contain;">
        @endif
        <p class="brand-name">Consultoría</p>
        <p class="brand-sub">Inmobiliaria</p>
        <p class="brand-tag">FOVISSSTE · INFONAVIT · Avalúos</p>
    </div>

    {{-- Etiqueta superior opcional --}}
    @hasSection('alert')
    <div class="chip-row">
        <span class="chip @yield('alert-class', 'gold')">@yield('alert')</span>
    </div>
    @endif

    {{-- Contenido principal --}}
    <div class="body">
        @yield('content')
    </div>

    {{-- Pie --}}
    <div class="footer">
        <p class="tagline">Tu patrimonio, nuestra prioridad.</p>
        @if(setting('telefono_1'))
        <p>Tel: <a href="tel:{{ setting('telefono_1') }}">{{ setting('telefono_1') }}</a></p>
        @endif
        @if($correoContacto)
        <p><a href="mailto:{{ $correoContacto }}">{{ $correoContacto }}</a></p>
        @endif
        <p>Hidalgo · Veracruz · San Luis Potosí</p>
        <p class="copy">&copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}. Todos los derechos reservados.</p>
    </div>

</div>
</div>
</td></tr>
</table>
</body>
</html>
