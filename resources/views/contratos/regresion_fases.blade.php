<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Regresión de fases</title>
    <style>
        @page { margin: 54pt 72pt; }
        /* Sin '*': en dompdf el selector universal también anula los márgenes de @page. */
        body, p { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #000; }
        p { margin-bottom: 0; }
        .fecha { text-align: right; margin-bottom: 40pt; }
        .destino { font-weight: bold; }
        .attn { margin-top: 28pt; font-weight: bold; }
        .cuerpo { margin-top: 28pt; text-align: justify; line-height: 1.8; }
        .cierre { margin-top: 28pt; }
        .atte { margin-top: 28pt; font-weight: bold; }
        .firma { margin-top: 48pt; }
        .linea { border-bottom: 1px solid #000; display: inline-block; min-width: 60pt; }
    </style>
</head>
<body>
@php
    $en = fn (?string $v, int $n) => $v !== '' && $v !== null ? e($v) : '<span class="linea" style="min-width:' . $n . 'pt">&nbsp;</span>';
@endphp

<p class="fecha">{{ $d['lugar'] }}; a {{ $d['fecha'] }}.</p>

<p class="destino">FONDO DE LA VIVIENDA DEL INSTITUTO DE SEGURIDAD<br>
Y SERVICIOS SOCIALES DE LOS TRABAJADORES DEL ESTADO “FOVISSSTE”<br>
P R E S E N T E</p>

<p class="attn">AT’N: “HIPOTECARIA HOGAR”, S. A. DE C. V.<br>
<span style="font-weight: normal">Sociedad Financiera de Objeto Múltiple, Entidad no Regulada</span></p>

<p class="cuerpo">
    Los que suscribimos <strong>{!! $en($d['acreditado'], 160) !!}</strong> y <strong>{!! $en($d['solidario'], 160) !!}</strong>
    por medio del presente hacemos de su conocimiento que por así convenir a nuestros intereses solicitamos de la
    manera más atenta que se decline el crédito identificado con el número {!! $en(null, 130) !!}, reconocemos que el
    recurso deberá reintegrarse en su totalidad al FOVISSSTE a la cuenta que se nos indique.
</p>

<p class="cierre">Quedo a sus órdenes para cualquier duda o aclaración.</p>

<p class="atte">A T E N T A M E N T E:</p>

<p class="firma">C. {!! $en($d['acreditado'], 200) !!}</p>
<p class="firma">C. {!! $en($d['solidario'], 200) !!}</p>

</body>
</html>
