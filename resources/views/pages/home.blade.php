@extends('layouts.app')

@section('title', 'Consultoría Inmobiliaria - Tu patrimonio, nuestra prioridad')

@section('content')
    @include('partials.hero')
    @include('partials.servicios')
    @include('partials.porque-elegirnos')
    @include('partials.proceso')
    @include('partials.cobertura')
    @include('partials.propiedades')
    @include('partials.testimonios')
    @include('partials.blog')
    @include('partials.contacto')
@endsection
