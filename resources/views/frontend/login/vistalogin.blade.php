@extends('frontend.login.plantilla')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <img src="{{ asset('images/logo.png') }}" alt="Astro Pollo" class="logo">

    <h2>Bienvenido</h2>
    <p class="subtitulo">Inicia sesión para entrar al panel de administración.</p>

    <form data-accion="login" novalidate>
        <div class="campo">
            <label for="usuario">Usuario</label>
            <div class="campo__control">
                <span class="campo__icono"><i class="fas fa-user"></i></span>
                <input id="usuario" type="text" maxlength="25" placeholder="Tu usuario" autocomplete="username" autofocus>
            </div>
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <div class="campo__control">
                <span class="campo__icono"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" maxlength="16" placeholder="Tu contraseña" autocomplete="current-password">
                <button type="button" class="ver-clave" data-para="password" aria-label="Mostrar contraseña"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <div class="enlace-fila">
            <a href="{{ url('/admin/ingreso/de/correo') }}" class="enlace">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="boton" id="btnLogin">Iniciar sesión</button>
    </form>
@endsection

@section('scripts')
<script>
    function login() {

        var usuario = document.getElementById('usuario').value.trim();
        var password = document.getElementById('password').value;

        if (usuario === '') {
            toastr.error("Usuario es requerido");
            return;
        }

        if (password === '') {
            toastr.error("Contraseña es requerida");
            return;
        }

        if (usuario.length > 50) {
            toastr.error("Máximo 50 caracteres");
            return;
        }

        if (password.length > 16) {
            toastr.error("Máximo 16 caracteres");
            return;
        }

        openLoading();

        let formData = new FormData();
        formData.append('usuario', usuario);
        formData.append('password', password);

        axios.post('admin/login', formData)
            .then((response) => {
                closeLoading();
                verificar(response);
            })
            .catch(() => {
                closeLoading();
                toastr.error("Error en la respuesta");
            });
    }

    function verificar(response) {

        if (response.data.success === 0) {
            toastr.error("Validación incorrecta");
        } else if (response.data.success === 1) {
            window.location = response.data.ruta;
        } else if (response.data.success === 2) {
            toastr.error("Contraseña incorrecta");
        } else if (response.data.success === 3) {
            toastr.error("Usuario no encontrado");
        } else if (response.data.success === 4) {
            toastr.error('Usuario bloqueado. Contacte al administrador');
        } else {
            toastr.error('Error');
        }
    }
</script>
@endsection
