@extends('layouts.app')

@section('seo_title', 'Consultoría Inmobiliaria - Tu patrimonio, nuestra prioridad')
@section('seo_description', 'Asesores expertos en crédito INFONAVIT y FOVISSSTE, avalúos comerciales y gestión de escrituras en Hidalgo, Veracruz y San Luis Potosí. +500 familias asesoradas.')
@section('og_title', 'Consultoría Inmobiliaria - Tu patrimonio, nuestra prioridad')
@section('og_description', 'Asesores expertos en crédito INFONAVIT y FOVISSSTE, avalúos comerciales y gestión de escrituras en Hidalgo, Veracruz y San Luis Potosí. +500 familias asesoradas.')

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
