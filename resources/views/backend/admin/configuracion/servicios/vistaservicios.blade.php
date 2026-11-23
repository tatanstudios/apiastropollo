@extends('adminlte::page')

@section('title', 'Restaurantes')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <style>
        #tabla-horario td { vertical-align: middle; }
        #tabla-horario tr.dia-cerrado input[type=time] { opacity: .4; }
    </style>
@stop

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="mb-0">Restaurantes</h1>
                <p class="text-muted mb-0">Horario, zonas que atiende, usuario de la app y menú de cada restaurante.</p>
            </div>
            <div class="mt-2">
                <a href="{{ route('index.vistas.zonas') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-map-marked-alt"></i> Todas las zonas
                </a>
                <a href="{{ route('index.cupones.listado') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-ticket-alt"></i> Cupones
                </a>
                <button type="button" onclick="cerrarTodo()" class="btn btn-danger btn-sm">
                    <i class="fas fa-store-slash"></i> Cerrar todo
                </button>
                <button type="button" onclick="abrirTodo()" class="btn btn-default btn-sm">
                    <i class="fas fa-store"></i> Abrir todo
                </button>
                <button type="button" onclick="abrirModalCierres()" class="btn btn-default btn-sm">
                    <i class="far fa-calendar-alt"></i> Cierres programados
                </button>
                <button type="button" onclick="abrirNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nuevo Restaurante
                </button>
            </div>
        </div>
        @if($zonasSinRestaurante)
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Hay {{ $zonasSinRestaurante }} {{ $zonasSinRestaurante === 1 ? 'zona' : 'zonas' }} sin restaurante: no se pueden hacer visibles.
                <a href="{{ route('index.zonas.restaurante', 'sin') }}" class="alert-link ml-1">Ver y asignar</a>
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

@include('backend.admin.configuracion.zonas.parciales.cierres_modal')
@include('backend.admin.configuracion.cupones.parciales.ayuda_modal')

<!-- crear / editar restaurante -->
<div class="modal fade" id="modalRestaurante">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="titulo-modal">Nuevo Restaurante</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="id-restaurante">

                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="nombre">Nombre del restaurante</label>
                            <input type="text" maxlength="100" class="form-control" id="nombre" autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="whatsapp"><i class="fab fa-whatsapp text-success"></i> WhatsApp <small class="text-muted">(opcional)</small></label>
                            <input type="tel" maxlength="20" class="form-control" id="whatsapp" autocomplete="off" placeholder="7777 8888">
                            <small class="form-text text-muted">Recibe "Nueva orden #..., revisar App" al entrar cada orden.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="d-block">Cupones
                                <a href="#" data-toggle="modal" data-target="#modalAyudaCupones" title="¿Qué significa?"><i class="far fa-question-circle"></i></a>
                            </label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="cupon">
                                <label class="custom-control-label" for="cupon">Acepta cupones</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0">Horario</label>
                    <button type="button" class="btn btn-default btn-xs" onclick="copiarLunes()" title="Pone el horario del lunes en todos los días">
                        <i class="far fa-copy"></i> Copiar lunes a todos
                    </button>
                </div>
                <table class="table table-sm table-bordered mb-1" id="tabla-horario">
                    <thead class="thead-light">
                    <tr>
                        <th>Día</th>
                        <th style="width: 110px">Abre ese día</th>
                        <th>Abre</th>
                        <th>Cierra</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($dias as $numero => $nombre)
                        <tr data-dia="{{ $numero }}">
                            <td><b>{{ $nombre }}</b></td>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input abre" id="abre-{{ $numero }}" onchange="marcarDia(this)">
                                    <label class="custom-control-label" for="abre-{{ $numero }}"></label>
                                </div>
                            </td>
                            <td><input type="time" class="form-control form-control-sm hora1"></td>
                            <td><input type="time" class="form-control form-control-sm hora2"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <small class="text-muted">Solo horarios dentro del mismo día (la hora de cierre debe ser después de la de apertura).</small>
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
    @include('backend.admin.configuracion.zonas.parciales.cierres_js')

    <script>
        var restauranteCierres = null; // cierres de todos los restaurantes

        $(function () {
            recargar();

            // la explicacion se abre encima de "Editar Restaurante": al cerrarla, ese sigue con scroll
            $('#modalAyudaCupones').on('hidden.bs.modal', function () {
                if ($('.modal.show').length) { $('body').addClass('modal-open'); }
            }).on('shown.bs.modal', function () {
                $('.modal-backdrop').last().css('z-index', 1055);
            });
        });

        function recargar() {
            $('#tablaDatatable').load("{{ url('/admin/servicios/listado/tabla') }}");
        }

        // ---- horario ----

        function marcarDia(interruptor) {
            $(interruptor).closest('tr').toggleClass('dia-cerrado', !interruptor.checked);
        }

        function ponerDia(dia, abre, hora1, hora2) {
            var fila = $('#tabla-horario tr[data-dia="' + dia + '"]');
            fila.find('.abre').prop('checked', abre);
            fila.find('.hora1').val(hora1);
            fila.find('.hora2').val(hora2);
            marcarDia(fila.find('.abre')[0]);
        }

        function copiarLunes() {
            var lunes = $('#tabla-horario tr[data-dia="2"]');
            var abre = lunes.find('.abre').prop('checked'), h1 = lunes.find('.hora1').val(), h2 = lunes.find('.hora2').val();
            $('#tabla-horario tbody tr').each(function () { ponerDia($(this).data('dia'), abre, h1, h2); });
        }

        function leerHorario() {
            var dias = [], error = null;
            $('#tabla-horario tbody tr').each(function () {
                var fila = $(this), abre = fila.find('.abre').prop('checked');
                var h1 = fila.find('.hora1').val(), h2 = fila.find('.hora2').val();
                var nombre = fila.find('b').text();

                if (!h1 || !h2) { error = error || 'Completa las horas del ' + nombre; }
                else if (abre && h1 >= h2) { error = error || 'El ' + nombre + ' la hora de cierre debe ser después de la de apertura'; }

                dias.push({ dia: fila.data('dia'), cerrado: abre ? 0 : 1, hora1: h1, hora2: h2 });
            });
            return { dias: dias, error: error };
        }

        // ---- crear / editar ----

        function abrirNuevo() {
            $('#titulo-modal').text('Nuevo Restaurante');
            $('#id-restaurante').val('');
            $('#nombre').val('');
            $('#whatsapp').val('');
            $('#cupon').prop('checked', false);
            $('#tabla-horario tbody tr').each(function () { ponerDia($(this).data('dia'), true, '08:00', '21:00'); });
            $('#modalRestaurante').modal({ backdrop: 'static', keyboard: false });
        }

        function abrirEditar(id) {
            openLoading();
            axios.post('/admin/servicios/informacion', { id: id })
                .then(function (response) {
                    closeLoading();
                    if (response.data.success !== 1) { toastr.error('Restaurante no encontrado'); return; }

                    var s = response.data.servicio;
                    $('#titulo-modal').text('Editar Restaurante');
                    $('#id-restaurante').val(s.id);
                    $('#nombre').val(s.nombre);
                    $('#whatsapp').val(s.whatsapp ? '+' + s.whatsapp : '');
                    $('#cupon').prop('checked', s.utiliza_cupon == 1);

                    // dias sin registro quedan cerrados con un horario de ejemplo
                    $('#tabla-horario tbody tr').each(function () { ponerDia($(this).data('dia'), false, '08:00', '21:00'); });
                    $.each(response.data.horario, function (i, h) {
                        ponerDia(h.dia, h.cerrado != 1, (h.hora1 || '').substr(0, 5), (h.hora2 || '').substr(0, 5));
                    });

                    $('#modalRestaurante').modal({ backdrop: 'static', keyboard: false });
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        function guardar() {
            var id = $('#id-restaurante').val();
            var nombre = $('#nombre').val().trim();
            if (!nombre) { toastr.error('El nombre es requerido'); return; }

            var horario = leerHorario();
            if (horario.error) { toastr.error(horario.error); return; }

            var datos = { nombre: nombre, whatsapp: $('#whatsapp').val().trim(), cupon: $('#cupon').prop('checked') ? 1 : 0, dias: horario.dias };
            var ruta = '/admin/servicios/registrar/nuevo';
            if (id) { datos.id = id; ruta = '/admin/servicios/editar-servicio'; }

            openLoading();
            axios.post(ruta, datos)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success(id ? 'Restaurante actualizado' : 'Restaurante registrado');
                        $('#modalRestaurante').modal('hide');
                        recargar();
                    } else if (response.data.success === 0) {
                        toastr.error(response.data.mensaje || 'Revisa los datos');
                    } else {
                        toastr.error('Error al guardar');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }
    </script>
@stop
