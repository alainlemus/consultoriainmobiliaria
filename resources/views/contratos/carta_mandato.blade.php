@extends('contratos._layout')
@section('titulo', 'Carta Mandato')

@section('contenido')
<h2>Datos del Mandante (Acreditado)</h2>
<table class="datos-grid">
    <tr>
        <td class="label">Nombre completo:</td>
        <td class="valor">{{ strtoupper($expediente->acreditado_nombre ?: '________________________') }}</td>
        <td class="label">CURP:</td>
        <td class="valor">{{ strtoupper($expediente->acreditado_curp ?: '________________________') }}</td>
    </tr>
    <tr>
        <td class="label">RFC:</td>
        <td class="valor">{{ strtoupper($expediente->acreditado_rfc ?: '________________________') }}</td>
        <td class="label">Estado civil:</td>
        <td class="valor">{{ ucfirst($expediente->acreditado_estado_civil ?: '________________________') }}</td>
    </tr>
    <tr>
        <td class="label">Teléfono:</td>
        <td class="valor">{{ $expediente->acreditado_telefono ?: '________________________' }}</td>
        <td class="label">Correo electrónico:</td>
        <td class="valor">{{ $expediente->acreditado_email ?: '________________________' }}</td>
    </tr>
    <tr>
        <td class="label">Domicilio:</td>
        <td class="valor" colspan="3">
            {{ $expediente->acreditado_domicilio ?: '' }}
            {{ $expediente->acreditado_colonia ? ', Col. ' . $expediente->acreditado_colonia : '' }}
            {{ $expediente->acreditado_municipio ? ', ' . $expediente->acreditado_municipio : '' }}
            {{ $expediente->acreditado_estado ? ', ' . $expediente->acreditado_estado : '' }}
            {{ $expediente->acreditado_cp ? ' C.P. ' . $expediente->acreditado_cp : '' }}
            @if(!$expediente->acreditado_domicilio) ________________________ @endif
        </td>
    </tr>
</table>

<h2>Datos del Mandatario (Asesor)</h2>
<table class="datos-grid">
    <tr>
        <td class="label">Nombre del asesor:</td>
        <td class="valor">{{ strtoupper($expediente->asesor?->name ?: '________________________') }}</td>
        <td class="label">Empresa:</td>
        <td class="valor">CONSULTORÍA INMOBILIARIA</td>
    </tr>
    <tr>
        <td class="label">Correo electrónico:</td>
        <td class="valor">{{ $expediente->asesor?->email ?: '________________________' }}</td>
        <td class="label">Tipo de trámite:</td>
        <td class="valor">{{ $expediente->tipoTramite?->nombre ?: '________________________' }}</td>
    </tr>
</table>

<h2>Declaraciones y Autorización</h2>

<div class="clausula">
    <p><span class="numero">PRIMERA. OTORGAMIENTO DEL MANDATO.</span> Por medio del presente instrumento, yo
    <strong>{{ strtoupper($expediente->acreditado_nombre ?: '________________') }}</strong>,
    en adelante <em>"EL MANDANTE"</em>, otorgo de manera voluntaria y expresa mi autorización al asesor
    <strong>{{ strtoupper($expediente->asesor?->name ?: '________________') }}</strong>,
    representante de <strong>CONSULTORÍA INMOBILIARIA</strong>, en adelante <em>"EL MANDATARIO"</em>,
    para que en mi nombre y representación realice todas las gestiones necesarias relativas al trámite de
    <strong>{{ $expediente->tipoTramite?->nombre ?: '________________' }}</strong>,
    correspondiente al expediente <strong>{{ $expediente->folio }}</strong>.</p>
</div>

<div class="clausula">
    <p><span class="numero">SEGUNDA. ALCANCE DEL MANDATO.</span> El presente mandato comprende, de manera enunciativa más no limitativa, las siguientes facultades:</p>
    <ul style="margin-left:20px; margin-bottom:10px;">
        <li>Presentar y retirar documentos ante instituciones financieras, notarías y dependencias gubernamentales.</li>
        <li>Firmar acuses de recibo, solicitudes y formatos oficiales relacionados con el trámite.</li>
        <li>Gestionar ante INFONAVIT, FOVISSSTE, SHF u organismo correspondiente los expedientes y solicitudes de crédito.</li>
        <li>Coordinar con el notario público la elaboración y firma de escrituras, en su caso.</li>
        <li>Recibir notificaciones y comunicaciones relacionadas con el trámite.</li>
    </ul>
</div>

<div class="clausula">
    <p><span class="numero">TERCERA. VIGENCIA.</span> El presente mandato tendrá vigencia a partir de la fecha de su firma y hasta la conclusión del trámite descrito, o bien hasta que EL MANDANTE lo revoque expresamente por escrito.</p>
</div>

<div class="clausula">
    <p><span class="numero">CUARTA. RESPONSABILIDAD.</span> EL MANDATARIO se obliga a actuar con diligencia y en estricto apego a las instrucciones del MANDANTE, informando periódicamente del avance del trámite y absteniéndose de realizar actos que excedan los límites del presente instrumento.</p>
</div>

<div class="clausula">
    <p><span class="numero">QUINTA. DECLARACIÓN DE CONFORMIDAD.</span> Habiendo leído y comprendido el contenido de la presente Carta Mandato, las partes manifiestan su conformidad y la firman en la Ciudad de México, a los
    <strong>{{ now()->locale('es')->isoFormat('D [días del mes de] MMMM [del año] YYYY') }}</strong>.</p>
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
                    <strong>EL MANDANTE</strong><br>
                    {{ strtoupper($expediente->acreditado_nombre ?: '________________________________') }}<br>
                    CURP: {{ strtoupper($expediente->acreditado_curp ?: '________________________') }}
                </div>
            </td>
            <td>
                <div class="linea-firma">
                    <strong>EL MANDATARIO</strong><br>
                    {{ strtoupper($expediente->asesor?->name ?: '________________________________') }}<br>
                    CONSULTORÍA INMOBILIARIA
                </div>
            </td>
        </tr>
    </table>
</div>
@endsection
