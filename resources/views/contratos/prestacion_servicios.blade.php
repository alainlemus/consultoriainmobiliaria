@extends('contratos._layout')
@section('titulo', 'Contrato de Prestación de Servicios Profesionales y Financiamiento de Gastos')

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

    $acreditado = strtoupper($expediente->acreditado_nombre      ?: '________________________');
    $domAcred   = collect([
        $expediente->acreditado_domicilio,
        $expediente->acreditado_colonia  ? 'COL. ' . $expediente->acreditado_colonia : null,
        $expediente->acreditado_cp       ? 'CP ' . $expediente->acreditado_cp : null,
        $expediente->acreditado_municipio,
        $expediente->acreditado_estado,
    ])->filter()->implode(', ') ?: '________________________';

    $curp        = strtoupper($expediente->acreditado_curp        ?: '__________________________');
    $rfc         = strtoupper($expediente->acreditado_rfc         ?: '________________________');
    $nss         = $expediente->acreditado_numero_credito         ?: '________________________';
    $tipoTramite = strtoupper($expediente->tipoTramite?->nombre   ?: 'CRÉDITO');
    $monto       = $expediente->monto_credito
                    ? '$' . number_format($expediente->monto_credito, 2) . ' MXN'
                    : '________________________';
    $pctHon      = $expediente->honorarios_porcentaje
                    ? $expediente->honorarios_porcentaje . '%'
                    : '10%';
    $montoHon    = $expediente->honorarios_monto
                    ? '$' . number_format($expediente->honorarios_monto, 2) . ' MXN'
                    : '________________________';

    // Mapa de placeholders
    $vars = [
        '{ciudad}'           => strtoupper($ciudad),
        '{fecha}'            => strtoupper($fecha),
        '{domicilio}'        => strtoupper($domicilio),
        '{acreditado}'       => $acreditado,
        '{dom_acreditado}'   => strtoupper($domAcred),
        '{tipo_tramite}'     => $tipoTramite,
        '{curp}'             => $curp,
        '{rfc}'              => $rfc,
        '{nss}'              => $nss,
        '{folio}'            => $expediente->folio,
        '{monto_credito}'    => $monto,
        '{pct_honorarios}'   => $pctHon,
        '{monto_honorarios}' => $montoHon,
        '{site_name}'        => strtoupper($siteName),
    ];

    $replace = fn(string $text) => strtr($text, $vars);

    $intro          = $replace(Configuracion::get('contrato_intro', ''));
    $declPrestador  = $replace(Configuracion::get('contrato_declaraciones_prestador', ''));
    $declInteresado = $replace(Configuracion::get('contrato_declaraciones_interesado', ''));
    $clausulas      = $replace(Configuracion::get('contrato_clausulas', ''));
@endphp

<p style="text-align:justify; font-size:9.5pt; margin-top:8px;">
    {!! nl2br(e($intro)) !!}
</p>

<h2>D E C L A R A C I O N E S</h2>

<p style="text-align:justify; font-size:9.5pt;"><strong>POR PARTE DE "EL PRESTADOR":</strong></p>
<p style="text-align:justify; font-size:9.5pt; margin-left:12px;">
    {!! nl2br(e($declPrestador)) !!}
</p>

<p style="text-align:justify; font-size:9.5pt;"><strong>DECLARA EL INTERESADO:</strong></p>
<p style="text-align:justify; font-size:9.5pt; margin-left:12px;">
    {!! nl2br(e($declInteresado)) !!}
</p>

<h2>C L Á U S U L A S</h2>

<p style="text-align:justify; font-size:9.5pt;">
    AMBAS PARTES SE COMPROMETEN A SOMETERSE AL TENOR DE LAS SIGUIENTES CLÁUSULAS SIN QUE EXISTAN VICIOS DE CONSENTIMIENTO:
</p>

<p style="text-align:justify; font-size:9.5pt; margin-left:12px;">
    {!! nl2br(e($clausulas)) !!}
</p>

@if($expediente->vivienda_calle)
<p style="text-align:justify; font-size:10.5pt; margin-top:10px;">
    <strong>INMUEBLE OBJETO DEL TRÁMITE:</strong>
    {{ strtoupper($expediente->vivienda_calle) }} {{ strtoupper($expediente->vivienda_numero ?? '') }},
    COL. {{ strtoupper($expediente->vivienda_colonia ?? '') }},
    {{ strtoupper($expediente->vivienda_municipio ?? '') }},
    {{ strtoupper($expediente->vivienda_estado ?? '') }}
    C.P. {{ $expediente->vivienda_cp ?? '' }}.
</p>
@endif

<p style="text-align:justify; font-size:10.5pt; margin-top:16px;">
    EN LA CIUDAD DE <strong>{{ strtoupper($ciudad) }}</strong>,
    A LOS <strong>{{ strtoupper($fecha) }}</strong>,
    HABIENDO LEÍDO Y COMPRENDIDO EL CONTENIDO DEL PRESENTE CONTRATO, LAS PARTES LO SUSCRIBEN EN SEÑAL DE CONFORMIDAD.
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
                    {{ $acreditado }}<br>
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
                    {{ strtoupper($expediente->obligado_solidario_nombre ?: '________________________') }}
                </div>
            </td>
        </tr>
    </table>
</div>
@endsection
