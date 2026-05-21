@props(['url'])
@php
  $logoPath = setting('logo');
  $logoUrl  = $logoPath ? asset('storage/' . $logoPath) : null;
  $siteName = setting('site_name', config('app.name'));
@endphp
<tr>
<td class="header" style="background:#1a1a1a; padding:32px 40px; text-align:center; border-top:4px solid #C9A84C;">
    <a href="{{ $url }}" style="display:inline-block; text-decoration:none;">
        @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}"
             style="max-height:70px; max-width:220px; object-fit:contain; display:block; margin:0 auto 10px;">
        @endif
        <span style="display:block; color:#C9A84C; font-size:20px; font-weight:bold; letter-spacing:3px; text-transform:uppercase; font-family:Georgia,'Times New Roman',serif;">
            {{ $siteName }}
        </span>
        <span style="display:block; color:#c8b88a; font-size:11px; letter-spacing:2px; text-transform:uppercase; font-family:Arial,sans-serif; margin-top:4px;">
            FOVISSSTE · INFONAVIT · AVALÚOS
        </span>
    </a>
</td>
</tr>
