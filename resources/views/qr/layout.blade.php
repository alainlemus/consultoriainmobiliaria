<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('titulo') — {{ setting('site_name', 'Consultoría Inmobiliaria') }}</title>
<style>
  * { box-sizing: border-box; }
  body { margin:0; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; background:#222121; color:#f5f1e8; }
  .wrap { max-width: 480px; margin: 0 auto; padding: 28px 18px 40px; }
  .marca { text-align:center; margin-bottom: 22px; }
  .marca .linea { width: 48px; height: 3px; background:#C9A84C; margin: 8px auto; }
  .marca h1 { font-size: 15px; letter-spacing: 3px; margin: 0; font-weight: 700; }
  .marca .sub { font-size: 11px; letter-spacing: 2px; color:#C9A84C; }
  .card { background:#fff; color:#1a1a1a; border-radius: 14px; padding: 22px 18px; }
  h2 { font-size: 20px; margin: 0 0 6px; }
  p { line-height: 1.5; margin: 0 0 14px; }
  .muted { color:#666; font-size: 14px; }
  label { display:block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; color:#333; }
  input[type=text], input[type=tel], input[type=email], input[type=password], select {
    width:100%; font-size: 17px; padding: 13px 12px; border: 1px solid #d6d0c4; border-radius: 10px; background:#fbfaf7; color:#1a1a1a;
  }
  input:focus, select:focus { outline: 2px solid #C9A84C; border-color:#C9A84C; }
  .check { display:flex; gap: 10px; align-items:flex-start; font-size: 13px; color:#444; margin-top: 16px; }
  .check input { margin-top: 3px; width: 18px; height: 18px; }
  .btn { display:block; width:100%; text-align:center; text-decoration:none; font-size: 17px; font-weight: 700; padding: 15px; border-radius: 12px; border: 0; cursor:pointer; margin-top: 18px; }
  .btn-oro { background:#C9A84C; color:#1a1a1a; }
  .btn-oscuro { background:#1a1a1a; color:#fff; }
  .btn-verde { background:#25d366; color:#fff; }
  .btn-claro { background:#f1ede3; color:#1a1a1a; }
  .error { background:#fee2e2; color:#991b1b; border-radius: 10px; padding: 10px 12px; font-size: 14px; margin-bottom: 12px; }
  .codigo { font-family: ui-monospace, Menlo, monospace; letter-spacing: 3px; font-weight: 700; color:#8a6d1f; }
  .asesor { display:flex; gap: 12px; align-items:center; background:#fdf9f0; border:1px solid #e8d9a8; border-radius: 12px; padding: 14px; margin: 14px 0; }
  .asesor .ini { width: 44px; height: 44px; border-radius: 50%; background:#1a1a1a; color:#C9A84C; display:flex; align-items:center; justify-content:center; font-weight:700; font-size: 18px; }
  .pie { text-align:center; font-size: 11px; color:#9a948a; margin-top: 22px; }
  .pie a { color:#C9A84C; }
  details summary { cursor:pointer; font-weight: 600; color:#333; margin-top: 18px; }
  .ok { background:#dcfce7; color:#166534; border-radius: 10px; padding: 12px; font-size: 14px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="marca">
    <div class="linea"></div>
    <h1>CONSULTORÍA INMOBILIARIA</h1>
    <div class="sub">FOVISSSTE · INFONAVIT</div>
    <div class="linea"></div>
  </div>
  @yield('contenido')
  <div class="pie">
    <a href="{{ route('aviso.privacidad') }}">Aviso de privacidad</a> · {{ date('Y') }}
  </div>
</div>
</body>
</html>
