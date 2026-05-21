@unless(app()->isProduction())
@php
    $env = app()->environment();
    $color = match($env) {
        'local'   => '#16a34a', // verde
        'staging' => '#d97706', // naranja
        default   => '#dc2626', // rojo para cualquier otro
    };
    $label = strtoupper($env);
@endphp
<div style="
    position: fixed;
    top: 18px;
    right: -32px;
    z-index: 99999;
    background: {{ $color }};
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    font-family: monospace;
    letter-spacing: 1px;
    padding: 4px 48px;
    transform: rotate(45deg);
    box-shadow: 0 2px 6px rgba(0,0,0,0.35);
    pointer-events: none;
    user-select: none;
">{{ $label }}</div>
@endunless
