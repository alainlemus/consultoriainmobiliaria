@extends('contratos._layout')
@section('titulo', 'Contrato de Prestación de Servicios')

@section('contenido')
<h2>Partes Contratantes</h2>
<table class="datos-grid">
    <tr>
        <td class="label">Prestador del servicio:</td>
        <td class="valor" colspan="3"><strong>CONSULTORÍA INMOBILIARIA</strong>, representada por el Asesor <strong>{{ strtoupper($expediente->asesor?->name ?: '________________________') }}</strong></td>
    </tr>
    <tr>
        <td class="label">Cliente (Acreditado):</td>
        <td class="valor" colspan="3">{{ strtoupper($expediente->acreditado_nombre ?: '________________________') }}</td>
    </tr>
    <tr>
        <td class="label">RFC del cliente:</td>
        <td class="valor">{{ strtoupper($expediente->acreditado_rfc ?: '________________________') }}</td>
        <td class="label">CURP:</td>
        <td class="valor">{{ strtoupper($expediente->acreditado_curp ?: '________________________') }}</td>
    </tr>
    <tr>
        <td class="label">Domicilio del cliente:</td>
        <td class="valor" colspan="3">
            {{ $expediente->acreditado_domicilio ?: '' }}
            {{ $expediente->acreditado_colonia ? ', Col. ' . $expediente->acreditado_colonia : '' }}
            {{ $expediente->acreditado_municipio ? ', ' . $expediente->acreditado_municipio : '' }}
            {{ $expediente->acreditado_estado ? ', ' . $expediente->acreditado_estado : '' }}
            {{ $expediente->acreditado_cp ? ' C.P. ' . $expediente->acreditado_cp : '' }}
            @if(!$expediente->acreditado_domicilio) ________________________ @endif
        </td>
    </tr>
    <tr>
        <td class="label">Teléfono:</td>
        <td class="valor">{{ $expediente->acreditado_telefono ?: '________________________' }}</td>
        <td class="label">Correo electrónico:</td>
        <td class="valor">{{ $expediente->acreditado_email ?: '________________________' }}</td>
    </tr>
</table>

<h2>Objeto del Contrato</h2>
<table class="datos-grid">
    <tr>
        <td class="label">Tipo de trámite:</td>
        <td class="valor"><strong>{{ $expediente->tipoTramite?->nombre ?: '________________________' }}</strong></td>
        <td class="label">Expediente:</td>
        <td class="valor"><strong>{{ $expediente->folio }}</strong></td>
    </tr>
    @if($expediente->vivienda_calle)
    <tr>
        <td class="label">Inmueble objeto:</td>
        <td class="valor" colspan="3">
            {{ $expediente->vivienda_calle }} {{ $expediente->vivienda_numero }},
            Col. {{ $expediente->vivienda_colonia }},
            {{ $expediente->vivienda_municipio }}, {{ $expediente->vivienda_estado }}
            C.P. {{ $expediente->vivienda_cp }}
        </td>
    </tr>
    @endif
    @if($expediente->uso_credito)
    <tr>
        <td class="label">Uso del crédito:</td>
        <td class="valor" colspan="3">{{ $expediente->uso_credito }}</td>
    </tr>
    @endif
    @if($expediente->monto_credito)
    <tr>
        <td class="label">Monto estimado del crédito:</td>
        <td class="valor" colspan="3"><strong>${{ number_format($expediente->monto_credito, 2) }} MXN</strong></td>
    </tr>
    @endif
</table>

<h2>Cláusulas</h2>

<div class="clausula">
    <p><span class="numero">PRIMERA. OBJETO.</span> <strong>CONSULTORÍA INMOBILIARIA</strong> se obliga a prestar al <em>CLIENTE</em> los servicios profesionales de gestión, asesoría y tramitación del crédito de tipo <strong>{{ $expediente->tipoTramite?->nombre ?: '________________' }}</strong>, así como todas las gestiones necesarias ante las instituciones correspondientes para la formalización del trámite indicado.</p>
</div>

<div class="clausula">
    <p><span class="numero">SEGUNDA. OBLIGACIONES DEL PRESTADOR.</span> CONSULTORÍA INMOBILIARIA se compromete a:</p>
    <ul style="margin-left:20px; margin-bottom:10px;">
        <li>Asesorar al cliente durante todo el proceso de tramitación.</li>
        <li>Integrar y gestionar el expediente documental requerido por las instituciones.</li>
        <li>Mantener informado al cliente sobre el estado y avance del trámite.</li>
        <li>Actuar con diligencia, confidencialidad y ética profesional.</li>
        <li>Entregar al cliente copia de los documentos que le correspondan al finalizar el trámite.</li>
    </ul>
</div>

<div class="clausula">
    <p><span class="numero">TERCERA. OBLIGACIONES DEL CLIENTE.</span> El CLIENTE se compromete a:</p>
    <ul style="margin-left:20px; margin-bottom:10px;">
        <li>Proporcionar verazmente toda la documentación e información solicitada en tiempo y forma.</li>
        <li>Cubrir los honorarios pactados en el Convenio de Honorarios correspondiente.</li>
        <li>Asistir puntualmente a las citas y firmas que sean requeridas.</li>
        <li>Notificar cualquier cambio en su situación laboral, crediticia o personal que pueda afectar el trámite.</li>
    </ul>
</div>

<div class="clausula">
    <p><span class="numero">CUARTA. CONFIDENCIALIDAD.</span> Ambas partes acuerdan mantener la confidencialidad de toda información personal, financiera y documental compartida durante la vigencia del presente contrato y posterior a su conclusión.</p>
</div>

<div class="clausula">
    <p><span class="numero">QUINTA. VIGENCIA.</span> El presente contrato inicia a partir de la fecha de firma y concluye con la formalización del trámite objeto del mismo, o bien con la cancelación expresa y documentada de cualquiera de las partes.</p>
</div>

<div class="clausula">
    <p><span class="numero">SEXTA. JURISDICCIÓN.</span> Para la interpretación y cumplimiento del presente contrato, las partes se someten a las leyes vigentes aplicables y a la jurisdicción de los tribunales competentes de la Ciudad de México, renunciando a cualquier otro fuero que pudiera corresponderles.</p>
</div>

<div class="clausula">
    <p>En la Ciudad de México, a los <strong>{{ now()->locale('es')->isoFormat('D [días del mes de] MMMM [del año] YYYY') }}</strong>, habiendo leído y comprendido el contenido del presente contrato, las partes lo suscriben en señal de conformidad.</p>
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
                    RFC: {{ strtoupper($expediente->acreditado_rfc ?: '____________') }} &nbsp;
                    CURP: {{ strtoupper($expediente->acreditado_curp ?: '____________') }}
                </div>
            </td>
            <td>
                <div class="linea-firma">
                    <strong>CONSULTORÍA INMOBILIARIA</strong><br>
                    {{ strtoupper($expediente->asesor?->name ?: '________________________________') }}<br>
                    Asesor responsable &bull; {{ $expediente->folio }}
                </div>
            </td>
        </tr>
    </table>
</div>
@endsection
