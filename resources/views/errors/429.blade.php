@extends('errors.plantilla')

@section('codigo', '429')
@section('titulo', 'Demasiadas solicitudes')
@section('mensaje', 'Hiciste muchas solicitudes en poco tiempo. Espera un momento antes de intentarlo otra vez.')

@section('icono')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 14l4-4"/><path d="M3.3 17a9 9 0 1 1 17.4 0"/></svg>
@endsection
