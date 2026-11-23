@extends('frontend.login.plantilla')

@section('titulo', 'Nueva contraseña')

@section('contenido')
    <div class="icono-paso"><i class="fas fa-key"></i></div>

    <h2>Nueva contraseña</h2>
    <p class="subtitulo">Crea una contraseña nueva para tu cuenta del panel.</p>

    <form data-accion="cambioDePassword" novalidate>
        <div class="campo">
            <label for="password">Nueva contraseña</label>
            <div class="campo__control">
                <span class="campo__icono"><i class="fas fa-lock"></i></span>
                <input id="password" type="password" maxlength="16" placeholder="Entre 4 y 16 caracteres" autocomplete="new-password" autofocus>
                <button type="button" class="ver-clave" data-para="password" aria-label="Mostrar contraseña"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <div class="campo">
            <label for="password2">Confirmar contraseña</label>
            <div class="campo__control">
                <span class="campo__icono"><i class="fas fa-lock"></i></span>
                <input id="password2" type="password" maxlength="16" placeholder="Repite la contraseña" autocomplete="new-password">
                <button type="button" class="ver-clave" data-para="password2" aria-label="Mostrar contraseña"><i class="fas fa-eye"></i></button>
            </div>
        </div>

        <button type="submit" class="boton" id="btnLogin" style="margin-top: 8px">Guardar contraseña</button>
    </form>

    <div class="volver">
        <a href="{{ url('/') }}" class="enlace"><i class="fas fa-arrow-left"></i> Volver a iniciar sesión</a>
    </div>
@endsection

@section('scripts')
<script>
    function cambioDePassword() {

        var contrasena = document.getElementById('password').value;
        var confirmacion = document.getElementById('password2').value;

        if (contrasena === '') {
            toastr.error("La contraseña nueva es requerida");
            return;
        }

        if (contrasena.length < 4) {
            toastr.error("Mínimo 4 caracteres");
            return;
        }

        if (contrasena !== confirmacion) {
            toastr.error("Las contraseñas no coinciden");
            return;
        }

        openLoading();

        var token = "{{ $token }}";

        let formData = new FormData();
        formData.append('token', token);
        formData.append('contrasena', contrasena);

        axios.post('/admin/administrador/actualizacion/password', formData)
            .then((response) => {
                closeLoading();

                if (response.data.success === 1) {
                    Swal.fire({
                        title: 'Contraseña actualizada',
                        text: "Ya puedes iniciar sesión con tu nueva contraseña.",
                        icon: 'success',
                        allowOutsideClick: false,
                        confirmButtonColor: '#c51818',
                        confirmButtonText: 'Iniciar sesión'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            volver();
                        }
                    });
                } else if (response.data.success === 2) {
                    // LINK EXPIRADO O TOKEN MALO
                    Swal.fire({
                        title: 'El enlace expiró',
                        text: "Solicita de nuevo el cambio de contraseña.",
                        icon: 'warning',
                        allowOutsideClick: false,
                        confirmButtonColor: '#c51818',
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            volver();
                        }
                    });
                } else {
                    toastr.error('Error al guardar');
                }
            })
            .catch(() => {
                closeLoading();
                toastr.error('Error del servidor');
            });
    }

    function volver() {
        window.location.href = "{{ url('/') }}";
    }
</script>
@endsection
