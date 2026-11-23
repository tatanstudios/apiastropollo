@extends('adminlte::page')

@section('title', 'Permisos Usuarios')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/buttons.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/estiloToggle.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')

<div id="divcontenedor" style="display: none">
    <section class="content-header">
        <div class="container-fluid">
            <div class="col-sm-12">
                <h1>Permisos Usuarios</h1>
            </div>
            <br>
            <button type="button" class="btn btn-success btn-sm" onclick="modalAgregar()">
                <i class="fas fa-pencil-alt"></i>
                Nuevo Usuario
            </button>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Lista</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div id="tablaDatatable"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalAgregar">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Nuevo Usuario</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-nuevo">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">

                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input type="text" maxlength="100" autocomplete="off" class="form-control" id="nombre-nuevo" placeholder="Nombre">
                                    </div>

                                    <div class="form-group">
                                        <label>Usuario</label>
                                        <input type="text" maxlength="25" autocomplete="off" class="form-control" id="usuario-nuevo" placeholder="Usuario">
                                    </div>

                                    <div class="form-group">
                                        <label>Contraseña</label>
                                        <input type="password" maxlength="16" autocomplete="new-password" class="form-control" id="password-nuevo" placeholder="Contraseña">
                                    </div>

                                    <div class="form-group">
                                        <label style="color:#191818">Roles</label>
                                        <div id="roles-nuevo">
                                            @foreach($roles as $key => $value)
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="rol-nuevo-{{ $key }}" value="{{ $key }}">
                                                    <label class="custom-control-label" for="rol-nuevo-{{ $key }}">{{ $value }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="nuevoUsuario()">Guardar</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modalEditar">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar Usuario</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-editar">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">

                                    <div class="form-group">
                                        <label style="color:#191818">Roles</label>
                                        <div id="roles-editar"></div>
                                    </div>

                                    <div class="form-group">
                                        <label>Nombre</label>
                                        <input type="hidden" id="id-editar">
                                        <input type="text" maxlength="100" autocomplete="off" class="form-control" id="nombre-editar">
                                    </div>

                                    <div class="form-group">
                                        <label>Usuario</label>
                                        <input type="text" maxlength="25" autocomplete="off" class="form-control" id="usuario-editar">
                                    </div>

                                    <div class="form-group">
                                        <label>Contraseña</label>
                                        <input type="password" maxlength="16" autocomplete="new-password" class="form-control" id="password-editar" placeholder="Dejar vacío para no cambiarla">
                                    </div>

                                    <div class="form-group">
                                        <label>Disponibilidad</label><br>
                                        <label class="switch" style="margin-top:10px">
                                            <input type="checkbox" id="toggle-editar">
                                            <div class="slider round">
                                                <span class="on">Activo</span>
                                                <span class="off">Inactivo</span>
                                            </div>
                                        </label>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="actualizar()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/jquery.dataTables.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.js') }}" type="text/javascript"></script>

    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="{{ asset('js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('js/jszip.min.js') }}"></script>
    <script src="{{ asset('js/buttons.html5.min.js') }}"></script>

    <!-- incluir tabla -->
    <script type="text/javascript">
        $(document).ready(function(){
            var ruta = "{{ URL::to('admin/permisos/tabla') }}";
            $('#tablaDatatable').load(ruta);
            document.getElementById("divcontenedor").style.display = "block";
        });
    </script>

    <script>

        function recargar(){
            var ruta = "{{ url('/admin/permisos/tabla') }}";
            $('#tablaDatatable').load(ruta);
        }

        function modalAgregar(){
            document.getElementById("formulario-nuevo").reset();
            $('#modalAgregar').modal('show');
        }

        // ids de los roles marcados dentro de un contenedor
        function rolesMarcados(contenedor){
            return $(contenedor + ' input[type=checkbox]:checked').map(function(){ return this.value; }).get();
        }

        // nuevo usuario de sistema
        function nuevoUsuario(){
            var nombre = document.getElementById('nombre-nuevo').value;
            var usuario = document.getElementById('usuario-nuevo').value;
            var password = document.getElementById('password-nuevo').value;
            var roles = rolesMarcados('#roles-nuevo');

            if(nombre === ''){
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 100){
                toastr.error('Máximo 100 caracteres para Nombre');
                return;
            }

            if(usuario === ''){
                toastr.error('Usuario es requerido');
                return;
            }

            if(usuario.length > 25){
                toastr.error('Máximo 25 caracteres para Usuario');
                return;
            }

            if(password === ''){
                toastr.error('Contraseña es requerido');
                return;
            }

            if(password.length < 4){
                toastr.error('Mínimo 4 caracteres para contraseña');
                return;
            }

            if(password.length > 16){
                toastr.error('Máximo 16 caracteres para contraseña');
                return;
            }

            if(roles.length === 0){
                toastr.error('Seleccionar al menos un rol');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('usuario', usuario);
            formData.append('password', password);
            roles.forEach(function(r){ formData.append('roles[]', r); });

            axios.post('/admin/permisos/nuevo-usuario', formData, {
            })
                .then((response) => {
                    closeLoading()

                    if (response.data.success === 1) {
                        toastr.error('Nombre Usuario ya existe');
                    }
                    else if(response.data.success === 2){
                        toastr.success('Agregado');
                        $('#modalAgregar').modal('hide');
                        recargar();
                    }
                    else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch((error) => {
                    closeLoading()
                    toastr.error('Error al guardar');
                });
        }

        // información de usuario
        function verInformacion(id){
            openLoading();
            document.getElementById("formulario-editar").reset();

            axios.post('/admin/permisos/info-usuario',{
                'id': id
            })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        $('#modalEditar').modal('show');
                        $('#id-editar').val(response.data.info.id);
                        $('#nombre-editar').val(response.data.info.nombre);
                        $('#usuario-editar').val(response.data.info.usuario);

                        var contenedor = $('#roles-editar').empty();
                        var asignados = response.data.idrol.map(String);

                        $.each(response.data.roles, function( key, val ){
                            var fila = $('<div class="custom-control custom-checkbox"></div>');
                            $('<input type="checkbox" class="custom-control-input">')
                                .attr('id', 'rol-editar-' + key)
                                .val(key)
                                .prop('checked', asignados.indexOf(String(key)) !== -1)
                                .appendTo(fila);
                            // .text escapa el nombre del rol
                            $('<label class="custom-control-label"></label>')
                                .attr('for', 'rol-editar-' + key)
                                .text(val)
                                .appendTo(fila);
                            contenedor.append(fila);
                        });

                        if(Number(response.data.info.activo) === 0){
                            $("#toggle-editar").prop("checked", false);
                        }else{
                            $("#toggle-editar").prop("checked", true);
                        }

                    }else{
                        toastr.error('Información no encontrado');
                    }

                })
                .catch((error) => {
                    closeLoading()
                    toastr.error('Información no encontrado');
                });
        }

        // actualizar el usuario
        function actualizar(){
            var id = document.getElementById('id-editar').value;
            var nombre = document.getElementById('nombre-editar').value;
            var usuario = document.getElementById('usuario-editar').value;
            var password = document.getElementById('password-editar').value;
            var roles = rolesMarcados('#roles-editar');

            var t = document.getElementById('toggle-editar').checked;
            var toggle = t ? 1 : 0;

            if(nombre === ''){
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 100){
                toastr.error('Máximo 100 caracteres para Nombre');
                return;
            }

            if(usuario === ''){
                toastr.error('Usuario es requerido');
                return;
            }

            if(usuario.length > 25){
                toastr.error('Máximo 25 caracteres para Usuario');
                return;
            }

            if(roles.length === 0){
                toastr.error('Seleccionar al menos un rol');
                return;
            }

            if(password.length > 0){
                if(password.length < 4){
                    toastr.error('Mínimo 4 caracteres para contraseña');
                    return;
                }

                if(password.length > 16){
                    toastr.error('Máximo 16 caracteres para contraseña');
                    return;
                }
            }

            openLoading()
            var formData = new FormData();
            formData.append('id', id);
            formData.append('nombre', nombre);
            formData.append('usuario', usuario);
            formData.append('password', password);
            formData.append('toggle', toggle);
            roles.forEach(function(r){ formData.append('roles[]', r); });

            axios.post('/admin/permisos/editar-usuario', formData, {
            })
                .then((response) => {
                    closeLoading()

                    if (response.data.success === 1) {
                        toastr.error('El Usuario ya existe');
                    }
                    else if(response.data.success === 2){
                        toastr.success('Actualizado');
                        $('#modalEditar').modal('hide');
                        recargar();
                    }
                    else if(response.data.success === 4){
                        toastr.error('No puedes desactivar tu propio usuario');
                    }
                    else if(response.data.success === 5){
                        toastr.error('Ninguno de los roles marcados tiene acceso a Roles y Permisos; perderías el acceso');
                    }
                    else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al actualizar');
                });
        }



    </script>
@stop
