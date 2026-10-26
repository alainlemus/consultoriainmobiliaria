<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagaré</title>
    <style>
        @page { margin: 40pt 50pt; }
        /* Sin '*': en dompdf el selector universal también anula los márgenes de @page. */
        body, h1, p, table, td { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9pt; line-height: 1.5; color: #000; }
        h1 { font-size: 20pt; font-weight: bold; text-align: center; margin-bottom: 22pt; letter-spacing: 1px; }
        p { margin-bottom: 11pt; text-transform: uppercase; }
        .j { text-align: justify; }
        .lugar { margin-left: 72pt; }
        .linea { border-bottom: 1px solid #000; display: inline-block; min-width: 60pt; }
        table.firma { width: 100%; border-collapse: collapse; margin-top: 18pt; }
        table.firma td { border: 1px solid #000; padding: 10pt 6pt; text-transform: uppercase; }
        table.firma td.fila-lugar { height: 40pt; vertical-align: top; }
        table.firma td.fila-firma { height: 70pt; vertical-align: top; }
    </style>
</head>
<body>
@php
    $en = fn (?string $v, int $n) => $v !== '' && $v !== null ? e($v) : '<span class="linea" style="min-width:' . $n . 'pt">&nbsp;</span>';
@endphp

<h1>PAGARÉ</h1>

<p class="lugar">Ciudad de Pachuca de Soto, Hidalgo, a {{ $d['dia'] }} de {{ $d['mes'] }} de {{ $d['anio'] }}.</p>

<p class="j">
    Yo, C. {!! $en($d['nombre'], 220) !!}, con domicilio en {!! $en($d['domicilio'], 240) !!},
    y RFC: {!! $en($d['rfc'], 110) !!}, en adelante "El Deudor", reconozco deber y pagar incondicionalmente
    a la orden de <strong>C. Jose Antonio Solis Santuario</strong>, con domicilio en Avenida Revolución 1014
    Despacho 55 Plaza Las Marialuisas Colonia Periodistas Código Postal 42060 Pachuca de Soto Hidalgo,
    y RFC: <strong>SOSA860530LM7</strong>, en adelante "El Acreedor", la cantidad de:
</p>

<p>1. Importe: la cantidad de ${!! $en(null, 80) !!} ({!! $en(null, 210) !!} pesos 00/100 M.N.).</p>
<p>2. Vencimiento: el presente pagaré vencerá el día {!! $en(null, 30) !!} de {!! $en(null, 90) !!} de 20{!! $en(null, 24) !!}, fecha en la cual deberá efectuarse el pago total del importe.</p>
<p class="j">3. Intereses: se causarán intereses moratorios a razón del 3% anual sobre saldos insolutos a partir del día siguiente al vencimiento.</p>
<p class="j">4. Lugar de pago: el pago se realizará en <strong>Pachuca de Soto, Hidalgo</strong> o en el domicilio que el acreedor designe por escrito.</p>
<p class="j">5. Gastos: cualquier gasto, desembolso o comisión que se origine por la cobranza o por incumplimiento será responsabilidad de El Deudor.</p>
<p>6. Jurisdicción: para la interpretación y cumplimiento de este documento, ambas partes se someten a las leyes y tribunales competentes de la ciudad de Pachuca de Soto, Hidalgo.</p>

<table class="firma">
    <tr><td class="fila-lugar">Lugar y fecha:</td></tr>
    <tr><td class="fila-firma">Nombre y firma de El Deudor:</td></tr>
</table>

</body>
</html>
