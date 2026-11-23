@extends('adminlte::page')

@section('title', 'Productos Populares')

@section('css')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
@stop

@section('content')
<style>
    .pop-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
    .pop-header h1 { margin:0; font-size:1.75rem; }
    .pop-sub { color:#6c757d; font-size:.92rem; margin-top:2px; }
    .pop-hint { color:#6c757d; font-size:.85rem; }

    .pop-modal .modal-header { border-bottom:0; padding-bottom:0; }
    .pop-modal .modal-title { font-weight:600; }
    .pop-modal .modal-title small { display:block; font-size:.85rem; font-weight:400; color:#6c757d; margin-top:2px; }
    .pop-modal .modal-footer { border-top:1px solid #f1f1f1; }
    .pop-modal label { font-weight:600; font-size:.9rem; }
    .pop-modal .select2-container { width:100% !important; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="pop-header">
            <div>
                <h1>Productos Populares</h1>
                <div class="pop-sub"><i class="fas fa-store mr-1"></i>{{ $restaurante ?: 'Restaurante' }}</div>
            </div>
            <div>
                <a href="{{ route('index.servicios.listado') }}" class="btn btn-outline-secondary btn-sm mr-1">
                    <i class="fas fa-arrow-left mr-1"></i> Restaurantes
                </a>
                <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Agregar producto
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <span class="pop-hint"><i class="fas fa-arrows-alt-v mr-1"></i>Se muestran destacados en el inicio de la app, en este orden. Arrastra las filas para cambiarlo.</span>
            </div>
            <div class="card-body p-0">
                <div id="tablaDatatable"></div>
            </div>
        </div>
    </div>
</section>

<!-- modal agregar -->
<div class="modal fade pop-modal" id="modalAgregar">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Agregar producto popular
                    <small><i class="fas fa-store mr-1"></i>{{ $restaurante ?: 'Restaurante' }}</small>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-nuevo" onsubmit="return false">
                    <div class="form-group mb-2">
                        <label for="select-productos">Producto <span class="text-danger">*</span></label>
                        <select class="form-control" id="select-productos">
                            <option value=""></option>
                            @foreach($grupoProductos as $grupo => $lista)
                                <optgroup label="{{ $grupo }}">
                                    @foreach($lista as $item)
                                        <option value="{{ $item->id }}" data-nombre="{{ $item->nombre }}">
                                            {{ $item->nombre }} · ${{ number_format((float) $item->precio, 2) }}{{ $item->activo ? '' : ' (oculto)' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Los productos que ya son populares aparecen desactivados.</small>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarRegistro()"><i class="fas fa-check mr-1"></i>Agregar</button>
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
    <script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>

    <script>
        const ID_SERVICIO = {{ $id }};

        $(document).ready(function(){
            recargar();

            $('#select-productos').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalAgregar'),
                placeholder: 'Buscar producto...',
                language: { noResults: function(){ return "Búsqueda no encontrada"; } },
            });
        });

        function recargar(){
            $('#tablaDatatable').load("{{ URL::to('/admin/productos/servicio/principales/tabla') }}/" + ID_SERVICIO);
        }

        // desactiva en el selector los productos que ya son populares
        function marcarYaAgregados(){
            var ya = $('#tablecontents tr.row1').map(function(){ return String($(this).data('producto')); }).get();
            $('#select-productos option').each(function(){
                var $o = $(this);
                if (!$o.val()) return;
                var agregado = ya.indexOf($o.val()) !== -1;
                $o.prop('disabled', agregado);
                var texto = $o.text().replace(/ — ya es popular$/, '');
                $o.text(agregado ? texto + ' — ya es popular' : texto);
            });
        }

        // abrir modal
        function modalNuevo(){
            marcarYaAgregados();
            $('#select-productos').val('').trigger('change');
            $('#modalAgregar').modal('show');
        }

        $('#modalAgregar').on('shown.bs.modal', function(){ $('#select-productos').select2('open'); });

        function guardarRegistro(){

            var idproducto = $('#select-productos').val();

            if(!idproducto) {
                toastr.error('Selecciona un producto');
                return;
            }

            openLoading();

            var formData = new FormData();
            formData.append('idservicio', ID_SERVICIO);
            formData.append('idproducto', idproducto);

            axios.post('/admin/productos/servicio/principales/nuevo', formData)
                .then((response) => {
                    closeLoading();

                    if (response.data.success === 1) {
                        toastr.info('Ese producto ya es popular');
                    }
                    else if (response.data.success === 2) {
                        $('#modalAgregar').modal('hide');
                        toastr.success('Producto agregado a populares');
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

        function borrarPrincipal(id, nombre) {
            Swal.fire({
                title: '¿Quitar de populares?',
                text: '«' + nombre + '» dejará de mostrarse destacado. El producto no se borra.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Quitar'
            }).then((result) => {
                if (result.isConfirmed) {
                    borrar(id);
                }
            })
        }

        function borrar(id){

            openLoading();
            var formData = new FormData();
            formData.append('id', id);

            axios.post('/admin/productos/servicio/principales/borrar', formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Quitado de populares');
                        recargar();
                    }
                    else {
                        toastr.error('Error al quitar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error de servidor');
                });
        }

    </script>
@stop
