@extends('emails.layouts.base')
@section('title', 'Carpeta del expediente')
@section('alert', 'Carpeta del expediente ' . $expediente->folio)
@section('alert-class', 'blue')
@php $preheader = 'Descarga la carpeta con los documentos del expediente ' . $expediente->folio; @endphp

@section('content')
    <h2>Carpeta lista para descargar</h2>
    <p>
        Aquí está la carpeta con los documentos del expediente <strong>{{ $expediente->folio }}</strong>
        @if($expediente->acreditado_nombre) ({{ $expediente->acreditado_nombre }}) @endif
        para que la descargues en tu computadora y continúes con el proceso.
    </p>

    <div class="cta">
        <a href="{{ $enlace }}">Descargar carpeta (ZIP)</a>
    </div>

    <div class="notice">
        El enlace es personal y estará disponible durante <strong>{{ $horas }} horas</strong>.
        Cada documento va en la carpeta de su sección, y la nota que lo identifica forma parte del nombre del archivo.
    </div>

    <hr class="divider">

    <p class="small">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
    <p style="font-size:13px; word-break:break-all; background:#faf8f3; border:1px solid #ede8db; padding:12px 16px; border-radius:14px; font-family:monospace;">{{ $enlace }}</p>
@endsection
