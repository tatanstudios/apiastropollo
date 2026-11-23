@extends('frontend.login.plantilla')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
    <div class="icono-paso"><i class="fas fa-envelope"></i></div>

    <h2>Recuperar contraseña</h2>
    <p class="subtitulo">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva contraseña. El enlace es válido por 60 minutos.</p>

    <form data-accion="buscarCorreo" novalidate>
        <div class="campo">
            <label for="correo">Correo electrónico</label>
            <div class="campo__control">
                <span class="campo__icono"><i class="fas fa-envelope"></i></span>
                <input id="correo" type="email" maxlength="100" placeholder="nombre@correo.com" autocomplete="email" autofocus>
            </div>
        </div>

        <button type="submit" class="boton" id="btnLogin" style="margin-top: 8px">Enviar enlace</button>
    </form>

    <div class="volver">
        <a href="{{ url('/') }}" class="enlace"><i class="fas fa-arrow-left"></i> Volver a iniciar sesión</a>
    </div>
@endsection

@section('scripts')
<script>
    function buscarCorreo() {

        var correo = document.getElementById('correo').value.trim();

        if (correo === '') {
            toastr.error("Correo es requerido");
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
            toastr.error("Ingresa un correo válido");
            return;
        }

        openLoading();

        axios.post('/admin/enviar/correo/password', {
            'correo': correo
        })
            .then((response) => {
                closeLoading();

                if (response.data.success === 1) {
                    // correo enviado: se limpia el campo
                    document.getElementById('correo').value = '';

                    Swal.fire({
                        title: 'Correo enviado',
                        text: "Revisa tu bandeja de entrada (y la carpeta de spam) para recuperar tu contraseña.",
                        icon: 'success',
                        confirmButtonColor: '#c51818',
                        confirmButtonText: 'Aceptar'
                    });
                } else if (response.data.success === 2) {
                    // CORREO NO ENCONTRADO
                    Swal.fire({
                        title: 'No encontrado',
                        text: "El correo ingresado no se encuentra registrado.",
                        icon: 'info',
                        confirmButtonColor: '#c51818',
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    toastr.error('Error al enviar');
                }
            })
            .catch(() => {
                closeLoading();
                toastr.error('Error del servidor');
            });
    }
</script>
@endsection
