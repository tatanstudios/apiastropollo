@extends('adminlte::page')

@section('title', 'Permisos del Rol')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
@stop

@section('content')
<!-- Lista de permisos de un Rol; se llega aqui seleccionando un Rol -->
<section class="content-header">
    <div class="container-fluid">
        <div class="col-sm-12">
            <h1>Permisos del rol: {{ $rol->name }}</h1>
        </div>
        <br>
        <button type="button" class="btn btn-default btn-sm" onclick="window.location.href='{{ route('admin.roles.index') }}'">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
        <button type="button" class="btn btn-success btn-sm" onclick="modalAgregar()">
            <i class="fas fa-pencil-alt"></i>
            Agregar Permiso
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
                <h4 class="modal-title">Agregar Permiso</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label style="color:#191818">Permiso</label>
                    <select class="form-control" id="permiso-nuevo" style="width: 100%">
                        @foreach($permisos as $permiso)
                            <option value="{{ $permiso->id }}">{{ $permiso->name }}{{ $permiso->description ? ' - '.$permiso->description : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="agregarPermiso()">Agregar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBorrar">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Quitar Permiso</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="idborrar">
                <p>Se quitará el permiso <b id="nombre-borrar"></b> del rol <b>{{ $rol->name }}</b>.</p>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" onclick="borrar()">Quitar</button>
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
    <script src="{{ asset('js/sweetalert2.all.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>
        var idRol = {{ (int) $rol->id }};

        $(document).ready(function(){
            $('#permiso-nuevo').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalAgregar'),
                "language": {
                    "noResults": function(){
                        return "Búsqueda no encontrada";
                    }
                },
            });

            recargar();
        });

        function recargar(){
            var ruta = "{{ url('/admin/roles/permisos/tabla') }}/" + idRol;
            $('#tablaDatatable').load(ruta);
        }

        // recarga el select con los permisos que el rol aun no tiene
        function recargarDisponibles(){
            axios.get("{{ url('/admin/roles/permisos/disponibles') }}/" + idRol)
                .then((response) => {
                    if(response.data.success !== 1) return;
                    var select = document.getElementById('permiso-nuevo');
                    select.options.length = 0;
                    response.data.permisos.forEach(function(p){
                        var texto = p.description ? (p.name + ' - ' + p.description) : p.name;
                        select.add(new Option(texto, p.id));
                    });
                    $('#permiso-nuevo').trigger('change');
                });
        }

        function modalBorrar(id, nombre){
            $('#idborrar').val(id);
            $('#nombre-borrar').text(nombre);
            $('#modalBorrar').modal('show');
        }

        function borrar(){
            openLoading();
            var idpermiso = document.getElementById('idborrar').value;

            var formData = new FormData();
            formData.append('idpermiso', idpermiso);
            formData.append('idrol', idRol);

            axios.post('/admin/roles/permiso/borrar', formData)
                .then((response) => {
                    closeLoading();
                    $('#modalBorrar').modal('hide');

                    if(response.data.success === 1){
                        toastr.success('Permiso quitado');
                        recargar();
                        recargarDisponibles();
                    }
                    else if(response.data.success === 2){
                        toastr.error('Perderías el acceso a Roles y Permisos: asígnate primero otro rol que tenga ese permiso');
                    }
                    else{
                        toastr.error('Error al quitar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al quitar');
                });
        }

        function modalAgregar(){
            if(document.getElementById('permiso-nuevo').options.length === 0){
                toastr.info('El rol ya tiene todos los permisos');
                return;
            }
            $('#modalAgregar').modal('show');
        }

        function agregarPermiso(){
            var idpermiso = document.getElementById('permiso-nuevo').value;

            if(idpermiso === ''){
                toastr.error('Seleccionar un permiso');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('idpermiso', idpermiso);
            formData.append('idrol', idRol);

            axios.post('/admin/roles/permiso/agregar', formData)
                .then((response) => {
                    closeLoading();
                    $('#modalAgregar').modal('hide');

                    if(response.data.success === 1){
                        toastr.success('Permiso agregado');
                        recargar();
                        recargarDisponibles();
                    }else{
                        toastr.error('Error al agregar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al agregar');
                });
        }

    </script>
@stop
