@extends('errors.plantilla')

@section('codigo', '403')
@section('titulo', 'Acceso denegado')
@section('mensaje', 'No tienes permisos para ver esta página. Si crees que es un error, contacta al administrador.')

@section('icono')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9.5 9.5 5 5m0-5-5 5"/></svg>
@endsection
