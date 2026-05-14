@extends('contratos._layout')
@section('titulo', 'Contrato de Prestación de Servicios Profesionales y Financiamiento de Gastos')

@section('contenido')
@php
    $siteName   = \App\Models\Configuracion::get('site_name') ?? 'CONSULTORÍA INMOBILIARIA';
    $cobertura  = \App\Models\Cobertura::first();
    $domicilio  = $cobertura?->detalle ?? 'Huejutla de Reyes, Hidalgo';
    $ciudad     = 'Huejutla de Reyes';
    $fecha      = now()->locale('es')->isoFormat('D [DÍAS DEL MES DE] MMMM [DEL AÑO] YYYY');

    $acreditado = strtoupper($expediente->acreditado_nombre      ?: '________________________');
    $domAcred   = collect([
        $expediente->acreditado_domicilio,
        $expediente->acreditado_colonia  ? 'COL. ' . $expediente->acreditado_colonia : null,
        $expediente->acreditado_cp       ? 'CP ' . $expediente->acreditado_cp : null,
        $expediente->acreditado_municipio,
        $expediente->acreditado_estado,
    ])->filter()->implode(', ') ?: '________________________';

    $curp        = strtoupper($expediente->acreditado_curp            ?: '__________________________');
    $rfc         = strtoupper($expediente->acreditado_rfc             ?: '________________________');
    $nss         = $expediente->acreditado_numero_credito             ?: '________________________';
    $tipoTramite = strtoupper($expediente->tipoTramite?->nombre       ?: 'CRÉDITO');
    $monto       = $expediente->monto_credito
                    ? '$' . number_format($expediente->monto_credito, 2) . ' MXN'
                    : '________________________';
    $pctHon      = $expediente->honorarios_porcentaje
                    ? $expediente->honorarios_porcentaje . '%'
                    : '10%';
    $montoHon    = $expediente->honorarios_monto
                    ? '$' . number_format($expediente->honorarios_monto, 2) . ' MXN'
                    : '________________________';
@endphp

<p style="text-align:justify; font-size:9.5pt; margin-top:8px;">
    EN LA CIUDAD DE <strong>{{ strtoupper($ciudad) }}</strong> A LOS <strong>{{ strtoupper($fecha) }}</strong>,
    CELEBRAN EL PRESENTE <strong>CONTRATO DE PRESTACIÓN DE SERVICIOS PROFESIONALES Y FINANCIAMIENTO DE GASTOS</strong>
    POR UNA PARTE EL <strong>LIC. JOSE ANTONIO SOLIS SANTUARIO</strong>, EN ADELANTE <strong>"EL PRESTADOR"</strong>,
    CON DOMICILIO EN <strong>{{ strtoupper($domicilio) }}</strong>.
    Y POR LA OTRA EL C. <strong>{{ $acreditado }}</strong>, EN ADELANTE <strong>"EL INTERESADO"</strong>,
    QUIEN CUENTA CON DOMICILIO EN <strong>{{ strtoupper($domAcred) }}</strong>,
    QUIENES SE RECONOCEN CON CAPACIDADES LEGALES PARA OBLIGARSE,
    SUJETÁNDOSE A LAS SIGUIENTES:
</p>

<h2>D E C L A R A C I O N E S</h2>

<p style="text-align:justify; font-size:9.5pt;"><strong>POR PARTE DE "EL PRESTADOR":</strong></p>
<p style="text-align:justify; font-size:9.5pt; margin-left:12px;">
    1.- QUE ES UNA EMPRESA CON PLENA CAPACIDAD LEGAL Y EXPERIENCIA EN LA TRAMITACIÓN DE {{ $tipoTramite }}.<br>
    2.- QUE SE ESPECIALIZA EN REALIZAR ÚNICAMENTE EL TRÁMITE JUNTO AL "INTERESADO" SIEMPRE Y CUANDO EXISTA UN {{ $tipoTramite }} VIGENTE.<br>
    3.- QUE EL DOMICILIO DE "EL PRESTADOR" ESTÁ UBICADO EN {{ strtoupper($domicilio) }}.<br>
    4.- "EL PRESTADOR" ES EL ENCARGADO DE REALIZAR EL TRÁMITE EN CUESTIÓN DEL {{ $tipoTramite }}.<br>
    5.- QUE "EL PRESTADOR" FINANCIARÁ CON RECURSOS PROPIOS, EN CALIDAD DE PRÉSTAMO TEMPORAL,
    LOS GASTOS ESTRICTAMENTE NECESARIOS PARA EL TRÁMITE DEL {{ $tipoTramite }} DE "EL INTERESADO",
    TALES COMO: EL AVALÚO DEL PREDIO O CASA HABITACIÓN, GASTOS ANTE EL REGISTRO PÚBLICO DE LA PROPIEDAD,
    ESCRITURAS PÚBLICAS Y OTROS GASTOS INDISPENSABLES PREVIAMENTE AUTORIZADOS POR ESCRITO POR "EL INTERESADO".
    DICHOS GASTOS SERÁN REEMBOLSADOS AL FINALIZAR EL TRÁMITE POR EL "INTERESADO".
</p>

<p style="text-align:justify; font-size:9.5pt;"><strong>DECLARA EL INTERESADO:</strong></p>
<p style="text-align:justify; font-size:9.5pt; margin-left:12px;">
    1.- QUE CUENTA CON UN {{ $tipoTramite }} VIGENTE Y CAPACIDAD LEGAL PARA OBLIGARSE EN ESTE ACTO.<br>
    2.- QUE ACEPTA QUE "EL PRESTADOR" FINANCIE LOS GASTOS ANTES INDICADOS, COMPROMETIÉNDOSE A REEMBOLSAR CONFORME LO PACTADO.<br>
    3.- QUIEN SE IDENTIFICA CON CURP <strong>{{ $curp }}</strong>
    @if($expediente->acreditado_numero_credito)
    Y NÚMERO DE SEGURIDAD SOCIAL / CRÉDITO <strong>{{ $nss }}</strong>
    @endif
    , QUIEN BAJO PROTESTA DE DECIR VERDAD ASEGURA CONTAR CON EL DERECHO DE PODER REALIZAR EL TRÁMITE.<br>
    4.- QUE CUENTA CON DOMICILIO EN <strong>{{ strtoupper($domAcred) }}</strong>.<br>
    5.- QUE CUENTA CON RFC <strong>{{ $rfc }}</strong>.<br>
    6.- QUE SE ENCUENTRA BIEN DE SUS FACULTADES MENTALES Y CUENTA CON EL DERECHO DE PODER REALIZAR EL TRÁMITE DEL {{ $tipoTramite }}.
</p>

<h2>C L Á U S U L A S</h2>

<p style="text-align:justify; font-size:9.5pt;">
    AMBAS PARTES SE COMPROMETEN A SOMETERSE AL TENOR DE LAS SIGUIENTES CLÁUSULAS SIN QUE EXISTAN VICIOS DE CONSENTIMIENTO:
</p>

<p style="text-align:justify; font-size:9.5pt; margin-left:12px;">
    <strong>A.-)</strong> AMBAS PARTES ESTÁN TOTALMENTE DE ACUERDO EN QUE SE REALICE EL TRÁMITE DEL {{ $tipoTramite }}.<br>
    <strong>B.-)</strong> "EL PRESTADOR" SE COMPROMETE A DESEMPEÑAR TODO SU CONOCIMIENTO PARA CUMPLIR SATISFACTORIAMENTE
    EL OBJETIVO DEL PRESENTE CONTRATO BAJO SU EXPERIENCIA, ASÍ COMO RESPONDER POR LA CALIDAD DE SUS SERVICIOS
    Y DE CUALQUIER INCIDENTE QUE SUCEDA REFERENTE AL TRÁMITE DE "EL INTERESADO".<br>
    <strong>C.-)</strong> EL "INTERESADO" SE OBLIGA A BRINDAR TODA LA INFORMACIÓN QUE SE REQUIERA POR PARTE DE "EL PRESTADOR"
    PARA PODER LLEVAR A CABO EL TRÁMITE DEL {{ $tipoTramite }}.<br>
    <strong>D.-)</strong> "EL PRESTADOR" FINANCIARÁ LOS GASTOS QUE CONLLEVE EL TRÁMITE TALES COMO AVALÚO,
    ESCRITURAS PÚBLICAS Y LOS DEMÁS QUE RESULTEN, OTORGÁNDOLOS EN FORMA DE PRÉSTAMO A "EL INTERESADO".<br>
    <strong>E.-)</strong> "EL INTERESADO" SE COMPROMETE A REEMBOLSAR LOS GASTOS MENCIONADOS EN LA CLÁUSULA "D"
    AL MOMENTO DE FORMALIZAR EL TRÁMITE.<br>
    <strong>F.-)</strong> "EL INTERESADO" REALIZARÁ LA ENTREGA DEL REMANENTE EN UNA SOLA EXHIBICIÓN.<br>
    <strong>G.-)</strong> "EL INTERESADO" LE COMUNICARÁ A "EL PRESTADOR" CUALQUIER HECHO QUE SE SUSCITE DURANTE EL PROCESO.<br>
    <strong>H.-)</strong> "EL PRESTADOR" PODRÁ RESCINDIR EL PRESENTE CONTRATO SIN TENER CLÁUSULAS PENALES NI RESPONSABILIDADES.<br>
    <strong>I.-)</strong> POR PARTE DE "EL INTERESADO" NO PODRÁ RESCINDIR DICHO CONTRATO SIN CAUSA JUSTIFICADA.<br>
    <strong>J.-)</strong> EN CASO DE QUE "EL INTERESADO" RESCINDA EL CONTRATO, SE VERÁ EN LA NECESIDAD DE CUBRIR
    LOS PAGOS DE GASTOS REALIZADOS TALES COMO VALUADOR Y TRÁMITES NOTARIALES, ASÍ COMO EL
    <strong>20% DEL MONTO TOTAL DEL {{ $tipoTramite }}</strong>.<br>
    <strong>K.-)</strong> "EL INTERESADO" SE COMPROMETE A NO COMETER ACTOS DE MOLESTIA NI ACTOS ILÍCITOS CONTRA
    "EL PRESTADOR", NI CAUSAR DAÑOS MORALES NI PATRIMONIALES.<br>
    <strong>L.-)</strong> "EL INTERESADO" ACUDIRÁ A LAS INSTALACIONES DE "EL PRESTADOR" CUANDO SE LE SOLICITE,
    EN RAZÓN DE REQUERIR FIRMA O REQUISITOS ADICIONALES.<br>
    <strong>M.-)</strong> EN CUESTIÓN DE LOS HONORARIOS DE "EL PRESTADOR", "EL INTERESADO" ACEPTA PAGAR
    <strong>{{ $pctHon }} DE HONORARIOS SOBRE EL MONTO TOTAL DEL CRÉDITO</strong>
    @if($expediente->honorarios_monto)
    , EQUIVALENTE A <strong>{{ $montoHon }}</strong>
    @endif
    .<br>
    <strong>N.-)</strong> DERIVADO DEL INCUMPLIMIENTO DEL INCISO ANTERIOR, "EL PRESTADOR" SE VERÁ EN LA NECESIDAD
    DE ACUDIR ANTE LOS TRIBUNALES CIVILES COMPETENTES PARA HACER CUMPLIR EL PRESENTE CONTRATO.<br>
    <strong>Ñ.-)</strong> "LAS PARTES" MANIFIESTAN QUE A LA FIRMA DEL PRESENTE CONTRATO NO EXISTE DOLO, ERROR,
    VIOLENCIA, MALA FE O CUALQUIER OTRO VICIO DE CONSENTIMIENTO QUE PUDIERA INVALIDARLO.
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
                    C. JOSE ANTONIO SOLIS SANTUARIO<br>
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
                    LIC. LUZ ANGÉLICA PÉREZ MEJÍA
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
