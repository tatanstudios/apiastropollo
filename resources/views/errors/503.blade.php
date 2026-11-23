@extends('errors.plantilla')

@section('codigo', '503')
@section('titulo', 'Estamos en mantenimiento')
@section('mensaje', 'Estamos realizando mejoras en el sistema. Vuelve a intentarlo en unos minutos.')

@section('icono')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>
@endsection

@section('acciones')
    <a href="javascript:location.reload()" class="boton boton--secundario">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4L21 8"/><path d="M21 3v5h-5"/></svg>
        Recargar
    </a>
    <a href="{{ url('/') }}" target="_top" class="boton boton--principal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/></svg>
        Ir al inicio
    </a>
@endsection
