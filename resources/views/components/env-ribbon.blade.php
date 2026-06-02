@unless (app()->isProduction())
    @php
        $env = app()->environment();
        $color = match ($env) {
            'local' => '#16a34a',
            'staging' => '#d97706',
            default => '#dc2626',
        };
        $label = match ($env) {
            'local' => 'AMBIENTE DE DESARROLLO',
            'staging' => 'AMBIENTE DE PRUEBAS',
            default => strtoupper($env),
        };
    @endphp
    <div
        style="
        position: fixed;
        bottom: 16px;
        right: 16px;
        z-index: 99999;
        background: {{ $color }};
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        font-family: monospace;
        letter-spacing: 2px;
        padding: 8px 18px;
        border-radius: 999px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        pointer-events: none;
        user-select: none;
    ">
        {{ $label }}</div>
@endunless
