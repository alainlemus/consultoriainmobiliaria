{{-- Versión del sistema al pie del menú lateral del panel (App\Support\AppVersion). --}}
@php
    $version = \App\Support\AppVersion::class;
    $builtAt = $version::builtAt();
@endphp
<div class="px-4 pb-3 pt-1 text-center text-xs text-gray-400"
    title="{{ collect(['Versión '.$version::number(), $version::fullCommit() ? 'Commit '.$version::fullCommit() : null, $version::branch() ? 'Rama '.$version::branch() : null, $builtAt ? 'Publicada '.$builtAt->timezone('America/Mexico_City')->format('d/m/Y H:i') : null])->filter()->implode(' · ') }}">
    <span style="font-family:ui-monospace,monospace;">Consultoría Inmobiliaria {{ $version::label() }}</span>
</div>
