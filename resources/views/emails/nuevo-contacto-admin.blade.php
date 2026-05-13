<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo mensaje de contacto</title>
    <style>
        body { margin:0; padding:0; background:#f5f0e8; font-family: Arial, sans-serif; color:#2a2a2a; }
        .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-top:4px solid #8B1A1A; }
        .header { background:#1a1a1a; padding:28px 40px; }
        .header h1 { margin:0; color:#C9A84C; font-size:18px; letter-spacing:2px; text-transform:uppercase; }
        .header p { margin:4px 0 0; color:#aaa; font-size:12px; }
        .alert-bar { background:#8B1A1A; padding:12px 40px; }
        .alert-bar p { margin:0; color:#fff; font-size:13px; font-weight:bold; letter-spacing:0.5px; }
        .body { padding:36px 40px; }
        .body p { font-size:15px; line-height:1.7; color:#444; margin:0 0 16px; }
        .card { background:#fafafa; border:1px solid #e8e0d0; border-left:4px solid #C9A84C; padding:20px 24px; margin:20px 0; border-radius:2px; }
        .card table { width:100%; border-collapse:collapse; font-size:14px; }
        .card table td { padding:8px 0; vertical-align:top; color:#555; border-bottom:1px solid #f0e8dc; }
        .card table tr:last-child td { border-bottom:none; }
        .card table td:first-child { font-weight:bold; color:#1a1a1a; width:120px; }
        .mensaje-box { background:#fff8f0; border:1px solid #e8d8c0; padding:16px 20px; border-radius:2px; font-size:14px; line-height:1.7; color:#333; margin-top:8px; white-space:pre-wrap; }
        .cta { text-align:center; margin:28px 0; }
        .cta a { display:inline-block; background:#C9A84C; color:#1a1a1a; font-size:13px; font-weight:bold;
                 letter-spacing:1px; text-transform:uppercase; padding:12px 28px; border-radius:2px; text-decoration:none; }
        .footer { background:#1a1a1a; padding:20px 40px; text-align:center; }
        .footer p { margin:3px 0; color:#666; font-size:12px; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <h1>Panel de Administración</h1>
        <p>{{ setting('site_name', config('app.name')) }}</p>
    </div>

    <div class="alert-bar">
        <p>📬 Nuevo mensaje de contacto recibido</p>
    </div>

    <div class="body">
        <p>Se ha recibido un nuevo mensaje a través del formulario de contacto del sitio web.</p>

        <div class="card">
            <table>
                <tr>
                    <td>Nombre:</td>
                    <td><strong>{{ $contacto->nombre }}</strong></td>
                </tr>
                <tr>
                    <td>Teléfono:</td>
                    <td>
                        <a href="tel:{{ $contacto->telefono }}" style="color:#8B1A1A; text-decoration:none;">
                            {{ $contacto->telefono }}
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>Correo:</td>
                    <td>
                        <a href="mailto:{{ $contacto->email }}" style="color:#8B1A1A; text-decoration:none;">
                            {{ $contacto->email }}
                        </a>
                    </td>
                </tr>
                @if($contacto->servicio)
                <tr>
                    <td>Servicio:</td>
                    <td>{{ ucfirst($contacto->servicio) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Fecha:</td>
                    <td>{{ $contacto->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Mensaje:</td>
                    <td>
                        <div class="mensaje-box">{{ $contacto->mensaje }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="cta">
            <a href="{{ url('/admin/contactos') }}">Ver en el panel de administración</a>
        </div>

        <p style="font-size:13px; color:#999; text-align:center;">
            Este correo fue generado automáticamente por el sistema.
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}</p>
    </div>

</div>
</body>
</html>
