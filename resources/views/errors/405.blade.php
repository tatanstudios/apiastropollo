@extends('errors.plantilla')

@section('codigo', '405')
@section('titulo', 'Acción no permitida')
@section('mensaje', 'Esta dirección no acepta la acción que intentaste realizar.')

@section('icono')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/></svg>
@endsection
