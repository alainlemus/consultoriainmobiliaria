{{--
  Partial: header de email
  Uso: @include('emails.partials.header')
  Muestra el logo si está configurado, o el nombre del sitio como texto.
--}}
@php
  $logoPath = setting('logo');
  $logoUrl  = $logoPath ? asset('storage/' . $logoPath) : null;
  $siteName = setting('site_name', config('app.name'));
@endphp
<div class="header">
    @if($logoUrl)
    <div style="margin-bottom:10px;">
        <img src="{{ $logoUrl }}"
             alt="{{ $siteName }}"
             style="max-height:70px; max-width:220px; object-fit:contain;">
    </div>
    @endif
    <h1>{{ $siteName }}</h1>
    <p>FOVISSSTE · INFONAVIT · AVALÚOS</p>
</div>
