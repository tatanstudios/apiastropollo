@extends('adminlte::page')

@section('title', 'Rol y Permisos')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')
<div id="divcontenedor" style="display: none">
    <section class="content-header">
        <div class="container-fluid">
            <div class="col-sm-12">
                <h1>Rol y Permisos</h1>
            </div>
            <br>

            <button type="button" class="btn btn-success btn-sm" onclick="modalAgregar()">
                <i class="fas fa-edit"></i>
                Nuevo Rol
            </button>

            <button type="button" class="btn btn-success btn-sm" onclick="vistaPermisos()">
                <i class="fas fa-list-alt"></i>
                Lista de Permisos
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
                    <div id="tablaDatatable"></div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalAgregar">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Nuevo Rol</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-nuevo" onsubmit="return false;">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" maxlength="30" autocomplete="off" class="form-control" id="nombre-nuevo" placeholder="Nombre">
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="agregarRol()">Agregar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditar">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Editar Rol</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-editar" onsubmit="return false;">
                        <input type="hidden" id="id-editar">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" maxlength="30" autocomplete="off" class="form-control" id="nombre-editar" placeholder="Nombre">
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="editarRol()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBorrar">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Eliminar Rol</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idborrar">
                    <p>Esta acción eliminará el rol <b id="nombre-borrar"></b> con todos sus permisos.</p>
                    <p id="aviso-usuarios" class="text-danger" style="display: none"></p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-danger" onclick="borrar()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/jquery.dataTables.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/dataTables.bootstrap4.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('js/responsive.bootstrap4.min.js') }}"></script>

    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function(){
            recargar();
            document.getElementById("divcontenedor").style.display = "block";
        });
    </script>

    <script>

        function recargar(){
            var ruta = "{{ url('/admin/roles/tabla') }}";
            $('#tablaDatatable').load(ruta);
        }

        // ver y asignar permisos del rol
        function verInformacion(id){
            window.location.href="{{ url('/admin/roles/lista/permisos') }}/"+id;
        }

        // ver todos los permisos que existen
        function vistaPermisos(){
            window.location.href="{{ url('/admin/roles/permisos/lista') }}";
        }

        function modalAgregar(){
            document.getElementById("formulario-nuevo").reset();
            $('#modalAgregar').modal('show');
        }

        function agregarRol(){
            var nombre = document.getElementById('nombre-nuevo').value.trim();

            if(nombre === ''){
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 30){
                toastr.error('Máximo 30 caracteres para Nombre');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('nombre', nombre);

            axios.post('/admin/permisos/nuevo-rol', formData)
                .then((response) => {
                    closeLoading();

                    if (response.data.success === 1) {
                        toastr.error('Ya existe un rol con ese nombre');
                    }
                    else if(response.data.success === 2){
                        toastr.success('Rol agregado');
                        $('#modalAgregar').modal('hide');
                        recargar();
                    }
                    else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al guardar');
                });
        }

        function modalEditar(id, nombre){
            $('#id-editar').val(id);
            $('#nombre-editar').val(nombre);
            $('#modalEditar').modal('show');
        }

        function editarRol(){
            var id = document.getElementById('id-editar').value;
            var nombre = document.getElementById('nombre-editar').value.trim();

            if(nombre === ''){
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 30){
                toastr.error('Máximo 30 caracteres para Nombre');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('idrol', id);
            formData.append('nombre', nombre);

            axios.post('/admin/roles/editar', formData)
                .then((response) => {
                    closeLoading();

                    if (response.data.success === 1) {
                        toastr.error('Ya existe un rol con ese nombre');
                    }
                    else if(response.data.success === 2){
                        toastr.success('Rol actualizado');
                        $('#modalEditar').modal('hide');
                        recargar();
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

        function modalBorrar(id, nombre, usuarios){
            $('#idborrar').val(id);
            $('#nombre-borrar').text(nombre);

            if(usuarios > 0){
                $('#aviso-usuarios').text(usuarios + ' usuario(s) tienen este rol y quedarán sin rol.').show();
            }else{
                $('#aviso-usuarios').hide();
            }

            $('#modalBorrar').modal('show');
        }

        function borrar(){
            openLoading();
            var idrol = document.getElementById('idborrar').value;

            var formData = new FormData();
            formData.append('idrol', idrol);

            axios.post('/admin/roles/borrar-global', formData)
                .then((response) => {
                    closeLoading();
                    $('#modalBorrar').modal('hide');

                    if(response.data.success === 1){
                        toastr.success('Rol eliminado');
                        recargar();
                    }
                    else if(response.data.success === 2){
                        toastr.error('Perderías el acceso a Roles y Permisos: asígnate primero otro rol que tenga ese permiso');
                    }
                    else{
                        toastr.error('Error al eliminar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al eliminar');
                });
        }

    </script>
@stop
