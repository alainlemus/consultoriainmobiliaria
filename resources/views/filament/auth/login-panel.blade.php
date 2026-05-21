@php
    $loginImage = setting('login_image');
@endphp
<div class="absolute inset-0 h-full w-full bg-cover bg-center"
     style="background-image: url('{{ $loginImage ? asset('storage/' . $loginImage) : asset('storage/propiedades/01KRT2GJHH8ZH3HMWG4866BAGE.jpeg') }}'); opacity: 60%; background-position: center;">
</div>
