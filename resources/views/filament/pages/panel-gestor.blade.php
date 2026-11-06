@php
    use App\Models\EncargoGestor;
    $encargos = $this->getEncargos();
    $color = [
        EncargoGestor::POR_REVISAR => 'warning', EncargoGestor::CON_OBSERVACIONES => 'danger',
        EncargoGestor::EN_TRAMITE => 'info', EncargoGestor::POR_VALIDAR => 'warning', EncargoGestor::TERMINADO => 'success', EncargoGestor::CANCELADO => 'gray',
    ];
    $rev = ['pendiente' => 'Sin revisar', 'correcto' => 'Correcto', 'incorrecto' => 'Incorrecto'];
@endphp
<x-filament-panels::page>
    <x-filament::section collapsible :collapsed="$encargos->isNotEmpty()" icon="heroicon-o-book-open" heading="¿Cómo funciona?">
        <ol style="list-style:decimal; padding-left:1.25rem; display:flex; flex-direction:column; gap:0.35rem;">
            <li><b>Te llega un expediente</b> con una notificación: lee qué tramitar, dónde y para cuándo.</li>
            <li><b>Revisa cada documento</b> en la app: márcalo como correcto o incorrecto. Si está mal, escribe por qué.</li>
            <li>Si marcaste alguno mal, la administración lo cambia y te lo regresa. Solo revisas lo que cambió.</li>
            <li>Cuando todos estén correctos, <b>empieza el trámite</b>. Antes no puedes subir documentos.</li>
            <li><b>Sube lo que obtengas</b> desde la app: escaneo, foto, PDF o Word. Si te lo rechazan verás el motivo.</li>
            <li>Cuando subas todo, toca <b>Ya terminé</b> en la app. Si olvidaste algo, puedes <b>reabrirlo</b> mientras la administración valida.</li>
        </ol>
        <p style="margin-top:0.75rem; opacity:0.7;">Aquí en la web puedes ver tus expedientes y descargar sus documentos. La revisión, la subida y «Ya terminé» se hacen en la app.</p>
    </x-filament::section>

    @forelse ($encargos as $e)
        <x-filament::section>
            <x-slot name="heading">
                {{ $e->expediente->folio ?? '#'.$e->expediente_id }} · {{ $e->expediente->acreditado_nombre }}
            </x-slot>
            <x-slot name="afterHeader">
                <x-filament::badge :color="$color[$e->estado] ?? 'gray'">{{ $e->estadoLabel() }}</x-filament::badge>
            </x-slot>

            <div style="display:flex; flex-direction:column; gap:1rem; font-size:0.875rem;">
                <div>
                    <div style="font-weight:600;">Qué tramitar</div>
                    <div>{{ $e->instrucciones }}</div>
                    @if ($e->fecha_entrega)
                        <div style="opacity:0.6; margin-top:0.25rem;">Entrega: {{ $e->fecha_entrega->format('d/m/Y') }}</div>
                    @endif
                </div>

                @foreach ($e->datosExpediente() as $seccion)
                    <div>
                        <div style="font-weight:600; margin-bottom:0.35rem;">{{ $seccion['titulo'] }}</div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(14rem, 1fr)); gap:0.35rem 1.5rem;">
                            @forelse ($seccion['campos'] as $c)
                                <div><span style="opacity:0.6;">{{ $c['label'] }}:</span> {{ $c['valor'] }}</div>
                            @empty
                                <div style="opacity:0.6;">Sin datos capturados.</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                <div>
                    <div style="font-weight:600; margin-bottom:0.35rem;">Documentos del expediente</div>
                    @foreach ($e->documentos as $d)
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:0.35rem 0; border-bottom:1px solid rgba(127,127,127,0.2);">
                            <span>{{ $d->nombre }} <span style="opacity:0.6;">· {{ $rev[$d->pivot->revision] ?? $d->pivot->revision }}</span></span>
                            <x-filament::link :href="$this->urlDescarga($e->expediente_id, $d->id)" icon="heroicon-o-arrow-down-tray" target="_blank">Descargar</x-filament::link>
                        </div>
                    @endforeach
                </div>

                @if ($e->documentosSubidos->isNotEmpty())
                    <div>
                        <div style="font-weight:600; margin-bottom:0.35rem;">Lo que has subido</div>
                        @foreach ($e->documentosSubidos as $d)
                            <div style="padding:0.25rem 0;">
                                {{ $d->nombre }} · <span style="opacity:0.7;">{{ ['en_revision' => 'En revisión', 'validado' => 'Validado', 'rechazado' => 'Rechazado'][$d->validacion] ?? $d->validacion }}</span>
                                @if ($d->motivo_rechazo)<div style="color:#dc2626;">Motivo: {{ $d->motivo_rechazo }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-filament::section>
    @empty
        <x-filament::section>
            <div style="display:flex; flex-direction:column; align-items:center; text-align:center; gap:12px; padding:32px 16px;">
                <x-filament::icon icon="heroicon-o-building-library" style="width:48px; height:48px; color:#B8960C;" />
                <h2 style="font-size:1.125rem; font-weight:700;">Hola, {{ auth()->user()->name }}</h2>
                <p style="max-width:32rem; color:#6b7280;">
                    Aún no tienes expedientes. Cuando la administración te envíe uno, te llegará una notificación
                    y aparecerá aquí con lo que tienes que tramitar.
                </p>
            </div>
        </x-filament::section>
    @endforelse
</x-filament-panels::page>
