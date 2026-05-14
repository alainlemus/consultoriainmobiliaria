<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #1a1a1a;
            background: #faf8f3;
        }

        .page {
            padding: 0;
            min-height: 100%;
            background: #ffffff;
        }

        /* ── HEADER ─────────────────────────────────── */
        .header {
            background: #1a1a1a;
            padding: 10px 28px 0 28px;
        }

        .header-top {
            display: table;
            width: 100%;
        }

        .header-logo {
            display: table-cell;
            vertical-align: middle;
            width: 110px;
        }

        .header-logo img {
            width: 90px;
            height: auto;
        }

        .header-text {
            display: table-cell;
            vertical-align: middle;
            padding-left: 16px;
        }

        .header-empresa {
            font-size: 13pt;
            font-weight: bold;
            color: #d4af37;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .header-slogan {
            font-size: 7.5pt;
            color: #a0936a;
            margin-top: 1px;
            letter-spacing: 0.5px;
        }

        .header-divider {
            height: 3px;
            background: linear-gradient(to right, #d4af37, #9b2335, #d4af37);
            margin-top: 10px;
        }

        .doc-titulo-bar {
            background: #9b2335;
            padding: 5px 28px;
            text-align: center;
        }

        .doc-titulo-bar span {
            font-size: 9.5pt;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* ── FOLIO BADGE ─────────────────────────────── */
        .folio-area {
            padding: 7px 28px 0 28px;
            text-align: right;
        }

        .folio-box {
            display: inline-block;
            background: #fdf9ee;
            border: 1px solid #d4af37;
            border-radius: 3px;
            padding: 3px 10px;
            font-size: 7.5pt;
            color: #96760f;
        }

        /* ── BODY ────────────────────────────────────── */
        .body-content {
            padding: 10px 28px 24px 28px;
        }

        h2 {
            font-size: 8.5pt;
            font-weight: bold;
            color: #9b2335;
            border-bottom: 1.5px solid #d4af37;
            padding-bottom: 2px;
            margin-top: 12px;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        p {
            margin-bottom: 6px;
            text-align: justify;
        }

        /* ── DATOS GRID ──────────────────────────────── */
        .datos-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .datos-grid td {
            padding: 5px 8px;
            font-size: 9.5pt;
            vertical-align: top;
        }

        .datos-grid td.label {
            font-weight: bold;
            color: #7a5f0e;
            width: 38%;
            white-space: nowrap;
        }

        .datos-grid td.valor {
            color: #1a1a1a;
            border-bottom: 1px dotted #c9a84c;
        }

        /* ── FIRMAS ──────────────────────────────────── */
        .firma-bloque {
            margin-top: 30px;
        }

        .firmas {
            width: 100%;
        }

        .firmas td {
            width: 50%;
            padding: 0 24px;
            text-align: center;
            vertical-align: bottom;
        }

        .linea-firma {
            border-top: 1.5px solid #1a1a1a;
            padding-top: 6px;
            font-size: 9pt;
            line-height: 1.6;
        }

        /* ── FOOTER ──────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 0.8cm;
            left: 0;
            right: 0;
            padding: 0 32px;
        }

        .footer-inner {
            border-top: 1px solid #d4af37;
            padding-top: 5px;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            font-size: 7.5pt;
            color: #96760f;
            vertical-align: middle;
        }

        .footer-right {
            display: table-cell;
            font-size: 7.5pt;
            color: #9b2335;
            text-align: right;
            vertical-align: middle;
        }

        /* ── UTILS ───────────────────────────────────── */
        .clausula { margin-bottom: 12px; }
        .clausula .numero { font-weight: bold; color: #9b2335; }
        .page-break { page-break-after: always; }
        ul li { margin-bottom: 3px; }
    </style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <div class="header-top">
            <div class="header-logo">
                @php
                    $logoPath = \App\Models\Configuracion::get('logo');
                    $logoAbs  = $logoPath
                        ? storage_path('app/public/' . $logoPath)
                        : null;
                @endphp
                @if($logoAbs && file_exists($logoAbs))
                    <img src="{{ $logoAbs }}" alt="Logo">
                @endif
            </div>
            <div class="header-text">
                <div class="header-empresa">{{ \App\Models\Configuracion::get('site_name') ?? 'Consultoría Inmobiliaria' }}</div>
                <div class="header-slogan">Gestión de trámites hipotecarios y patrimoniales</div>
            </div>
        </div>
        <div class="header-divider"></div>
    </div>

    <div class="doc-titulo-bar">
        <span>@yield('titulo')</span>
    </div>

    {{-- FOLIO --}}
    <div class="folio-area">
        <div class="folio-box">
            Expediente: <strong>{{ $expediente->folio }}</strong>
            &nbsp;|&nbsp;
            {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </div>
    </div>

    {{-- CONTENIDO --}}
    <div class="body-content">
        @yield('contenido')
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <div class="footer-inner">
            <div class="footer-left">
                {{ \App\Models\Configuracion::get('site_name') ?? 'Consultoría Inmobiliaria' }}
                &bull; Documento generado el {{ now()->format('d/m/Y H:i') }}
            </div>
            <div class="footer-right">
                {{ $expediente->folio }}
            </div>
        </div>
    </div>

</div>
</body>
</html>
