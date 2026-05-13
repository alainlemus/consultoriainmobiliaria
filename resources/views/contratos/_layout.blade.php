<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
            padding: 0;
        }
        .page {
            padding: 2.5cm 3cm 2.5cm 3cm;
            min-height: 100%;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header .empresa {
            font-size: 16pt;
            font-weight: bold;
            color: #7c3aed;
            letter-spacing: 1px;
        }
        .header .slogan {
            font-size: 9pt;
            color: #555;
            margin-top: 2px;
        }
        .header .doc-titulo {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .folio-box {
            background: #f3f0ff;
            border: 1px solid #7c3aed;
            border-radius: 4px;
            padding: 6px 16px;
            display: inline-block;
            font-size: 9pt;
            color: #7c3aed;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 11pt;
            font-weight: bold;
            color: #7c3aed;
            border-bottom: 1px solid #ddd6fe;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        p { margin-bottom: 10px; text-align: justify; }
        .datos-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .datos-grid td {
            padding: 5px 8px;
            font-size: 10pt;
            vertical-align: top;
        }
        .datos-grid td.label {
            font-weight: bold;
            color: #555;
            width: 40%;
            white-space: nowrap;
        }
        .datos-grid td.valor {
            color: #111;
            border-bottom: 1px dotted #ccc;
        }
        .firma-bloque {
            margin-top: 60px;
        }
        .firmas {
            width: 100%;
            margin-top: 10px;
        }
        .firmas td {
            width: 50%;
            padding: 0 20px;
            text-align: center;
            vertical-align: bottom;
        }
        .linea-firma {
            border-top: 1px solid #333;
            padding-top: 6px;
            font-size: 9.5pt;
        }
        .footer {
            position: fixed;
            bottom: 1cm;
            left: 3cm;
            right: 3cm;
            font-size: 8pt;
            color: #aaa;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 6px;
        }
        .monto-destacado {
            font-size: 14pt;
            font-weight: bold;
            color: #7c3aed;
        }
        .clausula { margin-bottom: 14px; }
        .clausula .numero {
            font-weight: bold;
            color: #7c3aed;
        }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="empresa">CONSULTORÍA INMOBILIARIA</div>
        <div class="slogan">Gestión de trámites hipotecarios y patrimoniales</div>
        <div class="doc-titulo">@yield('titulo')</div>
    </div>

    <div style="text-align:center;">
        <div class="folio-box">Expediente: {{ $expediente->folio }} &nbsp;|&nbsp; Fecha: {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</div>
    </div>

    @yield('contenido')

    <div class="footer">
        Consultoría Inmobiliaria &bull; Documento generado el {{ now()->format('d/m/Y H:i') }} &bull; {{ $expediente->folio }}
    </div>
</div>
</body>
</html>
