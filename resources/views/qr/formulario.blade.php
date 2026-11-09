@extends('qr.layout')
@section('titulo', 'Quiero mi crédito')
@section('contenido')
<div class="card">
  <h2>Quiero mi crédito de vivienda</h2>
  <p class="muted">Déjanos tus datos y <strong>{{ $qr->asesor?->name ?? 'un asesor' }}</strong> te contacta. Sin costo y sin compromiso.</p>

  @if ($errors->any())
    <div class="error">{{ $errors->first() }}</div>
  @endif

  <form method="post" action="{{ route('qr.formulario.store', ['codigo' => $qr->codigo]) }}" autocomplete="on">
    @csrf
    <input type="text" name="sitio_web" value="" tabindex="-1" autocomplete="off" style="position:absolute; left:-9999px; width:1px; height:1px;" aria-hidden="true">

    <label for="nombre">Tu nombre</label>
    <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}" required autocomplete="name" placeholder="Nombre y apellidos">

    <label for="telefono">Tu teléfono (WhatsApp)</label>
    <input id="telefono" type="tel" name="telefono" value="{{ old('telefono') }}" required inputmode="numeric" autocomplete="tel" placeholder="10 dígitos">

    <label for="credito">¿Qué crédito te interesa?</label>
    <select id="credito" name="credito" required>
      <option value="" disabled {{ old('credito') ? '' : 'selected' }}>Elige una opción</option>
      @foreach ($creditos as $valor => $texto)
        <option value="{{ $valor }}" {{ old('credito') === $valor ? 'selected' : '' }}>{{ $texto }}</option>
      @endforeach
    </select>

    <label for="municipio">Municipio donde vives <span class="muted">(opcional)</span></label>
    <input id="municipio" type="text" name="municipio" value="{{ old('municipio') }}" autocomplete="address-level2" placeholder="Ej. Huejutla">

    <label for="email">Correo <span class="muted">(opcional)</span></label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="correo@ejemplo.com">

    <label class="check">
      <input type="checkbox" name="privacidad" value="1" {{ old('privacidad') ? 'checked' : '' }} required>
      <span>Acepto que me contacten y el <a href="{{ route('aviso.privacidad') }}" target="_blank" rel="noopener">aviso de privacidad</a>.</span>
    </label>

    <button class="btn btn-oro" type="submit">Quiero que me contacten</button>
  </form>

  <p class="muted" style="margin-top:16px; font-size:12px;">Código de asesor: <span class="codigo">{{ $qr->codigo }}</span></p>
</div>
@endsection
