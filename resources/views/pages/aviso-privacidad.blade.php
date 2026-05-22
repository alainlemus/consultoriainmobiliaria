@extends('layouts.app')

@section('title', 'Aviso de Privacidad — ' . setting('site_name', 'Consultoría Inmobiliaria'))

@section('content')
    <section class="py-20 bg-cream-50 min-h-screen" style="padding-top: 140px;">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">
                <p class="section-subtitle text-gold-400 mb-3">Legal</p>
                <h1 class="section-title mb-4">Aviso de <span class="text-gold-400">Privacidad</span></h1>
                <div class="gold-divider"></div>
            </div>

            <div class="bg-white rounded-sm shadow-sm border border-cream-200 p-8 sm:p-12">
                @if (setting('aviso_privacidad'))
                    <div class="richtext">
                        {!! setting('aviso_privacidad') !!}
                    </div>
                @else
                    <div class="text-center py-16 text-dark-400">
                        <svg class="w-12 h-12 mx-auto mb-4 text-cream-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">El aviso de privacidad aún no ha sido publicado.</p>
                    </div>
                @endif
            </div>

            <div class="text-center mt-8">
                <a href="{{ route('home') }}" class="text-gold-500 hover:text-gold-600 text-sm transition-colors">
                    ← Regresar al inicio
                </a>
            </div>

        </div>
    </section>
@endsection
