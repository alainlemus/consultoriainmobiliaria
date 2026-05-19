@extends('contratos._layout')
@section('titulo', 'Convenio de Honorarios Profesionales')

@section('contenido')
@php
    use App\Models\Configuracion;

    $siteName       = Configuracion::get('site_name') ?? 'CONSULTORÍA INMOBILIARIA';
    $firmaPrestador = strtoupper(Configuracion::get('firma_prestador', 'C. JOSE ANTONIO SOLIS SANTUARIO'));
    $firmaJuridico  = strtoupper(Configuracion::get('firma_juridico',  'LIC. LUZ ANGÉLICA PÉREZ MEJÍA'));
    $cobertura      = \App\Models\Cobertura::first();
    $domicilio      = $cobertura?->detalle ?? 'Huejutla de Reyes, Hidalgo';
    $ciudad         = 'Huejutla de Reyes';
    $fecha          = now()->locale('es')->isoFormat('D [DÍAS DEL MES DE] MMMM [DEL AÑO] YYYY');

    $acreditado  = strtoupper($expediente->acreditado_nombre ?: '________________________');
    $curp        = strtoupper($expediente->acreditado_curp   ?: '__________________________');
    $rfc         = strtoupper($expediente->acreditado_rfc    ?: '________________________');
    $tipoTramite = strtoupper($expediente->tipoTramite?->nombre ?: 'CRÉDITO');

    $montoCredito = $expediente->monto_credito
        ? '$' . number_format($expediente->monto_credito, 2) . ' MXN'
        : '________________________';
    $pctHon   = $expediente->honorarios_porcentaje
        ? $expediente->honorarios_porcentaje . '%'
        : '________%';
    $montoHon = $expediente->honorarios_monto
        ? '$' . number_format($expediente->honorarios_monto, 2) . ' MXN'
        : '________________________';

    // Mapa de placeholders
    $vars = [
        '{ciudad}'           => strtoupper($ciudad),
        '{fecha}'            => strtoupper($fecha),
        '{domicilio}'        => strtoupper($domicilio),
        '{acreditado}'       => $acreditado,
        '{tipo_tramite}'     => $tipoTramite,
        '{curp}'             => $curp,
        '{rfc}'              => $rfc,
        '{folio}'            => $expediente->folio,
        '{monto_credito}'    => $montoCredito,
        '{pct_honorarios}'   => $pctHon,
        '{monto_honorarios}' => $montoHon,
        '{site_name}'        => strtoupper($siteName),
    ];

    $replace = fn(string $text) => strtr($text, $vars);

    $intro     = $replace(Configuracion::get('convenio_intro', ''));
    $clausulas = $replace(Configuracion::get('convenio_clausulas', ''));
@endphp

<p style="text-align:justify; font-size:10.5pt; margin-top:10px;">
    {!! nl2br(e($intro)) !!}
</p>

<h2>D A T O S   D E L   T R Á M I T E</h2>

<table class="datos-grid">
    <tr>
        <td class="label">Expediente:</td>
        <td class="valor"><strong>{{ $expediente->folio }}</strong></td>
        <td class="label">Tipo de trámite:</td>
        <td class="valor"><strong>{{ $tipoTramite }}</strong></td>
    </tr>
    <tr>
        <td class="label">Acreditado / Interesado:</td>
        <td class="valor" colspan="3">{{ $acreditado }}</td>
    </tr>
    <tr>
        <td class="label">CURP:</td>
        <td class="valor">{{ $curp }}</td>
        <td class="label">RFC:</td>
        <td class="valor">{{ $rfc }}</td>
    </tr>
    @if($expediente->monto_credito)
    <tr>
        <td class="label">Monto del crédito aprobado:</td>
        <td class="valor" colspan="3"><strong>{{ $montoCredito }}</strong></td>
    </tr>
    @endif
</table>

<h2>C O N D I C I O N E S   D E   H O N O R A R I O S</h2>

<p style="text-align:justify; font-size:10.5pt; margin-left:14px;">
    {!! nl2br(e($clausulas)) !!}
</p>

<p style="text-align:justify; font-size:10.5pt; margin-top:16px;">
    EN LA CIUDAD DE <strong>{{ strtoupper($ciudad) }}</strong>,
    A LOS <strong>{{ strtoupper($fecha) }}</strong>,
    HABIENDO LEÍDO Y COMPRENDIDO EL CONTENIDO DEL PRESENTE CONVENIO, LAS PARTES LO SUSCRIBEN EN SEÑAL DE CONFORMIDAD.
</p>

<div class="firma-bloque">
    <table class="firmas">
        <tr>
            <td style="height:70px;"></td>
            <td style="height:70px;"></td>
        </tr>
        <tr>
            <td>
                <div class="linea-firma">
                    <strong>FIRMA DE "EL PRESTADOR"</strong><br>
                    {{ $firmaPrestador }}<br>
                    <small>{{ strtoupper($siteName) }}</small>
                </div>
            </td>
            <td>
                <div class="linea-firma">
                    <strong>FIRMA DEL "INTERESADO"</strong><br>
                    C. {{ $acreditado }}<br>
                    <small>RFC: {{ $rfc }} &nbsp; CURP: {{ $curp }}</small>
                </div>
            </td>
        </tr>
    </table>

    <table class="firmas" style="margin-top:40px;">
        <tr>
            <td style="height:70px;"></td>
            <td style="height:70px;"></td>
        </tr>
        <tr>
            <td>
                <div class="linea-firma">
                    <strong>FIRMA POR PARTE DEL JURÍDICO</strong><br>
                    {{ $firmaJuridico }}
                </div>
            </td>
            <td>
                <div class="linea-firma">
                    <strong>FIRMA DEL "OBLIGADO SOLIDARIO"</strong><br>
                    C. {{ strtoupper($expediente->obligado_solidario_nombre ?: '________________________') }}
                </div>
            </td>
        </tr>
    </table>
</div>
@endsection
