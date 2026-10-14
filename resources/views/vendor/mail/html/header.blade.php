@props(['url'])
@php
  $logoPath = setting('logo');
  $logoUrl  = $logoPath ? asset('storage/' . $logoPath) : null;
@endphp
<tr>
<td style="height:4px; line-height:4px; font-size:0; background:#cd9d36; background-image:linear-gradient(90deg,#9c1006 0%,#cd9d36 55%,#debb5a 100%);">&nbsp;</td>
</tr>
<tr>
<td class="header" style="background:#222121; background-image:radial-gradient(120% 140% at 50% -20%, rgba(205,157,54,0.28), rgba(34,33,33,0) 62%); padding:38px 40px 34px; text-align:center;">
    <a href="{{ $url }}" style="display:inline-block; text-decoration:none;">
        @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ setting('site_name', config('app.name')) }}" height="64"
             style="height:64px; max-width:220px; width:auto; object-fit:contain; display:block; margin:0 auto;">
        @endif
        <span style="display:block; margin-top:12px; color:#cd9d36; font-size:24px; font-weight:bold; letter-spacing:4px; text-transform:uppercase; font-family:Georgia,'Times New Roman',serif; line-height:1.1;">Consultoría</span>
        <span style="display:block; margin-top:6px; color:#b8b2ac; font-size:10px; letter-spacing:5px; text-transform:uppercase; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">Inmobiliaria</span>
        <span style="display:block; margin-top:16px; color:#debb5a; font-size:11px; letter-spacing:2px; text-transform:uppercase; font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">FOVISSSTE · INFONAVIT · Avalúos</span>
    </a>
</td>
</tr>
