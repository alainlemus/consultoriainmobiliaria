{{-- Marca del panel: logo + "Consultoría / Inmobiliaria" en dos renglones (mismos colores que el login) --}}
@php $esLogin = request()->routeIs('filament.admin.auth.*'); @endphp
<div style="display:flex; align-items:center; gap:0.75rem;">
    @if ($logo)
        {{-- En login/recuperar contraseña el logo va grande, como antes --}}
        <img src="{{ $logo }}" alt="Consultoría Inmobiliaria" style="height:{{ $esLogin ? '12rem' : '2.5rem' }}; width:auto;">
    @endif
    @unless ($esLogin)
    <div style="display:flex; flex-direction:column; line-height:1.1;">
        <span style="font-size:0.95rem; font-weight:600; letter-spacing:0.18em; text-transform:uppercase; color:#9ca3af;">Consultoría</span>
        <span style="font-size:0.95rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#B8960C;">Inmobiliaria</span>
    </div>
    @endunless
</div>
