@extends('adminlte::page')

@section('title', 'Cupones')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <style>
        #tabla-premios td { vertical-align: middle; }
        #tabla-premios tr.sin-premio .premio { visibility: hidden; }
    </style>
@stop

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a href="{{ route('index.servicios.listado') }}" class="btn btn-default btn-sm mr-3">
                    <i class="fas fa-arrow-left"></i> Restaurantes
                </a>
                <div>
                    <h1 class="mb-0">Cupones</h1>
                    <p class="text-muted mb-0">El cliente escribe el código al pagar y recibe el producto gratis del restaurante donde compra.</p>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#modalAyudaCupones">
                    <i class="far fa-question-circle"></i> ¿Cómo funcionan?
                </button>
                <button type="button" onclick="abrirNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nuevo Cupón
                </button>
            </div>
        </div>
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

@include('backend.admin.configuracion.cupones.parciales.ayuda_modal')

<!-- crear / editar cupon -->
<div class="modal fade" id="modalCupon">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="titulo-modal">Nuevo Cupón</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="id-cupon">

                <div class="row">
                    <div class="col-md-5 form-group">
                        <label for="nombre">Código</label>
                        <input type="text" maxlength="50" class="form-control" id="nombre" autocomplete="off" placeholder="Ej.: NuevaApp">
                        <small class="text-muted">Lo que escribe el cliente en la app.</small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="limite">Límite de usos</label>
                        <input type="number" min="1" step="1" class="form-control" id="limite" placeholder="100">
                        <small class="text-muted" id="texto-usado"></small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="d-block">Estado
                            <a href="#" data-toggle="modal" data-target="#modalAyudaCupones" title="¿Qué significa?"><i class="far fa-question-circle"></i></a>
                        </label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" class="custom-control-input" id="activo">
                            <label class="custom-control-label" for="activo">Activo</label>
                        </div>
                    </div>
                </div>

                <label>Dónde vale y qué regala</label>
                <table class="table table-sm table-bordered mb-1" id="tabla-premios">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 40%">Restaurante</th>
                        <th>Producto gratis</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($servicios as $s)
                        <tr data-servicio="{{ $s->id }}" class="sin-premio">
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input vale" id="vale-{{ $s->id }}" onchange="marcarRestaurante(this)">
                                    <label class="custom-control-label" for="vale-{{ $s->id }}">{{ $s->nombre }}</label>
                                </div>
                                @if($s->utiliza_cupon != 1)
                                    <small class="text-danger d-block">No acepta cupones: actívalo en Restaurantes → Editar.</small>
                                @endif
                            </td>
                            <td>
                                <input type="text" maxlength="100" class="form-control form-control-sm premio" placeholder="Ej.: 1 pieza gratis">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <small class="text-muted">El límite de usos es uno solo para todos los restaurantes marcados.</small>
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
        $(function () { recargar(); });

        function recargar() {
            $('#tablaDatatable').load("{{ url('/admin/cupones/listado/tabla') }}");
        }

        function marcarRestaurante(casilla) {
            var fila = $(casilla).closest('tr').toggleClass('sin-premio', !casilla.checked);
            if (casilla.checked) { fila.find('.premio').focus(); }
        }

        function ponerPremio(servicio, premio) {
            var fila = $('#tabla-premios tr[data-servicio="' + servicio + '"]');
            fila.find('.vale').prop('checked', premio !== null);
            fila.find('.premio').val(premio || '');
            fila.toggleClass('sin-premio', premio === null);
        }

        function limpiarPremios() {
            $('#tabla-premios tbody tr').each(function () { ponerPremio($(this).data('servicio'), null); });
        }

        function abrirNuevo() {
            $('#titulo-modal').text('Nuevo Cupón');
            $('#id-cupon').val('');
            $('#nombre').val('');
            $('#limite').val('');
            $('#texto-usado').text('');
            $('#activo').prop('checked', true);
            limpiarPremios();
            $('#modalCupon').modal({ backdrop: 'static', keyboard: false });
        }

        function abrirEditar(id) {
            openLoading();
            axios.post('/admin/cupones/informacion', { id: id })
                .then(function (response) {
                    closeLoading();
                    if (response.data.success !== 1) { toastr.error('Cupón no encontrado'); return; }

                    var c = response.data.cupon;
                    $('#titulo-modal').text('Editar Cupón');
                    $('#id-cupon').val(c.id);
                    $('#nombre').val(c.texto_cupon);
                    $('#limite').val(c.uso_limite);
                    $('#texto-usado').text('Usado ' + c.contador + (c.contador == 1 ? ' vez' : ' veces'));
                    $('#activo').prop('checked', c.activo == 1);
                    limpiarPremios();
                    $.each(response.data.premios, function (i, p) { ponerPremio(p.id_servicios, p.nombre); });

                    $('#modalCupon').modal({ backdrop: 'static', keyboard: false });
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        function guardar() {
            var id = $('#id-cupon').val();
            var nombre = $('#nombre').val().trim();
            var limite = $('#limite').val().trim();

            if (!nombre) { toastr.error('Escribe el código del cupón'); return; }
            if (!/^\d+$/.test(limite) || Number(limite) < 1) { toastr.error('El límite de usos debe ser un número entero mayor que 0'); return; }

            var premios = [], error = null;
            $('#tabla-premios tbody tr').each(function () {
                var fila = $(this);
                if (!fila.find('.vale').prop('checked')) return;
                var premio = fila.find('.premio').val().trim();
                if (!premio) { error = error || 'Escribe qué regala ' + fila.find('label').text().trim(); }
                premios.push({ servicio: fila.data('servicio'), premio: premio });
            });
            if (error) { toastr.error(error); return; }

            var datos = { nombre: nombre, limite: limite, activo: $('#activo').prop('checked') ? 1 : 0, premios: premios };
            if (id) { datos.id = id; }

            openLoading();
            axios.post('/admin/cupones/guardar', datos)
                .then(function (response) {
                    closeLoading();
                    var r = response.data;
                    if (r.success === 1) {
                        toastr.success(id ? 'Cupón actualizado' : 'Cupón creado');
                        $('#modalCupon').modal('hide');
                        recargar();
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

        function cambiarActivo(id, interruptor) {
            axios.post('/admin/cupones/activo', { id: id, activo: interruptor.checked ? 1 : 0 })
                .then(function (response) {
                    if (response.data.success === 1) {
                        toastr.success(interruptor.checked ? 'Cupón activado' : 'Cupón desactivado');
                        recargar();
                    } else {
                        interruptor.checked = !interruptor.checked;
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(function () {
                    interruptor.checked = !interruptor.checked;
                    toastr.error('Error del servidor');
                });
        }
    </script>
@stop
