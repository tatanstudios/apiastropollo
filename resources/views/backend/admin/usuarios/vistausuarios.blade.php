@extends('adminlte::page')

@section('title', 'Usuarios de restaurante')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="mb-0">Usuarios de restaurante</h1>
                <p class="text-muted mb-0">Cuentas con las que cada restaurante entra a su app para recibir órdenes. Un restaurante puede tener varios usuarios y todos reciben las notificaciones.</p>
            </div>
            <div class="mt-2">
                <button type="button" onclick="abrirNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>
        @if($sinUsuario->count())
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Sin usuario activo (no pueden recibir órdenes en su app):
                @foreach($sinUsuario as $s)
                    <a href="#" class="alert-link ml-2" onclick="abrirNuevo({{ $s->id }}); return false;">
                        {{ $s->nombre }} <i class="fas fa-plus-circle"></i>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-body">
                <div id="tablaDatatable"></div>
            </div>
        </div>
    </div>
</section>

<!-- crear / editar usuario -->
<div class="modal fade" id="modalUsuario">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="titulo-modal">Nuevo Usuario</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario" onsubmit="return false;" autocomplete="off">
                    <input type="hidden" id="id-usuario">

                    <div class="form-group">
                        <label for="servicio">Restaurante</label>
                        <select class="form-control" id="servicio">
                            <option value="">Seleccionar...</option>
                            @foreach($servicios as $s)
                                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" id="ayuda-servicio"></small>
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" maxlength="100" class="form-control" id="nombre" placeholder="Ej.: Sucursal Metapán">
                    </div>

                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" maxlength="20" class="form-control" id="usuario" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Ej.: SucursalMetapan">
                        <small class="text-muted">Con esto entra a la app del restaurante. Sin espacios.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="password">Contraseña</label>
                        <div class="input-group">
                            <input type="password" maxlength="50" class="form-control" id="password" autocomplete="new-password">
                            <div class="input-group-append">
                                <button class="btn btn-default" type="button" onclick="verPassword()" title="Mostrar u ocultar">
                                    <i class="far fa-eye" id="icono-password"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted" id="ayuda-password">Mínimo 4 caracteres.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" onclick="guardar()">Guardar</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/jquery.dataTables.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/dataTables.responsive.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/responsive.bootstrap4.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>
        $(function () {
            recargar();

            // el usuario no lleva espacios: ni al escribir ni al pegar
            $('#usuario')
                .on('keydown', function (e) { if (e.key === ' ' || e.key === 'Spacebar') { e.preventDefault(); } })
                .on('input', function () {
                    var limpio = this.value.replace(/\s+/g, '');
                    if (limpio !== this.value) { this.value = limpio; }
                });

            // viene de Restaurantes > "Sin usuario"
            var nuevo = new URLSearchParams(location.search).get('nuevo');
            if (nuevo) { abrirNuevo(Number(nuevo)); }
        });

        function recargar() {
            $('#tablaDatatable').load("{{ url('/admin/restaurantes/usuario/tabla') }}");
        }

        function verPassword() {
            var campo = $('#password'), visible = campo.attr('type') === 'text';
            campo.attr('type', visible ? 'password' : 'text');
            $('#icono-password').toggleClass('fa-eye', visible).toggleClass('fa-eye-slash', !visible);
        }

        function limpiar() {
            document.getElementById('formulario').reset();
            $('#password').attr('type', 'password');
            $('#icono-password').addClass('fa-eye').removeClass('fa-eye-slash');
        }

        function abrirNuevo(idServicio) {
            limpiar();
            $('#titulo-modal').text('Nuevo Usuario');
            $('#id-usuario').val('');
            $('#servicio').val(idServicio || '').prop('disabled', false);
            $('#ayuda-servicio').text('Puede tener varios usuarios; cada uno entra con su propia cuenta.');
            $('#ayuda-password').text('Mínimo 4 caracteres.');
            $('#modalUsuario').modal({ backdrop: 'static', keyboard: false });
        }

        function abrirEditar(id) {
            openLoading();
            axios.post('/admin/restaurantes/usuario/informacion', { id: id })
                .then(function (response) {
                    closeLoading();
                    if (response.data.success !== 1) { toastr.error('Usuario no encontrado'); return; }

                    var u = response.data.info;
                    limpiar();
                    $('#titulo-modal').text('Editar Usuario');
                    $('#id-usuario').val(u.id);
                    $('#servicio').val(u.id_servicios).prop('disabled', true);
                    $('#ayuda-servicio').text('El restaurante no se cambia: crea otro usuario para otro restaurante.');
                    $('#nombre').val(u.nombre);
                    $('#usuario').val(u.usuario);
                    $('#ayuda-password').text('Déjala vacía para no cambiarla. Si la cambias, se cierra la sesión en la app.');
                    $('#modalUsuario').modal({ backdrop: 'static', keyboard: false });
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        function guardar() {
            var id = $('#id-usuario').val();
            var datos = {
                nombre: $('#nombre').val().trim(),
                usuario: $('#usuario').val().trim(),
                password: $('#password').val()
            };

            if (!id && !$('#servicio').val()) { toastr.error('Selecciona el restaurante'); return; }
            if (!datos.nombre) { toastr.error('Escribe el nombre'); return; }
            if (!datos.usuario) { toastr.error('Escribe el usuario'); return; }
            if (/\s/.test(datos.usuario)) { toastr.error('El usuario no puede tener espacios'); return; }
            if (!id && !datos.password) { toastr.error('Escribe la contraseña'); return; }
            if (datos.password && datos.password.length < 4) { toastr.error('La contraseña debe tener al menos 4 caracteres'); return; }

            var ruta = '/admin/restaurantes/usuario/nuevo';
            if (id) { datos.id = id; ruta = '/admin/restaurantes/usuario/actualizar'; }
            else { datos.idservicio = $('#servicio').val(); }

            openLoading();
            axios.post(ruta, datos)
                .then(function (response) {
                    closeLoading();
                    var r = response.data;
                    if (r.success === 1) {
                        toastr.success(id ? (r.cerroSesion ? 'Usuario actualizado; se cerró su sesión en la app' : 'Usuario actualizado') : 'Usuario creado');
                        $('#modalUsuario').modal('hide');
                        // la alerta de restaurantes sin usuario cambia: recargar la pagina completa al crear
                        if (!id) { location.href = location.pathname; } else { recargar(); }
                    } else if (r.success === 0) {
                        toastr.error(r.mensaje || 'Revisa los datos');
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        function cambiarBloqueo(id, bloquear, boton) {
            var usuario = $(boton).data('nombre');
            Swal.fire({
                title: bloquear ? '¿Bloquear a ' + usuario + '?' : '¿Desbloquear a ' + usuario + '?',
                text: bloquear
                    ? 'Se cierra su sesión, ya no podrá entrar a la app del restaurante y deja de recibir notificaciones.'
                    : 'Podrá volver a entrar a la app del restaurante.',
                icon: bloquear ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: bloquear ? '#d33' : '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: bloquear ? 'Sí, bloquear' : 'Sí, desbloquear',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                openLoading();
                axios.post('/admin/restaurantes/usuario/bloquear', { id: id, bloqueado: bloquear })
                    .then(function (response) {
                        closeLoading();
                        if (response.data.success === 1) {
                            toastr.success(bloquear ? 'Usuario bloqueado' : 'Usuario desbloqueado');
                            location.href = location.pathname;
                        } else {
                            toastr.error(response.data.mensaje || 'Error al actualizar');
                        }
                    })
                    .catch(function () {
                        closeLoading();
                        toastr.error('Error del servidor');
                    });
            });
        }
    </script>
@stop
