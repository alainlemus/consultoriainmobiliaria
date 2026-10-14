<tr>
<td class="footer" align="center" style="background:#222121; padding:34px 24px 38px; text-align:center; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
    @php
      $tel    = setting('telefono_1');
      $correo = setting_email_list('correo_contacto')[0] ?? null;
    @endphp
    <p style="margin:0 0 14px; text-align:center; color:#cd9d36; font-family:Georgia,'Times New Roman',serif; font-style:italic; font-size:16px;">Tu patrimonio, nuestra prioridad.</p>
    @if($tel)
    <p style="margin:5px 0; text-align:center; color:#a59e98; font-size:12px;">Tel: <a href="tel:{{ $tel }}" style="color:#debb5a; text-decoration:none;">{{ $tel }}</a></p>
    @endif
    @if($correo)
    <p style="margin:5px 0; text-align:center; color:#a59e98; font-size:12px;"><a href="mailto:{{ $correo }}" style="color:#debb5a; text-decoration:none;">{{ $correo }}</a></p>
    @endif
    <p style="margin:5px 0; text-align:center; color:#a59e98; font-size:12px;">Hidalgo · Veracruz · San Luis Potosí</p>
    <p style="margin:16px 0 0; text-align:center; color:#6f6a66; font-size:11px;">&copy; {{ date('Y') }} {{ setting('site_name', config('app.name')) }}. Todos los derechos reservados.</p>
    {!! isset($slot) ? $slot : '' !!}
</td>
</tr>
