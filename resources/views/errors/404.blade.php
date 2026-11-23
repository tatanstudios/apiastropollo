@extends('errors.plantilla')

@section('codigo', '404')
@section('titulo', 'Página no encontrada')
@section('mensaje', 'La página que buscas no existe o fue movida. Revisa la dirección o vuelve al inicio.')

@section('icono')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8.5 11h5"/></svg>
@endsection
