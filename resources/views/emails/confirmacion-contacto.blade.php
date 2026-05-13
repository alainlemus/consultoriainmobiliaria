<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibimos tu mensaje</title>
    <style>
        body { margin:0; padding:0; background:#f5f0e8; font-family: Georgia, 'Times New Roman', serif; color:#2a2a2a; }
        .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-top:4px solid #C9A84C; }
        .header { background:#1a1a1a; padding:32px 40px; text-align:center; }
        .header h1 { margin:0; color:#C9A84C; font-size:22px; letter-spacing:3px; text-transform:uppercase; }
        .header p { margin:6px 0 0; color:#c8b88a; font-size:12px; letter-spacing:2px; text-transform:uppercase; font-family: Arial, sans-serif; }
        .body { padding:40px; }
        .body h2 { font-size:20px; color:#1a1a1a; margin:0 0 16px; }
        .body p { font-size:15px; line-height:1.7; color:#444; margin:0 0 16px; font-family: Arial, sans-serif; }
        .card { background:#f9f6f0; border-left:3px solid #C9A84C; padding:20px 24px; margin:24px 0; border-radius:2px; }
        .card table { width:100%; border-collapse:collapse; font-family: Arial, sans-serif; font-size:14px; }
        .card table td { padding:6px 0; vertical-align:top; color:#555; }
        .card table td:first-child { font-weight:bold; color:#1a1a1a; width:110px; }
        .divider { border:none; border-top:1px solid #e8e0d0; margin:28px 0; }
        .footer { background:#1a1a1a; padding:24px 40px; text-align:center; }
        .footer p { margin:4px 0; color:#7a7a6a; font-size:12px; font-family: Arial, sans-serif; line-height:1.6; }
        .footer a { color:#C9A84C; text-decoration:none; }
        .badge { display:inline-block; background:#C9A84C; color:#1a1a1a; font-family:Arial,sans-serif; font-size:11px; font-weight:bold; letter-spacing:1px; text-transform:uppercase; padding:4px 12px; border-radius:2px; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <h1>Consultoría Inmobiliaria</h1>
        <p>{{ setting('site_name', config('app.name')) }}</p>
    </div>

    <div class="body">
        <h2>Hola, {{ $contacto->nombre }}.</h2>
        <p>
            Recibimos tu mensaje correctamente. Uno de nuestros asesores revisará tu solicitud
            y se pondrá en contacto contigo a la brevedad, sin costo ni compromiso.
        </p>

        <div class="card">
            <table>
                <tr>
                    <td>Nombre:</td>
                    <td>{{ $contacto->nombre }}</td>
                </tr>
                <tr>
                    <td>Teléfono:</td>
                    <td>{{ $contacto->telefono }}</td>
                </tr>
                <tr>
                    <td>Correo:</td>
                    <td>{{ $contacto->email }}</td>
                </tr>
                @if($contacto->servicio)
                <tr>
                    <td>Servicio:</td>
                    <td>{{ ucfirst($contacto->servicio) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Mensaje:</td>
                    <td>{{ $contacto->mensaje }}</td>
                </tr>
            </table>
        </div>

        <hr class="divider">

        <p>
            Si tienes alguna duda urgente, puedes contactarnos directamente por WhatsApp:
        </p>
        @if(setting('whatsapp_1'))
        <p style="text-align:center; margin:20px 0;">
            <a href="https://wa.me/{{ setting('whatsapp_1') }}"
               style="display:inline-block; background:#25D366; color:#fff; font-family:Arial,sans-serif;
                      font-size:14px; font-weight:bold; padding:12px 28px; border-radius:3px; text-decoration:none;">
                Escribir por WhatsApp
            </a>
        </p>
        @endif

        <p style="font-size:13px; color:#888;">
            Este correo es una confirmación automática. Por favor no respondas a este mensaje.
        </p>
    </div>

    <div class="footer">
        @if(setting('telefono_1'))
        <p>Tel: <a href="tel:{{ setting('telefono_1') }}">{{ setting('telefono_1') }}</a></p>
        @endif
        @if(setting('correo_contacto'))
        <p><a href="mailto:{{ setting('correo_contacto') }}">{{ setting('correo_contacto') }}</a></p>
        @endif
        <p style="margin-top:12px; color:#555;">
            &copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}. Todos los derechos reservados.
        </p>
    </div>

</div>
</body>
</html>
