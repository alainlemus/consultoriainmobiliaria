@extends('layouts.app')

@section('seo_title', 'Crédito FOVISSSTE | Orientación para adquirir vivienda')
@section('seo_description', '¿Eres trabajador del Estado y tienes FOVISSSTE? Recibe orientación sobre tus opciones de crédito y vivienda.')
@section('og_title', 'Crédito FOVISSSTE | Orientación para adquirir vivienda')
@section('og_description', '¿Eres trabajador del Estado y tienes FOVISSSTE? Recibe orientación sobre tus opciones de crédito y vivienda.')
@section('og_image', asset('images/og/fovissste.jpg'))

@php
    $waNumero  = setting('whatsapp_1', '527711910395');
    $waTexto   = 'Hola, soy trabajador del Estado y quiero información sobre mi crédito FOVISSSTE';
    $situaciones = $situaciones ?? [];
    $busquedas   = $busquedas ?? [];
    $enviado     = session('fovissste_enviado', false);
@endphp

@section('content')

    {{-- ══════════════════════════════════════════════════════════════════
         HERO
         ══════════════════════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden bg-dark-900" style="padding-top: 140px;">
        <div class="absolute top-0 left-0 w-64 h-64 -translate-x-1/2 -translate-y-1/2 border rounded-full border-gold-500/10"></div>
        <div class="absolute bottom-0 right-0 translate-x-1/2 translate-y-1/2 border rounded-full w-96 h-96 border-gold-500/10"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-dark-800/60"></div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 lg:pb-28 text-center">
            <div x-reveal>
                <div class="inline-flex items-center gap-2 px-3 py-1 mb-6 text-xs font-semibold tracking-widest text-white uppercase bg-crimson-600 rounded-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-gold-400 inline-block"></span>
                    Orientación para trabajadores del Estado
                </div>

                <h1 class="font-serif text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight text-white mb-6">
                    ¿Eres trabajador del Estado<br>y tienes <span class="text-gold-400">FOVISSSTE?</span>
                </h1>
                <div class="w-20 h-0.5 bg-gold-400 mx-auto mb-6"></div>

                <p class="text-cream-200 text-lg sm:text-xl leading-relaxed max-w-2xl mx-auto mb-10">
                    Conoce tus opciones para adquirir una vivienda y recibe orientación durante tu proceso.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="#formulario" class="btn-gold justify-center">
                        Quiero información
                    </a>
                    <a href="https://wa.me/{{ $waNumero }}?text={{ urlencode($waTexto) }}"
                       target="_blank" rel="noopener noreferrer" class="btn-dark justify-center">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Hablar por WhatsApp
                    </a>
                </div>

                <p class="text-cream-300/50 text-xs mt-8 max-w-xl mx-auto">
                    Consultoría Inmobiliaria no forma parte de FOVISSSTE ni lo representa oficialmente.
                    Somos una consultoría privada que orienta y acompaña a trabajadores del Estado en su trámite.
                </p>
            </div>
        </div>

        {{-- Cifras de confianza — mismo componente countUp que usa el hero del home --}}
        <div class="relative z-20 border-t bg-dark-900/90 backdrop-blur-sm border-gold-500/20">
            <div class="px-4 mx-auto max-w-4xl">
                <div class="grid grid-cols-3 divide-x divide-gold-500/20">
                    <div class="py-5 text-center" x-data="countUp(500, { prefix: '+', duration: 1500 })">
                        <div class="font-serif text-2xl font-bold text-gold-400 sm:text-3xl" x-text="display"></div>
                        <div class="mt-1 text-xs tracking-wider uppercase text-cream-300">Familias asesoradas</div>
                    </div>
                    <div class="py-5 text-center" x-data="countUp(3, { duration: 1000 })">
                        <div class="font-serif text-2xl font-bold text-gold-400 sm:text-3xl" x-text="display"></div>
                        <div class="mt-1 text-xs tracking-wider uppercase text-cream-300">Estados de cobertura</div>
                    </div>
                    <div class="py-5 text-center" x-data="countUp(100, { suffix: '%', duration: 1200 })">
                        <div class="font-serif text-2xl font-bold text-gold-400 sm:text-3xl" x-text="display"></div>
                        <div class="mt-1 text-xs tracking-wider uppercase text-cream-300">Orientación sin costo</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         BENEFICIOS
         ══════════════════════════════════════════════════════════════════ --}}
    <section class="py-20 sm:py-24 bg-cream-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14" x-reveal>
                <p class="section-subtitle mb-3">Qué recibes</p>
                <h2 class="section-title mb-4">Orientación para tu <span class="text-crimson-600">crédito FOVISSSTE</span></h2>
                <div class="gold-divider"></div>
                <p class="text-dark-600 mt-4 max-w-2xl mx-auto">
                    Ya sea que busques casas FOVISSSTE nuevas, usadas o un terreno, te acompañamos en cada paso.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @php
                    $beneficios = [
                        [
                            'icon'  => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
                            't'     => 'Orientación sobre tu crédito',
                            'd'     => 'Te explicamos con claridad tus opciones frente a FOVISSSTE, sin tecnicismos.',
                        ],
                        [
                            'icon'  => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
                            't'     => 'Opciones de vivienda',
                            'd'     => 'Conoces alternativas de vivienda FOVISSSTE acordes a tu municipio y a tu situación.',
                        ],
                        [
                            'icon'  => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z',
                            't'     => 'Acompañamiento durante el proceso',
                            'd'     => 'Un asesor te da seguimiento personal en cada etapa de tu trámite.',
                        ],
                        [
                            'icon'  => 'M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.745 3.745 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z',
                            't'     => 'Seguimiento de documentación',
                            'd'     => 'Te ayudamos a organizar y dar seguimiento a los documentos de tu trámite.',
                        ],
                    ];
                @endphp
                @foreach($beneficios as $b)
                <div class="card-service group" x-reveal.delay.{{ $loop->index * 100 }}>
                    <div class="icon-gold group-hover:bg-gold-500 transition-colors">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $b['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="font-serif text-lg font-bold text-dark-800 mb-2">{{ $b['t'] }}</h3>
                    <div class="w-8 h-0.5 bg-gold-400 mx-auto mb-3"></div>
                    <p class="text-sm text-dark-500 leading-relaxed">{{ $b['d'] }}</p>
                </div>
                @endforeach
            </div>

            <p class="text-center text-dark-400 text-xs max-w-2xl mx-auto mt-10">
                No representamos oficialmente a FOVISSSTE. Te orientamos como consultoría privada especializada
                en trámites de crédito hipotecario.
            </p>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         ¿QUIÉN PUEDE SOLICITAR ORIENTACIÓN?
         ══════════════════════════════════════════════════════════════════ --}}
    <section class="py-20 sm:py-24 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" x-reveal>
                <p class="section-subtitle mb-3">Para ti si...</p>
                <h2 class="section-title mb-4">¿Quién puede solicitar <span class="text-crimson-600">orientación?</span></h2>
                <div class="gold-divider"></div>
                <p class="text-dark-600 mt-4 max-w-2xl mx-auto">
                    Si eres trabajador del Estado y buscas vivienda con FOVISSSTE, esta orientación es para ti.
                </p>
            </div>

            @php
                $elegibilidad = [
                    'Eres trabajador del Estado y quieres conocer tus opciones de crédito',
                    'Ya tienes tu crédito FOVISSSTE y quieres ejercerlo',
                    'Ya tienes un proceso FOVISSSTE iniciado',
                    'Ya tienes una vivienda seleccionada',
                    'No estás seguro de tu situación — también podemos orientarte',
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($elegibilidad as $i => $item)
                <div class="flex items-start gap-3 bg-cream-50 border border-cream-200 rounded-sm p-4"
                     x-reveal.delay.{{ min($i, 4) * 75 }}>
                    <svg class="w-5 h-5 text-gold-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-dark-700 leading-relaxed">{{ $item }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         ¿CÓMO TE AYUDAMOS? / PROCESO
         ══════════════════════════════════════════════════════════════════ --}}
    <section id="proceso" class="py-20 sm:py-24 bg-cream-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" x-reveal>
                <p class="section-subtitle mb-3">Así trabajamos</p>
                <h2 class="section-title mb-4">¿Cómo te <span class="text-crimson-600">ayudamos?</span></h2>
                <div class="gold-divider"></div>
                <p class="text-dark-600 mt-4 max-w-2xl mx-auto">
                    Un proceso simple para conocer tu situación y orientarte sin compromiso.
                </p>
            </div>

            @php
                $pasos = [
                    ['n' => 1, 't' => 'Nos compartes tus datos', 'd' => 'Llenas el formulario con tu información básica.'],
                    ['n' => 2, 't' => 'Un asesor revisa tu situación', 'd' => 'Analizamos tu caso frente a FOVISSSTE.'],
                    ['n' => 3, 't' => 'Conocemos tus necesidades', 'd' => 'Platicamos qué tipo de vivienda buscas.'],
                    ['n' => 4, 't' => 'Te mostramos opciones', 'd' => 'Te compartimos alternativas disponibles para ti.'],
                    ['n' => 5, 't' => 'Te acompañamos', 'd' => 'Damos seguimiento a tu proceso paso a paso.'],
                ];
            @endphp
            <div class="relative" x-reveal>
                {{-- Línea de tiempo que se "dibuja" al entrar en viewport (solo desktop, decorativa) --}}
                <div class="hidden lg:block absolute top-8 left-[10%] right-[10%] h-0.5 bg-gold-400/15 overflow-hidden" aria-hidden="true">
                    <div class="fov-timeline-fill h-full bg-gradient-to-r from-gold-500 via-gold-400 to-crimson-500"></div>
                </div>

                <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    @foreach($pasos as $paso)
                    <div class="flex flex-col items-center text-center group" x-reveal.delay.{{ $loop->index * 100 }}>
                        <div class="w-16 h-16 bg-white border-2 border-gold-400 rounded-full flex items-center justify-center mb-4 shadow-md group-hover:bg-crimson-600 group-hover:border-crimson-600 group-hover:scale-110 transition-all duration-300 z-10">
                            <span class="font-serif font-bold text-gold-500 text-lg group-hover:text-white transition-colors">{{ $paso['n'] }}</span>
                        </div>
                        <div class="bg-white rounded-sm p-4 shadow-sm border border-cream-300 group-hover:border-gold-300 group-hover:-translate-y-1 transition-all duration-300 w-full flex-1">
                            <h4 class="font-serif font-semibold text-dark-800 text-sm mb-1">{{ $paso['t'] }}</h4>
                            <p class="text-dark-500 text-xs leading-relaxed">{{ $paso['d'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-12 text-center">
                <a href="#formulario" class="btn-gold">Quiero información</a>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         FORMULARIO — la sección más importante de la landing
         ══════════════════════════════════════════════════════════════════ --}}
    <section id="formulario" class="py-20 sm:py-24 bg-dark-900 text-cream-200 scroll-mt-20">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-10" x-reveal>
                <p class="section-subtitle text-gold-400 mb-3">Formulario de orientación</p>
                <h2 class="font-serif text-3xl md:text-4xl font-bold text-white mb-4">
                    Solicita orientación sobre tu <span class="text-gold-400">crédito FOVISSSTE</span>
                </h2>
                <div class="gold-divider"></div>
                <p class="text-cream-300 max-w-xl mx-auto mt-4 text-sm">
                    Déjanos tus datos y un asesor se pondrá en contacto contigo para conocer tu situación y orientarte.
                </p>
            </div>

            @if($enviado)
                {{-- Mensaje posterior al envío --}}
                <div class="bg-dark-800 border border-gold-500/30 rounded-sm p-8 text-center" x-reveal>
                    <svg class="w-14 h-14 text-gold-400 mx-auto mb-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="font-serif text-2xl font-bold text-white mb-3">¡Gracias! Recibimos tus datos.</h3>
                    <p class="text-cream-300 text-sm leading-relaxed max-w-md mx-auto mb-8">
                        Un asesor se pondrá en contacto contigo para conocer tu situación y orientarte sobre las opciones disponibles.
                    </p>
                    <a href="https://wa.me/{{ $waNumero }}?text={{ urlencode($waTexto) }}"
                       target="_blank" rel="noopener noreferrer" class="btn-gold">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Hablar por WhatsApp
                    </a>
                </div>

                {{-- Evento de conversión para GTM/Google Ads — solo en el estado de éxito real
                     (el backend ya guardó/actualizó el prospecto antes de llegar aquí, ver
                     FovisssteController@store). sessionStorage evita que un refresh o el
                     botón atrás/adelante sobre esta misma pestaña vuelva a contar el lead;
                     el flash de sesión de Laravel ya evita que esta vista se muestre otra
                     vez en una petición nueva, esto es una capa extra por si el navegador
                     restaura la página desde caché sin pasar por el servidor. --}}
                <script>
                    (function () {
                        var yaEnviado = false;
                        try {
                            yaEnviado = sessionStorage.getItem('fovissste_lead_sent') === '1';
                        } catch (e) {}

                        if (yaEnviado) return;

                        try {
                            sessionStorage.setItem('fovissste_lead_sent', '1');
                        } catch (e) {}

                        window.dataLayer = window.dataLayer || [];
                        window.dataLayer.push({ event: 'fovissste_lead' });
                    })();
                </script>
            @else
                <form action="{{ route('fovissste.store') }}" method="POST"
                      class="space-y-4 bg-dark-800 border border-dark-600 rounded-sm p-6 sm:p-8"
                      x-data="{ enviando: false }"
                      @submit="enviando = true">
                    @csrf

                    {{-- Honeypot: campo trampa invisible para humanos --}}
                    <div class="absolute -left-[9999px] w-px h-px overflow-hidden" aria-hidden="true">
                        <label for="fov_sitio_web">No llenar este campo</label>
                        <input type="text" id="fov_sitio_web" name="sitio_web" tabindex="-1" autocomplete="off">
                    </div>
                    <input type="hidden" name="form_iniciado" value="{{ now()->timestamp }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="fov_nombre" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Nombre completo *</label>
                            <input type="text" id="fov_nombre" name="nombre"
                                   value="{{ old('nombre') }}"
                                   required
                                   placeholder="Tu nombre completo"
                                   class="input-field @error('nombre') border-crimson-500 @enderror">
                            @error('nombre')
                                <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="fov_telefono" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Teléfono / WhatsApp *</label>
                            <input type="tel" id="fov_telefono" name="telefono"
                                   value="{{ old('telefono') }}"
                                   required
                                   placeholder="771 000 0000"
                                   class="input-field @error('telefono') border-crimson-500 @enderror">
                            @error('telefono')
                                <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="fov_email" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">
                            Correo electrónico <span class="text-dark-400 normal-case">(opcional)</span>
                        </label>
                        <input type="email" id="fov_email" name="email"
                               value="{{ old('email') }}"
                               placeholder="correo@ejemplo.com"
                               class="input-field @error('email') border-crimson-500 @enderror">
                        @error('email')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fov_municipio" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Municipio o ciudad donde buscas vivienda *</label>
                        <input type="text" id="fov_municipio" name="municipio"
                               value="{{ old('municipio') }}"
                               required
                               placeholder="Ej. Huejutla de Reyes, Hidalgo"
                               class="input-field @error('municipio') border-crimson-500 @enderror">
                        @error('municipio')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fov_situacion" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Situación con FOVISSSTE *</label>
                        <select id="fov_situacion" name="situacion_fovissste" required
                                class="input-field @error('situacion_fovissste') border-crimson-500 @enderror">
                            <option value="" class="bg-dark-800">— Selecciona una opción —</option>
                            @foreach($situaciones as $valor => $etiqueta)
                            <option value="{{ $valor }}" class="bg-dark-800" {{ old('situacion_fovissste') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('situacion_fovissste')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fov_busqueda" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">¿Qué estás buscando? *</label>
                        <select id="fov_busqueda" name="tipo_busqueda" required
                                class="input-field @error('tipo_busqueda') border-crimson-500 @enderror">
                            <option value="" class="bg-dark-800">— Selecciona una opción —</option>
                            @foreach($busquedas as $valor => $etiqueta)
                            <option value="{{ $valor }}" class="bg-dark-800" {{ old('tipo_busqueda') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('tipo_busqueda')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fov_mensaje" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">Mensaje <span class="text-dark-400 normal-case">(opcional)</span></label>
                        <textarea id="fov_mensaje" name="mensaje" rows="3"
                                  placeholder="Cuéntanos brevemente tu situación o consulta..."
                                  class="input-field resize-none @error('mensaje') border-crimson-500 @enderror">{{ old('mensaje') }}</textarea>
                        @error('mensaje')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fov_curp" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">
                            CURP <span class="text-dark-400 normal-case">(opcional)</span>
                        </label>
                        <input type="text" id="fov_curp" name="curp"
                               value="{{ old('curp') }}"
                               maxlength="18"
                               placeholder="AAAA000000XAAAAA00"
                               style="text-transform:uppercase"
                               oninput="this.value = this.value.toUpperCase()"
                               autocomplete="off" autocapitalize="characters" spellcheck="false"
                               class="input-field font-mono tracking-widest @error('curp') border-crimson-500 @enderror">
                        @error('curp')
                            <p class="text-crimson-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-dark-400 text-xs mt-1">
                            Es opcional para este primer contacto. Si más adelante tu asesor la necesita para tu precalificación, te explicará para qué se usa.
                        </p>
                    </div>

                    <div>
                        <label for="fov_captcha" class="block text-xs text-cream-300 uppercase tracking-wider mb-1">
                            Verificación: ¿Cuánto es
                            <span class="text-gold-400 font-bold">{{ $captcha['a'] ?? '?' }} + {{ $captcha['b'] ?? '?' }}</span>?
                            *
                        </label>
                        <input type="number" id="fov_captcha" name="captcha"
                               required
                               min="0" max="20"
                               placeholder="Escribe el resultado"
                               autocomplete="off"
                               class="input-field @error('captcha') border-crimson-500 @enderror">
                        @error('captcha')
                            <p class="text-crimson-500 text-xs mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex items-start gap-3 pt-2">
                        <input type="checkbox" id="fov_privacidad" name="privacidad" value="1"
                               required
                               {{ old('privacidad') ? 'checked' : '' }}
                               class="mt-1 w-4 h-4 rounded-sm border-dark-600 bg-dark-700 text-gold-500 focus:ring-gold-400 shrink-0">
                        <label for="fov_privacidad" class="text-xs text-cream-300 leading-relaxed">
                            Autorizo el tratamiento de mis datos personales de acuerdo con el
                            <a href="{{ route('aviso.privacidad') }}" target="_blank" rel="noopener" class="text-gold-400 hover:text-gold-300 underline">Aviso de Privacidad</a>. *
                        </label>
                    </div>
                    @error('privacidad')
                        <p class="text-crimson-500 text-xs -mt-2">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            :disabled="enviando"
                            class="btn-gold w-full justify-center disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!enviando">Solicitar orientación</span>
                        <span x-show="enviando" x-cloak>Enviando...</span>
                    </button>

                    <p class="text-xs text-dark-400 text-center">
                        * Campos obligatorios. Tu información es confidencial.
                    </p>
                </form>
            @endif
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         PREGUNTAS FRECUENTES
         ══════════════════════════════════════════════════════════════════ --}}
    <section id="preguntas" class="py-20 sm:py-24 bg-cream-50 scroll-mt-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" x-reveal>
                <p class="section-subtitle mb-3">Dudas comunes</p>
                <h2 class="section-title mb-4">Preguntas <span class="text-crimson-600">frecuentes</span></h2>
                <div class="gold-divider"></div>
            </div>

            @php
                $faqs = [
                    [
                        'p' => '¿Ustedes son FOVISSSTE?',
                        'r' => 'No. Somos una consultoría inmobiliaria privada; te orientamos y te acompañamos en tu trámite, pero FOVISSSTE es la institución que otorga y administra tu crédito.',
                    ],
                    [
                        'p' => '¿La orientación tiene costo?',
                        'r' => 'La primera orientación no tiene costo ni compromiso.',
                    ],
                    [
                        'p' => '¿Necesito ya tener mi crédito FOVISSSTE asignado para pedir orientación?',
                        'r' => 'No es necesario. También orientamos a quienes aún no saben si califican o no saben por dónde empezar.',
                    ],
                    [
                        'p' => '¿Qué documentos necesito para empezar?',
                        'r' => 'Para esta primera orientación solo necesitamos tus datos de contacto. Conforme avance tu proceso, tu asesor te indicará qué documentos se necesitan.',
                    ],
                    [
                        'p' => '¿Puedo capitalizar mi crédito FOVISSSTE y recibir el dinero en efectivo?',
                        'r' => 'Sí, existen opciones para capitalizar tu crédito FOVISSSTE, es decir, convertirlo en efectivo en lugar de usarlo para comprar una vivienda nueva. Te orientamos sobre los requisitos y qué opción aplica a tu caso.',
                    ],
                    [
                        'p' => '¿Necesito tener una vivienda para capitalizar mi crédito?',
                        'r' => 'No necesariamente. Dependiendo de tu situación, hay opciones para capitalizar tu crédito FOVISSSTE sin comprar una vivienda nueva. En tu orientación te decimos cuál opción aplica en tu caso.',
                    ],
                    [
                        'p' => '¿Cuánto dinero puedo recibir al capitalizar mi crédito?',
                        'r' => 'El monto depende de tu saldo y las condiciones específicas de tu crédito FOVISSSTE. En tu primera orientación revisamos tu situación particular para darte un panorama claro.',
                    ],
                    [
                        'p' => '¿Cuánto tiempo tarda el proceso de capitalización?',
                        'r' => 'Los tiempos varían según tu situación y los tiempos de validación de FOVISSSTE. Tu asesor te da un estimado una vez que revisamos tu caso.',
                    ],
                    [
                        'p' => '¿Dan orientación de crédito FOVISSSTE en Hidalgo?',
                        'r' => 'Sí. Atendemos principalmente Hidalgo, Veracruz y San Luis Potosí. Cuéntanos tu municipio en el formulario y te decimos si podemos apoyarte.',
                    ],
                    [
                        'p' => '¿Cómo me contactan después de dejar mis datos?',
                        'r' => 'Un asesor se comunica contigo por teléfono o WhatsApp para conocer tu situación y orientarte sobre las opciones disponibles.',
                    ],
                ];
            @endphp

            <div class="space-y-3">
                @foreach($faqs as $i => $faq)
                <div x-data="{ abierto: false }" x-reveal.delay.{{ min($i, 4) * 75 }}
                     class="bg-white rounded-sm border border-cream-300 overflow-hidden">
                    <button type="button" @click="abierto = !abierto"
                            class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-cream-50 transition-colors">
                        <span class="font-serif font-semibold text-dark-800 text-sm sm:text-base">{{ $faq['p'] }}</span>
                        <svg :class="abierto ? 'rotate-180' : ''" class="w-5 h-5 shrink-0 text-gold-500 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="abierto" x-transition x-cloak class="px-5 pb-4 -mt-1">
                        <p class="text-dark-500 text-sm leading-relaxed">{{ $faq['r'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         WHATSAPP
         ══════════════════════════════════════════════════════════════════ --}}
    <section class="py-20 bg-dark-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center" x-reveal>
            <p class="font-script text-gold-400 text-xl mb-3">¿Prefieres hablar directo?</p>
            <h2 class="font-serif text-2xl sm:text-3xl font-bold text-white mb-6">Escríbenos por WhatsApp</h2>
            <a href="https://wa.me/{{ $waNumero }}?text={{ urlencode($waTexto) }}"
               target="_blank" rel="noopener noreferrer" class="btn-gold">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Hablar por WhatsApp
            </a>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         AVISO DE PRIVACIDAD / INFORMACIÓN LEGAL
         ══════════════════════════════════════════════════════════════════ --}}
    <section class="py-10 bg-cream-100 border-t border-cream-300">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-dark-500 text-xs leading-relaxed">
                Consultoría Inmobiliaria es una empresa privada de asesoría inmobiliaria y no forma parte de,
                ni representa oficialmente a, FOVISSSTE (Fondo de la Vivienda del ISSSTE). El uso de tus datos
                personales se rige por nuestro
                <a href="{{ route('aviso.privacidad') }}" class="text-gold-600 hover:text-gold-700 underline">Aviso de Privacidad</a>.
            </p>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════════
         CTA fijo en móvil — mantiene "Quiero información" siempre a la mano.
         Sustituye al botón flotante de WhatsApp solo en pantallas pequeñas
         (ver estilo abajo) para no encimarse con esta barra.
         ══════════════════════════════════════════════════════════════════ --}}
    <div x-data="{ show: false }"
         x-init="window.addEventListener('scroll', () => { show = window.scrollY > 520 })"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         x-cloak
         class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-dark-900/95 backdrop-blur-sm border-t border-gold-500/30 px-4 py-3 shadow-2xl">
        <a href="#formulario" class="btn-gold w-full justify-center">Quiero información</a>
    </div>

@endsection

@push('styles')
<style>
    /* Línea de tiempo del proceso: se "dibuja" al entrar en viewport (junto con x-reveal) */
    .fov-timeline-fill { width: 0; transition: width 1.1s ease 0.2s; }
    .reveal-visible .fov-timeline-fill { width: 100%; }

    /* En móvil, el CTA fijo de la landing sustituye al botón flotante de WhatsApp
       para que no se encimen en la esquina inferior. En escritorio no aplica. */
    @media (max-width: 1023px) {
        .whatsapp-float { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .fov-timeline-fill { transition: none; width: 100%; }
    }
</style>
@endpush

@push('jsonld')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach($faqs as $faq)
        {
            "@@type": "Question",
            "name": {!! json_encode($faq['p']) !!},
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": {!! json_encode($faq['r']) !!}
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "Inicio",
            "item": "{{ route('home') }}"
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "Crédito FOVISSSTE",
            "item": "{{ route('fovissste.index') }}"
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Service",
    "name": "Orientación para crédito FOVISSSTE",
    "serviceType": "Asesoría de crédito hipotecario FOVISSSTE",
    "description": "Orientación y acompañamiento para trabajadores del Estado que buscan conocer sus opciones de crédito FOVISSSTE y adquirir una vivienda.",
    "provider": {
        "@@type": "RealEstateAgent",
        "name": "{{ setting('site_name', 'Consultoría Inmobiliaria') }}",
        "url": "{{ config('app.url') }}"
    },
    "areaServed": [
        { "@@type": "State", "name": "Hidalgo" },
        { "@@type": "State", "name": "Veracruz" },
        { "@@type": "State", "name": "San Luis Potosí" }
    ],
    "audience": {
        "@@type": "Audience",
        "audienceType": "Trabajadores del Estado afiliados a FOVISSSTE"
    }
}
</script>
@endpush
