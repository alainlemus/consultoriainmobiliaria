<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #fff; color: #1a1a1a; }
        .page { width: 100%; padding: 50px 70px; text-align: center; }
        .logo-line { width: 60px; height: 3px; background: #C9A84C; margin: 0 auto 22px; }
        .titulo { font-size: 24px; font-weight: bold; margin-bottom: 6px; letter-spacing: 0.5px; }
        .subtitulo { font-size: 13px; color: #666; margin-bottom: 30px; }
        .qr-wrapper { border: 2px solid #C9A84C; border-radius: 4px; padding: 18px; display: inline-block; margin-bottom: 26px; background: #fff; }
        .qr-wrapper img { width: 300px; height: 300px; display: block; }
        .instruccion { font-size: 13px; color: #444; margin-bottom: 8px; }
        .codigo { font-size: 44px; font-weight: bold; letter-spacing: 8px; color: #1a1a1a; margin-bottom: 6px; }
        .codigo-hint { font-size: 12px; color: #666; margin-bottom: 26px; }
        .url { font-size: 11px; color: #C9A84C; background: #fdf9f0; border: 1px solid #e8d9a8; border-radius: 3px; padding: 6px 16px; display: inline-block; margin-bottom: 30px; letter-spacing: 0.3px; }
        .asesor { font-size: 14px; color: #1a1a1a; margin-bottom: 30px; }
        .asesor strong { color: #C9A84C; }
        .footer-line { width: 100%; height: 1px; background: #eee; margin-bottom: 14px; }
        .footer-text { font-size: 10px; color: #aaa; letter-spacing: 0.5px; }
        .gold-accent { color: #C9A84C; font-weight: bold; }
    </style>
</head>
<body>
<div class="page">
    <div class="logo-line"></div>

    <div class="titulo">Descarga nuestra app</div>
    <div class="subtitulo">Escanea el código QR con la cámara de tu celular</div>

    <div class="qr-wrapper">
        <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR {{ $codigo->codigo }}">
    </div>

    <div class="instruccion">Al crear tu cuenta, escribe tu código de asesor:</div>
    <div class="codigo">{{ $codigo->codigo }}</div>
    <div class="codigo-hint">Así tu asesor podrá acompañarte en todo el trámite.</div>

    <div class="url">{{ $url }}</div>

    <div class="asesor">Tu asesor: <strong>{{ $codigo->asesor?->name ?? '—' }}</strong></div>

    <div class="footer-line"></div>
    <div class="footer-text">
        <span class="gold-accent">CONSULTORÍA INMOBILIARIA</span> &nbsp;·&nbsp;
        {{ $codigo->medio_label }} &nbsp;·&nbsp; {{ date('Y') }}
    </div>
</div>
</body>
</html>
