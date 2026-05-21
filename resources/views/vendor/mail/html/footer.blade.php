<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation"
       style="background:#1a1a1a; padding:24px 40px; text-align:center;">
<tr>
<td style="text-align:center;">
    @php
      $tel    = setting('telefono_1');
      $correo = setting('correo_contacto');
    @endphp
    @if($tel)
    <p style="margin:4px 0; color:#7a7a6a; font-size:12px; font-family:Arial,sans-serif;">
        Tel: <a href="tel:{{ $tel }}" style="color:#C9A84C; text-decoration:none;">{{ $tel }}</a>
    </p>
    @endif
    @if($correo)
    <p style="margin:4px 0; color:#7a7a6a; font-size:12px; font-family:Arial,sans-serif;">
        <a href="mailto:{{ $correo }}" style="color:#C9A84C; text-decoration:none;">{{ $correo }}</a>
    </p>
    @endif
    <p style="margin:12px 0 4px; color:#555; font-size:12px; font-family:Arial,sans-serif;">
        &copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}. Todos los derechos reservados.
    </p>
    {!! isset($slot) ? $slot : '' !!}
</td>
</tr>
</table>
</td>
</tr>
