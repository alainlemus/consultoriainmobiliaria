{{--
  Layout base para todos los correos del sistema.
  Uso:
    @extends('emails.layouts.base')
    @section('title', 'Asunto del correo')
    @section('content') ... @endsection

  Variables opcionales:
    $preheader  — texto de previsualización (oculto, para clientes de correo)
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body { margin:0; padding:0; background:#f5f0e8; font-family: Arial, sans-serif; color:#2a2a2a; }
        .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-top:4px solid #C9A84C; }

        /* Header */
        .header { background:#1a1a1a; padding:32px 40px; text-align:center; }
        .header h1 { margin:0; color:#C9A84C; font-size:22px; letter-spacing:3px; text-transform:uppercase; font-family: Georgia, 'Times New Roman', serif; }
        .header p { margin:6px 0 0; color:#c8b88a; font-size:12px; letter-spacing:2px; text-transform:uppercase; }

        /* Alert bar opcional */
        .alert-bar { padding:12px 40px; }
        .alert-bar p { margin:0; color:#fff; font-size:13px; font-weight:bold; letter-spacing:0.5px; }
        .alert-bar.gold  { background:#C9A84C; }
        .alert-bar.gold p { color:#1a1a1a; }
        .alert-bar.dark  { background:#8B1A1A; }
        .alert-bar.blue  { background:#1a3a5c; }

        /* Body */
        .body { padding:40px; }
        .body h2 { font-size:20px; color:#1a1a1a; margin:0 0 16px; font-family: Georgia, 'Times New Roman', serif; }
        .body h3 { font-size:16px; color:#1a1a1a; margin:24px 0 10px; font-family: Georgia, 'Times New Roman', serif; border-bottom:1px solid #e8e0d0; padding-bottom:6px; }
        .body p { font-size:15px; line-height:1.7; color:#444; margin:0 0 16px; }
        .body p.small { font-size:13px; color:#888; }
        .body p.center { text-align:center; }

        /* Card de datos */
        .card { background:#f9f6f0; border-left:3px solid #C9A84C; padding:20px 24px; margin:24px 0; border-radius:2px; }
        .card table { width:100%; border-collapse:collapse; font-size:14px; }
        .card table td { padding:7px 0; vertical-align:top; color:#555; border-bottom:1px solid #ede8df; }
        .card table tr:last-child td { border-bottom:none; }
        .card table td:first-child { font-weight:bold; color:#1a1a1a; width:150px; }

        /* Tabla de métricas */
        .metrics-table { width:100%; border-collapse:collapse; font-size:14px; margin:16px 0; }
        .metrics-table th { background:#1a1a1a; color:#C9A84C; padding:10px 14px; text-align:left; font-size:12px; letter-spacing:1px; text-transform:uppercase; }
        .metrics-table td { padding:9px 14px; border-bottom:1px solid #ede8df; color:#444; }
        .metrics-table td:last-child { text-align:right; font-weight:bold; color:#1a1a1a; }
        .metrics-table tr:last-child td { border-bottom:none; }
        .metrics-table tr:nth-child(even) td { background:#faf7f2; }

        /* Sección de alerta/aviso */
        .notice { background:#fff8f0; border:1px solid #e8d8c0; border-left:3px solid #C9A84C; padding:14px 18px; border-radius:2px; margin:20px 0; font-size:14px; line-height:1.6; color:#555; }
        .notice.warning { background:#fff5f5; border-color:#f8d0d0; border-left-color:#8B1A1A; }
        .notice.success { background:#f0faf4; border-color:#c0e8cc; border-left-color:#2d7a4f; }

        /* Botón CTA */
        .cta { text-align:center; margin:28px 0; }
        .cta a { display:inline-block; background:#C9A84C; color:#1a1a1a; font-size:13px; font-weight:bold;
                 letter-spacing:1px; text-transform:uppercase; padding:13px 32px; border-radius:2px; text-decoration:none; }

        /* Divisor */
        .divider { border:none; border-top:1px solid #e8e0d0; margin:28px 0; }

        /* Badge */
        .badge { display:inline-block; background:#C9A84C; color:#1a1a1a; font-size:11px; font-weight:bold;
                 letter-spacing:1px; text-transform:uppercase; padding:4px 12px; border-radius:2px; }

        /* Footer */
        .footer { background:#1a1a1a; padding:24px 40px; text-align:center; }
        .footer p { margin:4px 0; color:#7a7a6a; font-size:12px; line-height:1.6; }
        .footer a { color:#C9A84C; text-decoration:none; }
    </style>
</head>
<body>

{{-- Preheader oculto para clientes de correo --}}
@isset($preheader)
<span style="display:none;font-size:1px;color:#f5f0e8;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ $preheader }}</span>
@endisset

<div class="wrapper">

    {{-- Header con logo --}}
    @include('emails.partials.header')

    {{-- Alert bar opcional --}}
    @hasSection('alert')
    <div class="alert-bar @yield('alert-class', 'gold')">
        <p>@yield('alert')</p>
    </div>
    @endif

    {{-- Contenido principal --}}
    <div class="body">
        @yield('content')
    </div>

    {{-- Footer --}}
    <div class="footer">
        @if(setting('telefono_1'))
        <p>Tel: <a href="tel:{{ setting('telefono_1') }}">{{ setting('telefono_1') }}</a></p>
        @endif
        @if(setting('correo_contacto'))
        <p><a href="mailto:{{ setting('correo_contacto') }}">{{ setting('correo_contacto') }}</a></p>
        @endif
        <p style="margin-top:10px;">&copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}. Todos los derechos reservados.</p>
    </div>

</div>
</body>
</html>
