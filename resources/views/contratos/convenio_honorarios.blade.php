@extends('contratos._layout')
@section('titulo', 'Convenio de Honorarios Profesionales')

@section('contenido')
@php
    $siteName   = \App\Models\Configuracion::get('site_name') ?? 'CONSULTORÍA INMOBILIARIA';
    $cobertura  = \App\Models\Cobertura::first();
    $domicilio  = $cobertura?->detalle ?? 'Huejutla de Reyes, Hidalgo';
    $ciudad     = 'Huejutla de Reyes';
    $fecha      = now()->locale('es')->isoFormat('D [DÍAS DEL MES DE] MMMM [DEL AÑO] YYYY');

    $acreditado = strtoupper($expediente->acreditado_nombre ?: '________________________');
    $curp       = strtoupper($expediente->acreditado_curp   ?: '__________________________');
    $rfc        = strtoupper($expediente->acreditado_rfc    ?: '________________________');
    $tipoTramite= strtoupper($expediente->tipoTramite?->nombre ?: 'CRÉDITO');

    $montoCredito = $expediente->monto_credito
        ? '$' . number_format($expediente->monto_credito, 2) . ' MXN'
        : '________________________';

    $pctHon   = $expediente->honorarios_porcentaje
        ? $expediente->honorarios_porcentaje . '%'
        : '________%';

    $montoHon = $expediente->honorarios_monto
        ? '$' . number_format($expediente->honorarios_monto, 2) . ' MXN'
        : '________________________';
@endphp

<p style="text-align:justify; font-size:10.5pt; margin-top:10px;">
    EN LA CIUDAD DE <strong>{{ strtoupper($ciudad) }}</strong> A LOS <strong>{{ strtoupper($fecha) }}</strong>,
    CELEBRAN EL PRESENTE <strong>CONVENIO DE HONORARIOS PROFESIONALES</strong>
    POR UNA PARTE <strong>LIC. JOSE ANTONIO SOLIS SANTUARIO</strong>, EN ADELANTE <strong>"EL PRESTADOR"</strong>,
    CON DOMICILIO EN <strong>{{ strtoupper($domicilio) }}</strong>.
    Y POR LA OTRA EL C. <strong>{{ $acreditado }}</strong>, EN ADELANTE <strong>"EL INTERESADO"</strong>,
    QUIENES ACUERDAN LAS CONDICIONES DE RETRIBUCIÓN POR LOS SERVICIOS PROFESIONALES PRESTADOS,
    EN TÉRMINOS DEL CONTRATO DE PRESTACIÓN DE SERVICIOS PROFESIONALES Y FINANCIAMIENTO DE GASTOS
    SUSCRITO CON NÚMERO DE EXPEDIENTE <strong>{{ $expediente->folio }}</strong>.
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
    <strong>PRIMERA. MONTO DE HONORARIOS.-</strong>
    "EL INTERESADO" ACUERDA PAGAR A "EL PRESTADOR" POR CONCEPTO DE HONORARIOS PROFESIONALES
    LA CANTIDAD DE <strong>{{ $montoHon }}</strong>
    @if($expediente->honorarios_porcentaje)
    , EQUIVALENTE AL <strong>{{ $pctHon }}</strong> DEL MONTO TOTAL DEL {{ $tipoTramite }}
    @endif
    . DICHO MONTO INCLUYE LA GESTIÓN INTEGRAL DEL TRÁMITE, ASESORÍA PERSONALIZADA Y SEGUIMIENTO HASTA SU FORMALIZACIÓN.
</p>

<p style="text-align:justify; font-size:10.5pt; margin-left:14px;">
    <strong>SEGUNDA. FORMA DE PAGO.-</strong>
    "EL INTERESADO" SE COMPROMETE A PAGAR LOS HONORARIOS EN UNA SOLA EXHIBICIÓN AL MOMENTO EN QUE SE
    FORMALICE EL TRÁMITE Y SE LIBERE EL RECURSO DEL {{ $tipoTramite }} POR LA INSTITUCIÓN CORRESPONDIENTE.
    NO SE ACEPTARÁN PAGOS PARCIALES SALVO ACUERDO EXPRESO Y POR ESCRITO ENTRE LAS PARTES.
</p>

<p style="text-align:justify; font-size:10.5pt; margin-left:14px;">
    <strong>TERCERA. SERVICIOS INCLUIDOS.-</strong>
    LOS HONORARIOS PACTADOS AMPARAN LOS SIGUIENTES SERVICIOS:
</p>
<ul style="margin-left:32px; font-size:10.5pt; margin-bottom:10px;">
    <li>Integración y revisión del expediente documental.</li>
    <li>Gestión ante la institución de crédito correspondiente ({{ $tipoTramite }}).</li>
    <li>Coordinación con valuador y notario público.</li>
    <li>Seguimiento de etapas hasta la firma de escrituras o entrega del recurso.</li>
    <li>Asesoría y atención personalizada durante todo el proceso.</li>
</ul>

<p style="text-align:justify; font-size:10.5pt; margin-left:14px;">
    <strong>CUARTA. GASTOS ADICIONALES.-</strong>
    LOS GASTOS DERIVADOS DEL TRÁMITE TALES COMO AVALÚO, DERECHOS REGISTRALES, ESCRITURAS Y OTROS COSTOS
    INSTITUCIONALES <strong>NO ESTÁN INCLUIDOS</strong> EN LOS HONORARIOS Y SERÁN CUBIERTOS CONFORME
    A LO PACTADO EN EL CONTRATO DE PRESTACIÓN DE SERVICIOS CORRESPONDIENTE.
</p>

<p style="text-align:justify; font-size:10.5pt; margin-left:14px;">
    <strong>QUINTA. RESCISIÓN.-</strong>
    EN CASO DE QUE "EL INTERESADO" DESISTA DEL TRÁMITE UNA VEZ INICIADAS LAS GESTIONES, SE OBLIGARÁ
    A CUBRIR LOS GASTOS YA EROGADOS POR "EL PRESTADOR" Y EL <strong>20% DEL MONTO TOTAL DEL CRÉDITO</strong>
    EN CONCEPTO DE PENALIZACIÓN POR RESCISIÓN, CONFORME A LO ESTABLECIDO EN EL CONTRATO DE PRESTACIÓN DE SERVICIOS.
</p>

<p style="text-align:justify; font-size:10.5pt; margin-left:14px;">
    <strong>SEXTA. CONFIDENCIALIDAD.-</strong>
    LAS PARTES SE OBLIGAN A MANTENER LA CONFIDENCIALIDAD DE TODA LA INFORMACIÓN FINANCIERA Y PERSONAL
    COMPARTIDA DURANTE LA VIGENCIA DEL PRESENTE CONVENIO Y CON POSTERIORIDAD A SU CONCLUSIÓN.
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
