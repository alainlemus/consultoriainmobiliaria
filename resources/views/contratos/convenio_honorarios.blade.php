@extends('contratos._layout')
@section('titulo', 'Convenio de Honorarios')

@section('contenido')
<h2>Partes</h2>
<table class="datos-grid">
    <tr>
        <td class="label">Consultoría:</td>
        <td class="valor" colspan="3"><strong>CONSULTORÍA INMOBILIARIA</strong></td>
    </tr>
    <tr>
        <td class="label">Asesor responsable:</td>
        <td class="valor">{{ strtoupper($expediente->asesor?->name ?: '________________________') }}</td>
        <td class="label">Expediente:</td>
        <td class="valor"><strong>{{ $expediente->folio }}</strong></td>
    </tr>
    <tr>
        <td class="label">Cliente:</td>
        <td class="valor">{{ strtoupper($expediente->acreditado_nombre ?: '________________________') }}</td>
        <td class="label">RFC:</td>
        <td class="valor">{{ strtoupper($expediente->acreditado_rfc ?: '________________________') }}</td>
    </tr>
    <tr>
        <td class="label">Trámite:</td>
        <td class="valor" colspan="3"><strong>{{ $expediente->tipoTramite?->nombre ?: '________________________' }}</strong></td>
    </tr>
</table>

<h2>Condiciones Económicas</h2>
<table class="datos-grid" style="margin-bottom:20px;">
    @if($expediente->honorarios_porcentaje)
    <tr>
        <td class="label">Porcentaje de honorarios:</td>
        <td class="valor"><span class="monto-destacado">{{ $expediente->honorarios_porcentaje }}%</span></td>
        <td class="label">Base de cálculo:</td>
        <td class="valor">Monto total del crédito / operación</td>
    </tr>
    @endif
    <tr>
        <td class="label">Monto de honorarios pactado:</td>
        <td class="valor" colspan="3">
            <span class="monto-destacado">
                ${{ number_format($expediente->honorarios_monto ?: 0, 2) }} MXN
            </span>
            @if($expediente->honorarios_porcentaje)
            <br><small style="color:#888;">(equivalente al {{ $expediente->honorarios_porcentaje }}% sobre el monto de la operación)</small>
            @endif
        </td>
    </tr>
    @if($expediente->monto_credito)
    <tr>
        <td class="label">Monto estimado de la operación:</td>
        <td class="valor" colspan="3">${{ number_format($expediente->monto_credito, 2) }} MXN</td>
    </tr>
    @endif
</table>

<h2>Cláusulas</h2>

<div class="clausula">
    <p><span class="numero">PRIMERA. OBJETO.</span> El presente Convenio tiene por objeto establecer las condiciones de pago de los honorarios profesionales que percibirá <strong>CONSULTORÍA INMOBILIARIA</strong> por la prestación de los servicios de gestión del trámite de <strong>{{ $expediente->tipoTramite?->nombre ?: '________________' }}</strong>, de conformidad con el Contrato de Prestación de Servicios suscrito bajo el expediente <strong>{{ $expediente->folio }}</strong>.</p>
</div>

<div class="clausula">
    <p><span class="numero">SEGUNDA. MONTO DE HONORARIOS.</span> Las partes acuerdan que el monto total de los honorarios profesionales asciende a la cantidad de
    <strong>${{ number_format($expediente->honorarios_monto ?: 0, 2) }} ({{ \App\Helpers\NumeroLetras::convertir($expediente->honorarios_monto ?: 0) }} MXN 00/100)</strong>,
    @if($expediente->honorarios_porcentaje)
    equivalente al <strong>{{ $expediente->honorarios_porcentaje }}%</strong> del monto total de la operación,
    @endif
    que el CLIENTE se obliga a cubrir a CONSULTORÍA INMOBILIARIA conforme a las condiciones establecidas en el presente convenio.</p>
</div>

<div class="clausula">
    <p><span class="numero">TERCERA. FORMA Y MOMENTO DE PAGO.</span> Los honorarios serán pagados por el CLIENTE en la siguiente forma:</p>
    <ul style="margin-left:20px; margin-bottom:10px;">
        <li><strong>50%</strong> al inicio del trámite y firma del presente convenio.</li>
        <li><strong>50%</strong> restante a la formalización y/o cierre exitoso del trámite.</li>
    </ul>
    <p>El pago podrá realizarse mediante transferencia bancaria, depósito o cualquier otro medio acordado por escrito entre las partes.</p>
</div>

<div class="clausula">
    <p><span class="numero">CUARTA. CAUSAS DE CANCELACIÓN SIN CARGO.</span> No se cobrarán honorarios en caso de que el trámite no pueda concretarse por causas imputables exclusivamente a CONSULTORÍA INMOBILIARIA o a la institución crediticia, y siempre que no se haya devengado trabajo efectivo por parte del prestador.</p>
</div>

<div class="clausula">
    <p><span class="numero">QUINTA. GASTOS ADICIONALES.</span> Los gastos de avalúo, derechos notariales, impuestos, gestorías ante dependencias gubernamentales y demás erogaciones necesarias para la formalización del trámite corren por cuenta del CLIENTE, salvo pacto expreso en contrario. CONSULTORÍA INMOBILIARIA podrá anticipar dichos gastos, los cuales serán reembolsados por el CLIENTE.</p>
</div>

<div class="clausula">
    <p><span class="numero">SEXTA. VIGENCIA Y MODIFICACIONES.</span> El presente convenio es válido a partir de la fecha de firma. Cualquier modificación deberá constar por escrito y estar suscrita por ambas partes.</p>
</div>

<div class="clausula">
    <p>En la Ciudad de México, a los <strong>{{ now()->locale('es')->isoFormat('D [días del mes de] MMMM [del año] YYYY') }}</strong>, ambas partes, habiendo leído y comprendido el presente convenio, lo firman de conformidad.</p>
</div>

<div class="firma-bloque">
    <table class="firmas">
        <tr>
            <td style="height:70px;"></td>
            <td style="height:70px;"></td>
        </tr>
        <tr>
            <td>
                <div class="linea-firma">
                    <strong>EL CLIENTE</strong><br>
                    {{ strtoupper($expediente->acreditado_nombre ?: '________________________________') }}<br>
                    RFC: {{ strtoupper($expediente->acreditado_rfc ?: '____________') }}
                </div>
            </td>
            <td>
                <div class="linea-firma">
                    <strong>CONSULTORÍA INMOBILIARIA</strong><br>
                    {{ strtoupper($expediente->asesor?->name ?: '________________________________') }}<br>
                    Asesor responsable
                </div>
            </td>
        </tr>
    </table>
</div>
@endsection
