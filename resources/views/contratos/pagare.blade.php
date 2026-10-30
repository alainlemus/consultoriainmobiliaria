<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagaré</title>
    <style>
        @page { margin: 36pt 54pt; }
        /* Sin '*': en dompdf el selector universal también anula los márgenes de @page. */
        body, h1, h2, p, table, td { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9pt; line-height: 1.45; color: #000; }
        h1 { font-size: 18pt; font-weight: bold; text-align: center; margin-bottom: 8pt; letter-spacing: 1px; }
        h2 { font-size: 9pt; font-weight: bold; margin: 12pt 0 5pt; }
        p { margin-bottom: 5pt; }
        .j { text-align: justify; }
        .bueno { text-align: right; font-weight: bold; margin-bottom: 10pt; }
        .firma { margin-top: 18pt; }
        table.testigos { width: 100%; border-collapse: collapse; }
        table.testigos td { width: 50%; vertical-align: top; }
        .linea { border-bottom: 1px solid #000; display: inline-block; min-width: 60pt; }
    </style>
</head>
<body>
@php
    $en = fn (?string $v, int $n) => $v !== '' && $v !== null ? e($v) : '<span class="linea" style="min-width:' . $n . 'pt">&nbsp;</span>';
@endphp

<h1>PAGARÉ</h1>

<p class="bueno">BUENO POR: ${!! $en(null, 110) !!}</p>

<p>Lugar y fecha de suscripción: {!! $en($d['lugar'], 140) !!}, a {{ $d['fecha'] }}.</p>
<p>Fecha de vencimiento: {!! $en(null, 220) !!}</p>

<p class="j" style="margin-top: 10pt">
    Por este pagaré, yo, {!! $en($d['nombre'], 220) !!}, prometo pagar incondicionalmente a la orden de
    <strong>{{ $d['acreedor'] }}</strong>, la cantidad de:
</p>
<p>${!! $en(null, 110) !!} ({!! $en(null, 230) !!} PESOS 00/100 M.N.)</p>
<p class="j">Cantidad que pagaré en la fecha de vencimiento señalada, en el domicilio ubicado en: <strong>{{ $d['domicilio_pago'] }}</strong>.</p>
<p class="j">En caso de incumplimiento en el pago oportuno, me obligo a cubrir intereses moratorios a razón del {!! $en(null, 36) !!}% mensual, hasta la total liquidación del adeudo.</p>

<h2>DATOS DEL SUSCRIPTOR (DEUDOR)</h2>
<p>Nombre completo: {!! $en($d['nombre'], 300) !!}</p>
<p>Domicilio: {!! $en($d['domicilio'], 340) !!}</p>
<p>Identificación oficial: {!! $en($d['identificacion'], 280) !!}</p>
<p class="firma">Firma: {!! $en(null, 300) !!}</p>

<h2>DATOS DEL AVAL</h2>
<p class="j">
    Por medio del presente, yo, {!! $en($d['aval_nombre'], 220) !!}, declaro que avalo al suscriptor de este pagaré
    y garantizo el pago total de la obligación consignada en el mismo, obligándome solidariamente en los términos de
    la Ley General de Títulos y Operaciones de Crédito.
</p>
<p>Domicilio: {!! $en($d['aval_domicilio'], 340) !!}</p>
<p>Identificación oficial: {!! $en(null, 280) !!}</p>
<p class="firma">NOMBRE Y FIRMA DEL AVAL: {!! $en(null, 250) !!}</p>

<h2>TESTIGOS</h2>
<table class="testigos"><tr>
    @foreach ([1, 2] as $n)
        <td>
            <p><strong>TESTIGO {{ $n }}</strong></p>
            <p>Nombre: {!! $en(null, 175) !!}</p>
            <p class="firma">Firma: {!! $en(null, 180) !!}</p>
        </td>
    @endforeach
</tr></table>

</body>
</html>
