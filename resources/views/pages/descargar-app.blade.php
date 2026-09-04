@extends('layouts.app')

@section('seo_title', 'Descarga la App — ' . setting('site_name', 'Consultoría Inmobiliaria'))
@section('seo_description', 'Descarga la app de Consultoría Inmobiliaria: asesores dan seguimiento a expedientes y rutas, clientes consultan su trámite y suben documentos desde el celular.')

@section('content')

    @php
        $urlAppStore    = setting('app_store_url', 'https://google.com.mx');
        $urlPlayStore   = setting('play_store_url', 'https://google.com.mx');
        $appStoreListo  = $urlAppStore  && $urlAppStore  !== 'https://google.com.mx';
        $playStoreListo = $urlPlayStore && $urlPlayStore !== 'https://google.com.mx';

        // Capturas: si el admin subió una en "App móvil", se usa esa; si no, la
        // que viene incluida por defecto en public/images/app/.
        $shotIos     = setting('screenshot_login_ios')
            ? \Illuminate\Support\Facades\Storage::url(setting('screenshot_login_ios'))
            : asset('images/app/screenshot-login-ios.png');
        $shotAndroid = setting('screenshot_cliente_android')
            ? \Illuminate\Support\Facades\Storage::url(setting('screenshot_cliente_android'))
            : asset('images/app/screenshot-cliente-android.png');
    @endphp

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden bg-dark-900" style="padding-top: 140px;">
        <div class="absolute top-0 left-0 w-64 h-64 -translate-x-1/2 -translate-y-1/2 border rounded-full border-gold-500/10"></div>
        <div class="absolute bottom-0 right-0 translate-x-1/2 translate-y-1/2 border rounded-full w-96 h-96 border-gold-500/10"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24 lg:pb-32">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <div x-reveal>
                    <p class="section-subtitle text-gold-400 mb-3">Nuestra app móvil</p>
                    <h1 class="font-serif text-4xl sm:text-5xl font-bold text-white leading-tight mb-6">
                        Tu trámite,<br><span class="text-gold-400">en tu bolsillo</span>
                    </h1>
                    <div class="w-20 h-0.5 bg-gold-400 mb-6"></div>
                    <p class="text-cream-200 text-lg leading-relaxed max-w-lg mb-10">
                        La misma consultoría de siempre, ahora desde tu celular. Asesores dan seguimiento a
                        expedientes y rutas en campo; clientes consultan su trámite y suben documentos sin
                        tener que llamar o ir a la oficina.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        {{-- App Store --}}
                        @if($appStoreListo)
                            <a href="{{ $urlAppStore }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-3 bg-white hover:bg-cream-100 text-dark-900 px-5 py-3 rounded-sm shadow-md hover:shadow-xl hover:-translate-y-1 active:translate-y-0 active:scale-95 transition-all duration-300">
                                <svg class="w-7 h-7 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.08l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                                </svg>
                                <span class="text-left leading-tight">
                                    <span class="block text-[10px] uppercase tracking-wider text-dark-500">Descarga en</span>
                                    <span class="block text-base font-semibold -mt-0.5">App Store</span>
                                </span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-3 bg-dark-800 text-cream-300/50 px-5 py-3 rounded-sm border border-dark-600 cursor-not-allowed">
                                <svg class="w-7 h-7 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.53 4.08l-.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                                </svg>
                                <span class="text-left leading-tight">
                                    <span class="block text-[10px] uppercase tracking-wider text-cream-300/40">Muy pronto en</span>
                                    <span class="block text-base font-semibold -mt-0.5">App Store</span>
                                </span>
                            </span>
                        @endif

                        {{-- Google Play --}}
                        @if($playStoreListo)
                            <a href="{{ $urlPlayStore }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-3 bg-white hover:bg-cream-100 text-dark-900 px-5 py-3 rounded-sm shadow-md hover:shadow-xl hover:-translate-y-1 active:translate-y-0 active:scale-95 transition-all duration-300">
                                <svg class="w-7 h-7 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3.6 2.3c-.4.2-.6.6-.6 1.1v17.2c0 .5.2.9.6 1.1l9.4-9.7-9.4-9.7zm11.1 9.7l2.5-2.6-9.9-5.7 7.4 8.3zm0 0l-7.4 8.3 9.9-5.7-2.5-2.6zm1-.3l3-1.7c.5-.3.8-.7.8-1.3s-.3-1-.8-1.3l-3-1.7-2.7 3 2.7 3z"/>
                                </svg>
                                <span class="text-left leading-tight">
                                    <span class="block text-[10px] uppercase tracking-wider text-dark-500">Disponible en</span>
                                    <span class="block text-base font-semibold -mt-0.5">Google Play</span>
                                </span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-3 bg-dark-800 text-cream-300/50 px-5 py-3 rounded-sm border border-dark-600 cursor-not-allowed">
                                <svg class="w-7 h-7 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3.6 2.3c-.4.2-.6.6-.6 1.1v17.2c0 .5.2.9.6 1.1l9.4-9.7-9.4-9.7zm11.1 9.7l2.5-2.6-9.9-5.7 7.4 8.3zm0 0l-7.4 8.3 9.9-5.7-2.5-2.6zm1-.3l3-1.7c.5-.3.8-.7.8-1.3s-.3-1-.8-1.3l-3-1.7-2.7 3 2.7 3z"/>
                                </svg>
                                <span class="text-left leading-tight">
                                    <span class="block text-[10px] uppercase tracking-wider text-cream-300/40">Muy pronto en</span>
                                    <span class="block text-base font-semibold -mt-0.5">Google Play</span>
                                </span>
                            </span>
                        @endif
                    </div>

                    @if(!$appStoreListo || !$playStoreListo)
                        <p class="text-cream-300/60 text-xs mt-5 mb-2">
                            La app está en fase de pruebas. Si ya formas parte del equipo, tu asesor te comparte el acceso directo.
                        </p>
                    @endif
                </div>

                {{-- Mockup compacto solo para mobile/tablet: en desktop se muestra el par de abajo --}}
                <div class="flex lg:hidden justify-center relative h-[360px]" x-reveal.delay.150>
                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-72 h-72 bg-gold-500/20 rounded-full blur-3xl phone-glow"></div>
                    <div class="relative z-10 phone-float-1">
                        <div class="group relative w-44 h-[350px] bg-dark-800 rounded-[2.25rem] border-4 border-dark-700 shadow-2xl p-2.5 transition-transform duration-500">
                            <div class="w-full h-full rounded-[1.75rem] overflow-hidden border border-gold-500/20 relative">
                                <div class="absolute top-1.5 left-1/2 -translate-x-1/2 w-16 h-5 bg-dark-900 rounded-full z-10"></div>
                                <img src="{{ $shotIos }}"
                                     alt="Pantalla de acceso de la app en iPhone — Consultoría Inmobiliaria"
                                     class="w-full h-full object-cover object-top">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mockups: iPhone (login) + Android (portal del cliente) — capturas reales de la app. --}}
                <div class="hidden lg:flex items-center justify-center relative h-[480px]" x-reveal.delay.150>
                    {{-- Brillo dorado detrás de los teléfonos --}}
                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gold-500/20 rounded-full blur-3xl phone-glow"></div>

                    <div class="relative z-10 flex items-center">
                        {{-- Android — atrás, a la izquierda: portal del cliente --}}
                        <div class="relative z-0 mr-[-3.5rem] mt-16 phone-float-2">
                            <div class="group relative w-48 h-[380px] bg-dark-800 rounded-[1.75rem] border-4 border-dark-700 shadow-2xl p-2.5 transition-transform duration-500 hover:scale-105 hover:-rotate-2 cursor-default">
                                <div class="w-full h-full rounded-[1.35rem] overflow-hidden border border-gold-500/20 relative">
                                    <div class="absolute top-1.5 left-1/2 -translate-x-1/2 w-2.5 h-2.5 rounded-full bg-dark-900 border border-gold-500/30 z-10"></div>
                                    <img src="{{ $shotAndroid }}"
                                         alt="Portal del cliente en Android — Consultoría Inmobiliaria"
                                         class="w-full h-full object-cover object-top">
                                </div>
                            </div>
                        </div>

                        {{-- iPhone — al frente, a la derecha: pantalla de acceso --}}
                        <div class="relative z-10 phone-float-1">
                            <div class="group relative w-56 h-[440px] bg-dark-800 rounded-[2.75rem] border-4 border-dark-700 shadow-2xl p-3 transition-transform duration-500 hover:scale-105 hover:rotate-2 cursor-default">
                                <div class="w-full h-full rounded-[2.15rem] overflow-hidden border border-gold-500/20 relative">
                                    <div class="absolute top-2 left-1/2 -translate-x-1/2 w-24 h-6 bg-dark-900 rounded-full z-10"></div>
                                    <img src="{{ $shotIos }}"
                                         alt="Pantalla de acceso de la app en iPhone — Consultoría Inmobiliaria"
                                         class="w-full h-full object-cover object-top">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
    <style>
        @keyframes phone-float-1 { 0%,100% { transform: translateY(0) rotate(1deg); } 50% { transform: translateY(-16px) rotate(1deg); } }
        @keyframes phone-float-2 { 0%,100% { transform: translateY(0) rotate(-2deg); } 50% { transform: translateY(-10px) rotate(-2deg); } }
        @keyframes phone-glow-pulse { 0%,100% { opacity: .5; transform: translate(-50%,-50%) scale(1); } 50% { opacity: .8; transform: translate(-50%,-50%) scale(1.1); } }
        .phone-float-1 { animation: phone-float-1 5s ease-in-out infinite; }
        .phone-float-2 { animation: phone-float-2 6s ease-in-out infinite; animation-delay: .3s; }
        .phone-glow { animation: phone-glow-pulse 4s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) {
            .phone-float-1, .phone-float-2, .phone-glow { animation: none; }
        }
    </style>
    @endpush

    {{-- ── Qué puedes hacer ─────────────────────────────────────────────── --}}
    <section class="py-24 bg-cream-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" x-reveal>
                <p class="section-subtitle mb-3">Una app, dos perfiles</p>
                <h2 class="section-title mb-4">Qué puedes <span class="text-crimson-600">hacer</span></h2>
                <div class="gold-divider"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Asesores --}}
                <div class="card-service group text-left" x-reveal>
                    <div class="icon-gold group-hover:bg-gold-500">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-dark-800 mb-2 text-center">Para asesores</h3>
                    <div class="w-8 h-0.5 bg-gold-400 mx-auto mb-4"></div>
                    <ul class="text-sm text-dark-600 space-y-2">
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Gestiona expedientes y prospectos desde el celular</li>
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Escanea INE con OCR y genera contratos en campo</li>
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Registra tu ruta de visitas y ubicación de trabajo</li>
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Funciona sin conexión y sincroniza al recuperar señal</li>
                    </ul>
                </div>

                {{-- Clientes --}}
                <div class="card-service group text-left" x-reveal.delay.100>
                    <div class="icon-gold group-hover:bg-gold-500">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-dark-800 mb-2 text-center">Para clientes</h3>
                    <div class="w-8 h-0.5 bg-gold-400 mx-auto mb-4"></div>
                    <ul class="text-sm text-dark-600 space-y-2">
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Consulta el avance de tu trámite en tiempo real</li>
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Sube tus documentos sin ir a la oficina</li>
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Recibe notificaciones de cada paso del proceso</li>
                        <li class="flex items-start gap-2"><span class="text-gold-500 mt-0.5">✦</span> Contacta a tu asesor directo desde la app</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Cómo instalarla ──────────────────────────────────────────────── --}}
    <section class="py-24 bg-cream-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" x-reveal>
                <p class="section-subtitle mb-3">En 3 pasos</p>
                <h2 class="section-title mb-4">Cómo <span class="text-crimson-600">instalarla</span></h2>
                <div class="gold-divider"></div>
            </div>

            @php
                $pasos = [
                    ['n' => 1, 't' => 'Descarga', 'd' => 'Toca el botón de tu tienda (App Store o Google Play) y espera a que se instale.'],
                    ['n' => 2, 't' => 'Abre la app', 'd' => 'Búscala en tu pantalla de inicio como "Consultoría Inmobiliaria" y ábrela.'],
                    ['n' => 3, 't' => 'Inicia sesión', 'd' => 'Usa el usuario y contraseña que te compartió tu asesor. Si eres cliente, con el correo de tu expediente.'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-4xl mx-auto">
                @foreach($pasos as $paso)
                <div class="flex flex-col items-center text-center group" x-reveal.delay.{{ $loop->index * 100 }}>
                    <div class="w-16 h-16 bg-white border-2 border-gold-400 rounded-full flex items-center justify-center mb-4 shadow-md group-hover:bg-crimson-600 group-hover:border-crimson-600 transition-all duration-300 z-10">
                        <span class="font-serif font-bold text-gold-500 text-lg group-hover:text-white transition-colors">{{ $paso['n'] }}</span>
                    </div>
                    <div class="bg-white rounded-sm p-4 shadow-sm border border-cream-300 group-hover:border-gold-300 transition-colors w-full flex-1">
                        <h4 class="font-serif font-semibold text-dark-800 text-sm mb-1">{{ $paso['t'] }}</h4>
                        <p class="text-dark-500 text-xs leading-relaxed">{{ $paso['d'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Preguntas frecuentes (clientes) ─────────────────────────────────── --}}
    <section class="py-24 bg-cream-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" x-reveal>
                <p class="section-subtitle mb-3">Para clientes</p>
                <h2 class="section-title mb-4">Preguntas <span class="text-crimson-600">frecuentes</span></h2>
                <div class="gold-divider"></div>
            </div>

            @php
                $faqs = [
                    [
                        'p' => '¿Necesito tener un trámite abierto para usar la app?',
                        'r' => 'No. Si aún no tienes un trámite, puedes solicitar una asesoría directo desde la app eligiendo el tipo de crédito (INFONAVIT, FOVISSSTE o conyugal) y un asesor te contactará para iniciar tu proceso.',
                    ],
                    [
                        'p' => '¿Cómo ingreso a la app si soy cliente?',
                        'r' => 'Con el correo con el que tu asesor registró tu expediente. En tu primer ingreso puedes crear tu contraseña o recuperarla desde la pantalla de acceso si ya la olvidaste.',
                    ],
                    [
                        'p' => '¿Qué puedo consultar de mi trámite?',
                        'r' => 'El folio de tu expediente, la etapa en la que va (desde apertura hasta firma y escrituración), fechas estimadas y un mensaje que te explica en qué consiste cada etapa, además de los datos de contacto de tu asesor asignado.',
                    ],
                    [
                        'p' => '¿Cómo subo mis documentos?',
                        'r' => 'Desde la sección de tu expediente puedes tomar foto o adjuntar un PDF de cada documento solicitado (identificación, comprobantes, actas, etc.). Aceptamos JPG, PNG, HEIC de iPhone y PDF de hasta 20 MB.',
                    ],
                    [
                        'p' => '¿Me avisan si un documento no es válido?',
                        'r' => 'Sí. Si un documento se rechaza, recibes una notificación con el motivo para que puedas volver a subirlo correctamente sin tener que llamar a la oficina.',
                    ],
                    [
                        'p' => '¿Recibo avisos del avance de mi trámite?',
                        'r' => 'Sí, la app te notifica cada vez que tu expediente cambia de etapa, cuando un documento es aprobado o rechazado, y cuando tu trámite se cierra.',
                    ],
                    [
                        'p' => '¿Puedo contactar directamente a mi asesor?',
                        'r' => 'Sí. Tu expediente siempre muestra el nombre, teléfono y correo del asesor que te está atendiendo, por si prefieres resolver algo por llamada o WhatsApp.',
                    ],
                    [
                        'p' => '¿La app tiene costo?',
                        'r' => 'No, descargarla y usarla es completamente gratis para nuestros clientes. Actualmente está en fase de pruebas; si ya formas parte del equipo, tu asesor te comparte el acceso directo.',
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

    {{-- ── CTA final ─────────────────────────────────────────────────────── --}}
    <section class="py-20 bg-dark-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center" x-reveal>
            <p class="font-script text-gold-400 text-xl mb-3">¿Tienes dudas para instalarla?</p>
            <h2 class="font-serif text-2xl sm:text-3xl font-bold text-white mb-6">Un asesor te ayuda sin costo</h2>
            <a href="https://wa.me/{{ setting('whatsapp_1', '527711910395') }}?text=Hola,%20tengo%20dudas%20para%20instalar%20la%20app"
               target="_blank" rel="noopener noreferrer" class="btn-gold">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                Hablar con un asesor
            </a>
        </div>
    </section>

@endsection

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
@endpush
