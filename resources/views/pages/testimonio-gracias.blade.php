@extends('layouts.app')

@section('seo_title', '¡Gracias por tu testimonio! — ' . setting('seo_titulo', 'Consultoría Inmobiliaria'))
@section('robots', 'noindex, nofollow')

@section('content')
<section class="min-h-screen bg-dark-800 flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md text-center">

        <div class="w-20 h-20 bg-gold-500/20 border border-gold-500/40 rounded-full flex items-center justify-center mx-auto mb-8">
            <svg class="w-10 h-10 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <p class="section-subtitle text-gold-400 mb-2">¡Recibido!</p>
        <h1 class="font-serif text-3xl font-bold text-white mb-4">
            Gracias por compartir<br><span class="text-gold-400">tu experiencia</span>
        </h1>
        <div class="gold-divider"></div>

        <p class="text-cream-400 text-sm mt-6 leading-relaxed">
            Tu testimonio ha sido recibido y será publicado en nuestro sitio<br>
            una vez que lo revisemos. Esto suele tardar menos de 24 horas.
        </p>

        <a href="{{ url('/') }}"
           class="inline-block mt-8 bg-gold-500 hover:bg-gold-400 text-dark-900 font-semibold px-8 py-3 rounded-sm transition-colors duration-200 text-sm tracking-wide">
            Volver al inicio
        </a>
    </div>
</section>
@endsection
