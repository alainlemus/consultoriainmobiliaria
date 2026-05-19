@component('mail::message')

{!! nl2br(e($cuerpoHtml)) !!}

@component('mail::button', ['url' => config('app.url'), 'color' => 'green'])
Visitar sitio web
@endcomponent

_Este mensaje fue enviado por {{ config('app.name') }}._

@endcomponent
