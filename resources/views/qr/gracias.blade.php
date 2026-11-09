@extends('qr.layout')
@section('titulo', 'Gracias')
@section('contenido')
@php $asesor = $qr->asesor; $tel = preg_replace('/\D/', '', (string) ($asesor?->telefono ?? '')); @endphp
<div class="card">
  <h2>¡Listo{{ $contacto ? ', ' . explode(' ', trim($contacto->nombre))[0] : '' }}!</h2>
  <p class="muted">Ya quedaste registrado. <strong>{{ $asesor?->name ?? 'Tu asesor' }}</strong> se pondrá en contacto contigo para empezar tu trámite.</p>

  @if ($asesor)
    <div class="asesor">
      <div class="ini">{{ mb_strtoupper(mb_substr($asesor->name, 0, 1)) }}</div>
      <div>
        <div style="font-weight:700;">{{ $asesor->name }}</div>
        <div class="muted" style="font-size:13px;">Tu asesor · código <span class="codigo">{{ $qr->codigo }}</span></div>
      </div>
    </div>
    @if ($tel)
      <a class="btn btn-verde" href="https://wa.me/52{{ $tel }}?text={{ urlencode('Hola, acabo de registrarme con tu código ' . $qr->codigo . '. Quiero información de mi crédito.') }}">Escribirle por WhatsApp</a>
      <a class="btn btn-claro" href="tel:{{ $tel }}">Llamarle</a>
    @endif
  @endif

  <h2 style="margin-top:26px; font-size:17px;">Sigue tu trámite desde la app</h2>
  <p class="muted">Ahí ves el avance, subes tus documentos y hablas con tu asesor.</p>
  @if ($playStore)
    <a class="btn btn-oscuro" href="{{ $playStore }}">Descargar en Google Play</a>
  @endif
  @if ($appStore)
    <a class="btn btn-oscuro" href="{{ $appStore }}">Descargar en App Store</a>
  @endif
  @if (! $playStore && ! $appStore)
    <a class="btn btn-oscuro" href="{{ route('descargar.app', ['codigo' => $qr->codigo]) }}">Descargar la app</a>
  @endif

  @if ($cuentaLista)
    <div class="ok" style="margin-top:18px;">Tu cuenta quedó lista. Descarga la app y entra con tu correo y contraseña: tu asesor ya aparece ahí.</div>
  @elseif ($contacto)
    <details {{ $errors->any() ? 'open' : '' }}>
      <summary>Crear mi contraseña para entrar a la app (opcional)</summary>
      <p class="muted" style="margin-top:8px;">Si prefieres, también puedes crear tu cuenta después, desde la app, con este mismo teléfono.</p>
      @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
      @endif
      <form method="post" action="{{ route('qr.cuenta', ['codigo' => $qr->codigo]) }}">
        @csrf
        <label for="email">Correo (será tu usuario)</label>
        <input id="email" type="email" name="email" value="{{ old('email', $contacto->email) }}" required autocomplete="email">
        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        <label for="password_confirmation">Repite la contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        <button class="btn btn-oro" type="submit">Crear mi cuenta</button>
      </form>
    </details>
  @endif
</div>
@endsection
