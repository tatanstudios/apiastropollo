@extends('adminlte::page')

@section('title', 'Sub Categorías')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')
<style>
    .sub-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
    .sub-header h1 { margin:0; font-size:1.75rem; }
    .sub-ruta { color:#6c757d; font-size:.92rem; margin-top:2px; }
    .sub-ruta a { color:#6c757d; }
    .sub-ruta a:hover { color:#007bff; }
    .sub-ruta .sep { margin:0 6px; font-size:.7rem; }
    .sub-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; }
    .sub-toolbar .input-group { max-width:320px; }
    .sub-hint { color:#6c757d; font-size:.85rem; }

    .sub-modal .modal-header { border-bottom:0; padding-bottom:0; }
    .sub-modal .modal-title { font-weight:600; }
    .sub-modal .modal-title small { display:block; font-size:.85rem; font-weight:400; color:#6c757d; margin-top:2px; }
    .sub-modal .modal-footer { border-top:1px solid #f1f1f1; }
    .sub-modal label { font-weight:600; font-size:.9rem; }
    .sub-seccion { background:#f8f9fa; border:1px solid #eef0f2; border-radius:10px; padding:14px 16px; }
    .sub-seccion .custom-control-label { font-weight:600; cursor:pointer; }
    .sub-seccion .ayuda { color:#6c757d; font-size:.82rem; margin:2px 0 0; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="sub-header">
            <div>
                <h1>Sub Categorías</h1>
                <div class="sub-ruta">
                    <i class="fas fa-store mr-1"></i>
                    <a href="{{ url('/admin/categorias/listado/'.$idservicio) }}">{{ $restaurante ?: 'Restaurante' }}</a>
                    <i class="fas fa-chevron-right sep"></i>
                    <b class="text-dark">{{ $nomcategoria }}</b>
                </div>
            </div>
            <div>
                <a href="{{ url('/admin/categorias/listado/'.$idservicio) }}" class="btn btn-outline-secondary btn-sm mr-1">
                    <i class="fas fa-arrow-left mr-1"></i> Categorías
                </a>
                <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nueva Sub Categoría
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="sub-toolbar">
                    <span class="sub-hint"><i class="fas fa-arrows-alt-v mr-1"></i>Arrastra las filas para cambiar el orden en el menú</span>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="text" id="buscar-sub" class="form-control" placeholder="Buscar sub categoría..." autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="tablaDatatable"></div>
            </div>
        </div>
    </div>
</section>

<!-- modal agregar -->
<div class="modal fade sub-modal" id="modalAgregar">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Nueva Sub Categoría
                    <small><i class="fas fa-folder mr-1"></i>{{ $nomcategoria }}</small>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-nuevo" onsubmit="guardarRegistro(); return false;">
                    <div class="form-group">
                        <label for="nombre-nuevo">Nombre <span class="text-danger">*</span></label>
                        <input type="text" maxlength="100" class="form-control" autocomplete="off" id="nombre-nuevo" placeholder="Ej. Combos de 2 piezas">
                    </div>

                    <div class="alert alert-light border small mb-0">
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        Se crea <b>oculta</b>. Agrega productos y luego actívala desde <b>Editar</b>.
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarRegistro()"><i class="fas fa-check mr-1"></i>Guardar</button>
            </div>
        </div>
    </div>
</div>


<!-- modal editar -->
<div class="modal fade sub-modal" id="modalEditar">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Editar Sub Categoría
                    <small><i class="fas fa-folder mr-1"></i>{{ $nomcategoria }}</small>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-editar" onsubmit="editar(); return false;">
                    <input type="hidden" id="id-editar">

                    <div class="form-group">
                        <label for="nombre-editar">Nombre <span class="text-danger">*</span></label>
                        <input type="text" maxlength="100" class="form-control" autocomplete="off" id="nombre-editar" placeholder="Nombre">
                    </div>

                    <div class="sub-seccion">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="toggle-activo">
                            <label class="custom-control-label" for="toggle-activo">Visible en el menú</label>
                        </div>
                        <p class="ayuda">Apágalo para ocultar la sub categoría a los clientes sin borrarla.</p>

                        <div id="aviso-sin-productos" class="small mt-2" style="display:none; color:#856404">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            No tiene productos activos, así que aún no se puede hacer visible.
                            <a href="#" id="link-productos-editar">Ir a productos</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="editar()"><i class="fas fa-check mr-1"></i>Guardar cambios</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/jquery-ui-drag.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/datatables-drag.min.js') }}" type="text/javascript"></script>

    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script type="text/javascript">
        $(document).ready(function(){
            recargar();
        });
    </script>

    <script>

        function recargar(){
            var id = {{ $id }};
            var ruta = "{{ URL::to('/admin/sub/categorias/listado/tabla/') }}/"+id;
            $('#tablaDatatable').load(ruta, filtrarSub);
        }

        // filtro rapido por nombre
        function filtrarSub(){
            var texto = ($('#buscar-sub').val() || '').toLowerCase().trim();
            var visibles = 0;
            $('#tablecontents tr.row1').each(function(){
                var coincide = $(this).data('nombre').toString().indexOf(texto) !== -1;
                $(this).toggle(coincide);
                if(coincide) visibles++;
            });
            $('#sin-resultados').toggle(visibles === 0 && texto !== '');
        }

        $(document).on('input', '#buscar-sub', filtrarSub);

        // abrir modal
        function modalNuevo(){
            document.getElementById("formulario-nuevo").reset();
            $('#modalAgregar').modal('show');
        }

        $('#modalAgregar').on('shown.bs.modal', function(){ $('#nombre-nuevo').trigger('focus'); });
        $('#modalEditar').on('shown.bs.modal', function(){ $('#nombre-editar').trigger('focus'); });

        //nueva sub categoria
        function guardarRegistro(){

            var nombre = document.getElementById('nombre-nuevo').value.trim();

            if(nombre === '') {
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 100){
                toastr.error('Nombre máximo 100 caracteres');
                return;
            }

            // id de categoria
            var id = {{ $id }};

            openLoading();

            var formData = new FormData();
            formData.append('id', id);
            formData.append('nombre', nombre);

            axios.post('/admin/sub/categorias/nuevo', formData, {
            })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        $('#modalAgregar').modal('hide');
                        toastr.success('Registrado correctamente');
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

        function informacion(id){

            document.getElementById("formulario-editar").reset();
            openLoading();

            axios.post('/admin/sub/categorias/informacion',{
                'id': id
            })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){

                        $('#modalEditar').modal('show');
                        $('#id-editar').val(id);
                        $('#nombre-editar').val(response.data.subcategoria.nombre);

                        var activa = response.data.subcategoria.activo !== 0;
                        $("#toggle-activo").prop("checked", activa);

                        // sin productos activos no se puede activar (regla del backend)
                        var activos = parseInt($('tr.row1[data-id="' + id + '"]').data('activos') || 0, 10);
                        var bloquear = !activa && activos === 0;
                        $("#toggle-activo").prop("disabled", bloquear);
                        $('#aviso-sin-productos').toggle(bloquear);
                        $('#link-productos-editar').attr('href', "{{ url('/admin/productos/listado') }}/" + id);

                    }else{
                        toastr.error('Error al buscar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al buscar');
                    closeLoading();
                });
        }

        function editar(){

            var id = document.getElementById('id-editar').value;
            var nombre = document.getElementById('nombre-editar').value.trim();
            var cbactivo = document.getElementById('toggle-activo').checked;

            var toggleActivo = cbactivo ? 1 : 0;

            if(nombre === '') {
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 100){
                toastr.error('Nombre máximo 100 caracteres');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('id', id);
            formData.append('nombre', nombre);
            formData.append('cbactivo', toggleActivo);

            axios.post('/admin/sub/categorias/editar', formData, {
            })
                .then((response) => {
                    closeLoading();

                    if (response.data.success === 1) {

                        // no hay ningun producto activo en esta sub categoria
                        Swal.fire({
                            title: 'No guardado',
                            text: "La sub categoría no puede activarse porque no tiene productos activos.",
                            icon: 'info',
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Aceptar'
                        });

                    }
                    else if (response.data.success === 2) {
                        $('#modalEditar').modal('hide');
                        toastr.success('Actualizado correctamente');
                        recargar();
                    }
                    else {
                        toastr.error('Error al Editar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al Editar');
                    closeLoading();
                });
        }

        function verProductos(id) {
            window.location.href="{{ url('/admin/productos/listado') }}/"+id;
        }

    </script>
@stop
