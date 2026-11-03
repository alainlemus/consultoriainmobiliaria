@php
    use App\Models\EncargoGestor;
    $rev = ['pendiente' => ['Sin revisar', '#6b7280'], 'correcto' => ['Correcto', '#15803d'], 'incorrecto' => ['Incorrecto', '#b91c1c']];
    $val = ['en_revision' => ['En revisión', '#1d4ed8'], 'validado' => ['Validado', '#15803d'], 'rechazado' => ['Rechazado', '#b91c1c']];
    $eventos = [
        'enviado' => 'Enviado al gestor', 'revision_con_observaciones' => 'El gestor marcó documentos como incorrectos',
        'reenviado' => 'Corregido y reenviado', 'tramite_iniciado' => 'Todo correcto: empezó el trámite',
        'documento_subido' => 'El gestor subió un documento', 'documento_validado' => 'Documento validado',
        'documento_rechazado' => 'Documento rechazado', 'terminado' => 'Encargo terminado', 'cancelado' => 'Encargo cancelado',
    ];
@endphp
<div style="display:flex; flex-direction:column; gap:1.25rem; font-size:0.875rem;">
    <div>
        <div style="font-weight:600; margin-bottom:0.25rem;">Qué tramitar</div>
        <div style="opacity:0.85;">{{ $encargo->instrucciones }}</div>
        <div style="opacity:0.6; margin-top:0.25rem;">
            {{ $encargo->estadoLabel() }} · vuelta {{ $encargo->vuelta }}
            @if ($encargo->fecha_entrega) · entrega {{ $encargo->fecha_entrega->format('d/m/Y') }} @endif
        </div>
    </div>

    <div>
        <div style="font-weight:600; margin-bottom:0.5rem;">Documentos compartidos</div>
        @foreach ($encargo->documentos as $d)
            @php [$t, $c] = $rev[$d->pivot->revision] ?? [$d->pivot->revision, '#6b7280']; @endphp
            <div style="display:flex; justify-content:space-between; gap:1rem; padding:0.4rem 0; border-bottom:1px solid rgba(127,127,127,0.2);">
                <div>
                    {{ $d->nombre }}
                    @if ($d->pivot->nota)<div style="color:#dc2626;">Gestor: “{{ $d->pivot->nota }}”</div>@endif
                </div>
                <span style="color:{{ $c }}; font-weight:600; white-space:nowrap;">{{ $t }}</span>
            </div>
        @endforeach
    </div>

    <div>
        <div style="font-weight:600; margin-bottom:0.5rem;">Lo que subió el gestor</div>
        @forelse ($encargo->documentosSubidos as $d)
            @php [$t, $c] = $val[$d->validacion] ?? [$d->validacion, '#6b7280']; @endphp
            <div style="display:flex; justify-content:space-between; gap:1rem; padding:0.4rem 0; border-bottom:1px solid rgba(127,127,127,0.2);">
                <div>
                    {{ $d->nombre }}
                    @if ($d->notas)<div style="opacity:0.7;">“{{ $d->notas }}”</div>@endif
                    @if ($d->motivo_rechazo)<div style="color:#dc2626;">Rechazo: {{ $d->motivo_rechazo }}</div>@endif
                </div>
                <span style="color:{{ $c }}; font-weight:600; white-space:nowrap;">{{ $t }}</span>
            </div>
        @empty
            <div style="opacity:0.6;">Todavía nada. Valida o rechaza lo que suba en la pestaña Documentos.</div>
        @endforelse
    </div>

    <div>
        <div style="font-weight:600; margin-bottom:0.5rem;">Historial</div>
        @foreach ($encargo->eventos as $ev)
            <div style="padding:0.25rem 0;">
                <span style="opacity:0.6;">{{ $ev->created_at?->format('d/m H:i') }}</span>
                · {{ $eventos[$ev->tipo] ?? $ev->tipo }}
                @if ($ev->detalle)<span style="opacity:0.7;"> — {{ $ev->detalle }}</span>@endif
                @if ($ev->user)<span style="opacity:0.6;"> ({{ $ev->user->name }})</span>@endif
            </div>
        @endforeach
    </div>
</div>
