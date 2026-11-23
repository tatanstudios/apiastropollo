@extends('adminlte::page')

@section('title', 'Órdenes')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <style>
        #pestanas .nav-link { cursor: pointer; }
        #tabla_processing { z-index: 5; }
        .producto-img { width: 42px; height: 42px; object-fit: cover; border-radius: .25rem; }
        .detalle-dato { font-size: .8rem; color: #6c757d; text-transform: uppercase; margin-bottom: 0; }
    </style>
@stop

@php
    $pestanas = [
        'pendientes' => ['Pendientes', 'fas fa-hourglass-half', 'bg-warning', 'El restaurante aún no las inicia'],
        'iniciadas' => ['Iniciadas', 'fas fa-fire', 'bg-info', 'Iniciadas por el restaurante el día elegido'],
        'canceladas' => ['Canceladas', 'fas fa-ban', 'bg-danger', 'Canceladas el día elegido'],
        'todas' => ['Todas', 'fas fa-list', 'bg-secondary', 'Historial completo'],
    ];
@endphp

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="mb-0">Órdenes</h1>
        <p class="text-muted mb-0">Las pendientes se actualizan solas cada minuto.</p>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="pestanas">
                    @foreach($pestanas as $clave => [$texto, $icono, $color, $ayuda])
                        <li class="nav-item">
                            <a class="nav-link {{ $pestana === $clave ? 'active' : '' }}" data-estado="{{ $clave }}" title="{{ $ayuda }}" onclick="cambiarPestana('{{ $clave }}')">
                                <i class="{{ $icono }} mr-1"></i> {{ $texto }}
                                <span class="badge {{ $color }} ml-1" id="conteo-{{ $clave }}">{{ number_format($resumen[$clave]) }}</span>
                                @if(in_array($clave, ['iniciadas', 'canceladas']))
                                    <small class="text-muted" id="dia-{{ $clave }}">hoy</small>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body">
                <div class="form-row mb-2">
                    <div class="col-sm-4 col-md-3 mb-2">
                        <select class="form-control form-control-sm" id="filtro-servicio" onchange="recargar()">
                            <option value="">Todos los restaurantes</option>
                            @foreach($servicios as $s)
                                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4 col-md-3 mb-2" id="caja-fecha" style="display: none">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text">Día</span></div>
                            <input type="date" class="form-control" id="filtro-fecha" value="{{ $hoy }}" max="{{ $hoy }}" onchange="cambiarDia()">
                        </div>
                    </div>
                </div>

                <table id="tabla" class="table table-bordered table-striped table-hover" style="width: 100%">
                    <thead>
                    <tr>
                        <th style="width: 70px">#</th>
                        <th style="width: 150px">Fecha</th>
                        <th>Restaurante</th>
                        <th>Cliente</th>
                        <th>Dirección</th>
                        <th style="width: 80px">Total</th>
                        <th style="width: 140px">Estado</th>
                        <th style="width: 150px">Opciones</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- detalle de la orden -->
<div class="modal fade" id="modalDetalle">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Orden #<span id="d-id"></span> <span id="d-estado"></span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="text-primary"><i class="fas fa-user mr-1"></i> Cliente</h6>
                        <div id="d-cliente"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="text-primary"><i class="fas fa-store mr-1"></i> Orden</h6>
                        <div id="d-orden"></div>
                    </div>
                </div>
                <h6 class="text-primary"><i class="fas fa-hamburger mr-1"></i> Productos</h6>
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                    <tr><th>Producto</th><th style="width: 70px">Cant.</th><th style="width: 90px">Precio</th><th style="width: 90px">Subtotal</th></tr>
                    </thead>
                    <tbody id="d-productos"></tbody>
                    <tfoot><tr><th colspan="3" class="text-right">Total</th><th id="d-total"></th></tr></tfoot>
                </table>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" id="d-borrar" style="display: none"><i class="fas fa-trash-alt"></i> Borrar orden</button>
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
    @include('backend.partials.datatable_es')

    <script>
        var estado = @json($pestana);
        var hoy = @json($hoy);
        var tabla;

        function esc(texto) {
            return $('<div>').text(texto == null ? '' : String(texto)).html();
        }

        var ESTADOS = {
            pendiente: '<span class="badge bg-warning">Pendiente</span>',
            iniciada: '<span class="badge bg-info">Iniciada</span>',
            cancelada: '<span class="badge bg-danger">Cancelada</span>'
        };

        $(function () {
            tabla = iniciarTabla('#tabla', {
                "processing": true,
                "serverSide": true,
                "searchDelay": 400,
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "order": [[0, 'desc']],
                "ajax": {
                    "url": "{{ url('/admin/ordenes/tabla') }}",
                    "data": function (d) {
                        d.estado = estado;
                        d.servicio = $('#filtro-servicio').val();
                        d.fecha = $('#filtro-fecha').val();
                    },
                    "error": function () { toastr.error('No se pudieron cargar las órdenes'); }
                },
                "columns": [
                    { "data": "id", "render": function (v) { return '<b>' + v + '</b>'; } },
                    { "data": "fecha", "render": function (v, t, o) {
                        return esc(v) + '<div class="small text-muted">' + esc(o.hace) + '</div>';
                    } },
                    { "data": "restaurante", "render": esc },
                    { "data": "cliente", "render": function (v, t, o) {
                        return esc(v) + (o.telefono ? '<div class="small text-muted"><i class="fas fa-phone-alt"></i> ' + esc(o.telefono) + '</div>' : '');
                    } },
                    { "data": "direccion", "render": esc },
                    { "data": "total", "render": function (v, t, o) {
                        return esc(v) + (o.cupon ? '<div><span class="badge bg-success">cupón</span></div>' : '');
                    } },
                    { "data": "estado", "render": function (v, t, o) {
                        return ESTADOS[v] + (v === 'cancelada' ? '<div class="small text-muted">Por ' + esc(o.cancelado_por) + '</div>' : '');
                    } },
                    { "data": "id", "orderable": false, "render": function (v, t, o) {
                        var html = '<button type="button" class="btn btn-primary btn-xs mr-1" onclick="verDetalle(' + v + ')"><i class="fas fa-eye"></i>&nbsp; Detalle</button>';
                        if (o.estado === 'pendiente') {
                            html += '<button type="button" class="btn btn-danger btn-xs" onclick="borrarOrden(' + v + ')"><i class="fas fa-trash-alt"></i>&nbsp; Borrar</button>';
                        }
                        return html;
                    } }
                ]
            });
            cambiarPestana(estado, true);

            // las pendientes llegan solas: refrescar cada minuto sin perder la pagina
            setInterval(function () {
                if (estado === 'pendientes' && !$('.modal.show').length) {
                    tabla.ajax.reload(null, false);
                    actualizarConteo();
                }
            }, 60000);
        });

        function cambiarPestana(nuevo, inicial) {
            estado = nuevo;
            $('#pestanas .nav-link').removeClass('active');
            $('#pestanas .nav-link[data-estado="' + nuevo + '"]').addClass('active');
            $('#caja-fecha').toggle(nuevo === 'iniciadas' || nuevo === 'canceladas');
            history.replaceState(null, '', '?ver=' + nuevo);
            if (!inicial) { tabla.ajax.reload(); }
        }

        function cambiarDia() {
            var f = $('#filtro-fecha').val() || hoy;
            var texto = f === hoy ? 'hoy' : f.split('-').reverse().join('/');
            $('#dia-iniciadas, #dia-canceladas').text(texto);
            recargar();
        }

        function recargar() { tabla.ajax.reload(); }

        // el numero de la pestaña de pendientes sale del total de la tabla
        function actualizarConteo() {
            tabla.one('xhr', function (e, s, json) {
                if (json && estado === 'pendientes' && !$('#filtro-servicio').val() && !tabla.search()) {
                    $('#conteo-pendientes').text(json.recordsTotal.toLocaleString('en-US'));
                }
            });
        }

        function fila(etiqueta, valor) {
            if (valor === null || valor === undefined || valor === '') return '';
            return '<p class="detalle-dato">' + etiqueta + '</p><p class="mb-2">' + valor + '</p>';
        }

        function verDetalle(id) {
            openLoading();
            axios.post('/admin/ordenes/detalle', { id: id })
                .then(function (response) {
                    closeLoading();
                    var r = response.data;
                    if (r.success !== 1) { toastr.error('Orden no encontrada'); recargar(); return; }

                    var o = r.orden, c = r.cliente;
                    $('#d-id').text(o.id);
                    $('#d-estado').html(ESTADOS[o.estado]);

                    var cliente = '<span class="text-muted">Sin datos de entrega</span>';
                    if (c) {
                        var mapa = c.lat && c.lng
                            ? '<a href="https://www.google.com/maps?q=' + encodeURIComponent(c.lat + ',' + c.lng) + '" target="_blank" rel="noopener" class="btn btn-default btn-xs"><i class="fas fa-map-marker-alt text-danger"></i> Ver en el mapa</a>'
                            : '';
                        cliente = fila('Nombre', esc(c.nombre)) + fila('Teléfono', esc(c.telefono))
                            + fila('Dirección', esc(c.direccion)) + fila('Referencia', esc(c.referencia))
                            + fila('Versión de la app', esc(c.app)) + mapa;
                    }
                    $('#d-cliente').html(cliente);

                    $('#d-orden').html(
                        fila('Restaurante', esc(o.restaurante))
                        // iniciada: solo lo guardado en la orden; pendiente: el tiempo que se aplicara al iniciarla
                        + fila('Zona', esc(o.zona) + (o.estado === 'pendiente' && o.zona_minutos !== null
                            ? ' <span class="text-muted">(se sumarán ' + o.zona_minutos + ' min cuando el restaurante la inicie)</span>' : ''))
                        + fila('Fecha', esc(o.fecha)) + fila('Nota del cliente', esc(o.nota))
                        + fila('Cupón', o.cupon ? esc(o.cupon) + (o.mensaje_cupon ? ' · ' + esc(o.mensaje_cupon) : '') : '')
                        + fila('Iniciada', esc(o.fecha_iniciada))
                        + fila('Entrega estimada', o.fecha_estimada ? esc(o.fecha_estimada)
                            + (o.minutos_usados !== null ? ' <span class="text-muted">(+' + o.minutos_usados + ' min de la zona, guardado al iniciar)</span>' : '') : '')
                        + fila('Cancelada', o.fecha_cancelada ? esc(o.fecha_cancelada) + ' por ' + esc(o.cancelado_por) : '')
                        + fila('Motivo de cancelación', esc(o.nota_cancelada))
                    );

                    $('#d-productos').html(r.productos.length ? r.productos.map(function (p) {
                        return '<tr><td>'
                            + (p.imagen ? '<img class="producto-img mr-2" src="' + esc(p.imagen) + '" alt="">' : '')
                            + esc(p.nombre)
                            + (p.opciones || []).map(function (t) { return '<div class="small text-primary">' + esc(t) + '</div>'; }).join('')
                            + (p.nota ? '<div class="small text-muted">Nota: ' + esc(p.nota) + '</div>' : '')
                            + '</td><td>' + p.cantidad + '</td><td>' + esc(p.precio) + '</td><td>' + esc(p.subtotal) + '</td></tr>';
                    }).join('') : '<tr><td colspan="4" class="text-muted">Sin productos</td></tr>');
                    $('#d-total').text(o.total);

                    $('#d-borrar').toggle(o.estado === 'pendiente').off('click').on('click', function () { borrarOrden(o.id); });
                    $('#modalDetalle').modal('show');
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        function borrarOrden(id) {
            Swal.fire({
                titleText: '¿Borrar la orden #' + id + '?',
                text: 'Se borra por completo con sus productos y dirección. Si usó cupón, se devuelve su uso. No se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, borrar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                openLoading();
                axios.post('/admin/ordenes/borrar', { id: id })
                    .then(function (response) {
                        closeLoading();
                        var r = response.data;
                        if (r.success === 1) {
                            toastr.success('Orden #' + id + ' borrada');
                            $('#modalDetalle').modal('hide');
                            var conteo = $('#conteo-pendientes'), n = parseInt(conteo.text().replace(/,/g, ''), 10);
                            if (n > 0) conteo.text((n - 1).toLocaleString('en-US'));
                        } else {
                            toastr.error(r.mensaje || 'No se pudo borrar');
                        }
                        tabla.ajax.reload(null, false);
                    })
                    .catch(function () {
                        closeLoading();
                        toastr.error('Error del servidor');
                    });
            });
        }
    </script>
@stop
