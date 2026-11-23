@extends('frontend.login.plantilla')

@section('titulo', 'Enlace vencido')

@section('contenido')
    <div class="icono-paso"><i class="fas fa-clock"></i></div>

    <h2>El enlace expiró</h2>
    <p class="subtitulo">Este enlace para cambiar la contraseña ya no es válido: vence a los 60 minutos o ya fue usado. Solicita uno nuevo y revisa tu correo.</p>

    <a href="{{ url('/admin/ingreso/de/correo') }}" class="boton">Solicitar un nuevo enlace</a>

    <div class="volver">
        <a href="{{ url('/') }}" class="enlace"><i class="fas fa-arrow-left"></i> Volver a iniciar sesión</a>
    </div>
@endsection
