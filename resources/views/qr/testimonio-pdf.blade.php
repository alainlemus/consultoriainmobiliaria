<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #ffffff;
            color: #1a1a1a;
        }

        .page {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 80px;
            text-align: center;
        }

        .logo-line {
            width: 60px;
            height: 3px;
            background: #C9A84C;
            margin: 0 auto 24px;
        }

        .titulo {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .subtitulo {
            font-size: 13px;
            color: #666666;
            margin-bottom: 40px;
        }

        .qr-wrapper {
            border: 2px solid #C9A84C;
            border-radius: 4px;
            padding: 20px;
            display: inline-block;
            margin-bottom: 32px;
            background: #fff;
        }

        .qr-wrapper img {
            width: 220px;
            height: 220px;
            display: block;
        }

        .instruccion {
            font-size: 13px;
            color: #444444;
            margin-bottom: 8px;
        }

        .url {
            font-size: 11px;
            color: #C9A84C;
            background: #fdf9f0;
            border: 1px solid #e8d9a8;
            border-radius: 3px;
            padding: 6px 16px;
            display: inline-block;
            margin-bottom: 40px;
            letter-spacing: 0.3px;
        }

        .footer-line {
            width: 100%;
            height: 1px;
            background: #eeeeee;
            margin-bottom: 16px;
        }

        .footer-text {
            font-size: 10px;
            color: #aaaaaa;
            letter-spacing: 0.5px;
        }

        .gold-accent {
            color: #C9A84C;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="page">

    <div class="logo-line"></div>

    <div class="titulo">¿Cómo fue tu experiencia con nosotros?</div>
    <div class="subtitulo">Escanea el código QR y déjanos tu opinión — solo toma 1 minuto</div>

    <div class="qr-wrapper">
        <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR Testimonio">
    </div>

    <div class="instruccion">O visita directamente:</div>
    <div class="url">{{ $url }}</div>

    <div class="footer-line"></div>
    <div class="footer-text">
        <span class="gold-accent">CONSULTORÍA INMOBILIARIA</span> &nbsp;·&nbsp;
        Tu testimonio es revisado antes de publicarse &nbsp;·&nbsp; {{ date('Y') }}
    </div>

</div>
</body>
</html>
