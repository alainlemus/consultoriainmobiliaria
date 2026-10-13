<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Prestación de Servicios</title>
    {{-- Réplica del PDF que genera la app (app-consultoriainmobiliaria/src/contratos/prestacionServicios.ts) --}}
    <style>
        @page { margin: {{ $margenSup }}pt 18pt {{ $margenInf }}pt 18pt; }
        body, div, p, h2, span, table, td, tr { margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: {{ $fs }}px; line-height: {{ $lh }}; color: #1a1a1a; }
        .header { background: #1a1a1a; padding: 14px 48px 0 48px; }
        .header-empresa { font-size: 18px; font-weight: bold; color: #d4af37; letter-spacing: 1.5px; text-transform: uppercase; }
        .header-slogan { font-size: 10px; color: #a0936a; margin-top: 2px; letter-spacing: .5px; padding-bottom: 12px; }
        .header-divider { height: 4px; background: #d4af37; border-bottom: 0; }
        .doc-titulo-bar { background: #9b2335; padding: 8px 48px; text-align: center; }
        .doc-titulo-bar span { font-size: 12px; font-weight: bold; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
        .folio-area { margin: 10px 0 0 0; }
        .folio-area td { padding: 0 48px 0 0; }
        .folio-box { background: #fdf9ee; border: 1px solid #d4af37; padding: 4px 12px; font-size: 10px; color: #96760f; }
        .contenido { padding: 0 48px; }
        .bloque { margin-bottom: {{ $pb }}px; text-align: justify; }
        .item { margin-left: 12px; text-align: justify; page-break-inside: avoid; }
        .item.ultimo { margin-bottom: {{ $pb }}px; }
        h2 { font-size: 12px; font-weight: bold; color: #9b2335; border-bottom: 2px solid #d4af37; padding-bottom: 3px; margin-top: {{ $h2t }}px; margin-bottom: {{ $h2b }}px; text-transform: uppercase; letter-spacing: 1.5px; page-break-after: avoid; }
        .cierre { margin-top: {{ $cierre }}px; page-break-inside: avoid; }
        .firma-bloque { margin-top: {{ $firmaTop }}px; }
        .firmas { width: 100%; border-collapse: collapse; }
        .firmas td { width: 50%; padding: 0 24px; text-align: center; vertical-align: top; }
        .linea-firma { border-top: 2px solid #1a1a1a; padding-top: 6px; font-size: 12px; line-height: 1.6; }
        .footer { position: fixed; bottom: -{{ $margenInf - 8 }}px; left: 48px; right: 48px; height: 20px; border-top: 1px solid #d4af37; padding-top: 5px; font-size: 10px; }
        .footer-left { color: #96760f; float: left; }
        .footer-right { color: #9b2335; float: right; }
    </style>
</head>
<body>
    
    <div class="header">
        <div class="header-empresa">{{ $siteName }}</div>
        <div class="header-slogan">Gestión de trámites hipotecarios y patrimoniales</div>
    </div>
    <div class="header-divider"></div>
    <div class="doc-titulo-bar"><span>Contrato de Prestación de Servicios Profesionales y Financiamiento de Gastos</span></div>

    <table class="folio-area" style="width:100%;border-collapse:collapse;"><tr><td></td><td style="width:1%;white-space:nowrap;"><div class="folio-box">Expediente: <strong>{{ $folio }}</strong></div></td></tr></table>

    <div class="contenido">
        <div class="bloque" style="margin-top:12px;">{!! nl2br(e($intro)) !!}</div>

        <h2>Declaraciones</h2>
        <div class="bloque"><strong>POR PARTE DE "EL PRESTADOR":</strong></div>
        @foreach($declPrestador as $l)
            <div class="item {{ $loop->last ? 'ultimo' : '' }}">{{ $l }}</div>
        @endforeach
        <div class="bloque"><strong>DECLARA EL INTERESADO:</strong></div>
        @foreach($declInteresado as $l)
            <div class="item {{ $loop->last ? 'ultimo' : '' }}">{{ $l }}</div>
        @endforeach

        <h2>Cláusulas</h2>
        <div class="bloque">AMBAS PARTES SE COMPROMETEN A SOMETERSE AL TENOR DE LAS SIGUIENTES CLÁUSULAS SIN QUE EXISTAN VICIOS DE CONSENTIMIENTO:</div>
        @foreach($clausulas as $l)
            @if($loop->iteration == count($clausulas) - 1)<div style="page-break-inside: avoid;">@endif
            <div class="item {{ $loop->last ? 'ultimo' : '' }}">{{ $l }}</div>
        @endforeach

        <div class="cierre">
            <p style="text-align:justify;">
                EN LA CIUDAD DE <strong>{{ $ciudad }}</strong>, A LOS <strong>{{ $fecha }}</strong>,
                HABIENDO LEÍDO Y COMPRENDIDO EL CONTENIDO DEL PRESENTE CONTRATO, LAS PARTES LO SUSCRIBEN EN SEÑAL DE CONFORMIDAD.
            </p>
            <div class="firma-bloque">
                <table class="firmas">
                    <tr><td style="height:60px;"></td><td style="height:60px;"></td></tr>
                    <tr>
                        <td><div class="linea-firma"><strong>FIRMA DE "EL PRESTADOR"</strong><br>C. {{ $firmaPrestador }}<br><small>{{ $siteName }}</small></div></td>
                        <td><div class="linea-firma"><strong>FIRMA DEL "INTERESADO"</strong><br>C. {{ $acreditado }}<br><small>RFC: {{ $rfc }} &nbsp; CURP: {{ $curp }}</small></div></td>
                    </tr>
                </table>
                <table class="firmas" style="margin-top:30px;">
                    <tr><td style="height:60px;"></td><td style="height:60px;"></td></tr>
                    <tr>
                        <td><div class="linea-firma"><strong>FIRMA POR PARTE DEL JURÍDICO</strong><br>LIC. {{ $firmaJuridico }}</div></td>
                        <td><div class="linea-firma"><strong>FIRMA DEL "OBLIGADO SOLIDARIO"</strong><br>C. {{ $solidario }}</div></td>
                    </tr>
                </table>
            </div>
        </div>
        </div>
    </div>
</body>
</html>
