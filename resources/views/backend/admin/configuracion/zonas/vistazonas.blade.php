@extends('adminlte::page')

@section('title', 'Zonas')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" rel="stylesheet" />
    <style>
        .filtro-zona { cursor: pointer; border: 2px solid transparent; }
        .filtro-zona:hover { border-color: #ced4da; }
        .filtro-zona.activo { border-color: #007bff; }
        #mapa-general { height: calc(100vh - 260px); min-height: 420px; border-radius: .25rem; }
        .etiqueta-zona {
            background: rgba(255, 255, 255, .85);
            border: 1px solid #adb5bd;
            box-shadow: none;
            font-size: .75rem;
            font-weight: bold;
            padding: 1px 5px;
        }
        .etiqueta-zona::before { display: none; }
        .leyenda-mapa span { display: inline-block; width: 12px; height: 12px; border-radius: 2px; margin: 0 4px 0 12px; vertical-align: middle; }
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
                    @if(!$restaurante)
                        <h1 class="mb-0">Todas las zonas</h1>
                        <p class="text-muted mb-0">Zonas de todos los restaurantes. Una zona aparece en la app si está visible y tiene polígono.</p>
                    @elseif($restaurante->id === 'sin')
                        <h1 class="mb-0">Zonas sin restaurante</h1>
                        <p class="text-muted mb-0">Asigna el restaurante con Editar; así pasan a la lista de ese restaurante.</p>
                    @else
                        <h1 class="mb-0">Zonas de {{ $restaurante->nombre }}</h1>
                        <p class="text-muted mb-0">Una zona aparece en la app si está visible y tiene polígono.</p>
                    @endif
                </div>
            </div>
            <div class="mt-2">
                @if(!$restaurante)
                    <button type="button" onclick="cerrarTodo()" class="btn btn-danger btn-sm">
                        <i class="fas fa-store-slash"></i> Cerrar todo
                    </button>
                    <button type="button" onclick="abrirTodo()" class="btn btn-default btn-sm">
                        <i class="fas fa-store"></i> Abrir todo
                    </button>
                @elseif($restaurante->id !== 'sin')
                    <button type="button" onclick="cerrarRestaurante({{ $restaurante->id }}, @js($restaurante->nombre))" class="btn btn-danger btn-sm">
                        <i class="fas fa-store-slash"></i> Cerrar restaurante
                    </button>
                    <button type="button" onclick="abrirRestaurante({{ $restaurante->id }}, @js($restaurante->nombre))" class="btn btn-default btn-sm">
                        <i class="fas fa-store"></i> Abrir restaurante
                    </button>
                @endif
                <button type="button" onclick="abrirModalCierres()" class="btn btn-default btn-sm">
                    <i class="far fa-calendar-alt"></i> Cierres programados
                </button>
                <button type="button" onclick="abrirModalAgregar()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nueva Zona
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#tab-lista" role="tab">
                            <i class="fas fa-list"></i> Lista
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab-mapa" role="tab" id="link-mapa">
                            <i class="fas fa-map-marked-alt"></i> Mapa
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-lista" role="tabpanel">
                        <div id="tablaDatatable"></div>
                    </div>
                    <div class="tab-pane fade" id="tab-mapa" role="tabpanel">
                        <div class="small text-muted mb-2 leyenda-mapa">
                            Clic en una zona para editar su polígono.
                            <span style="background:#28a745"></span>Visible
                            <span style="background:#dc3545"></span>Cerrada
                            <span style="background:#6c757d"></span>Oculta
                            @if($restaurante)
                                <span style="background:#adb5bd; opacity:.5"></span>Otros restaurantes
                            @endif
                        </div>
                        <div id="mapa-general"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('backend.admin.configuracion.zonas.parciales.cierres_modal')

<!-- modal crear / editar zona -->
<div class="modal fade" id="modalZona">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="titulo-modal">Nueva Zona</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-zona" onsubmit="return false;" autocomplete="off">
                    <input type="hidden" id="id-zona">

                    <div class="form-group">
                        <label>Restaurante que atiende la zona</label>
                        <select class="form-control" id="servicio">
                            <option value="">Seleccionar...</option>
                            @foreach($servicios as $s)
                                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted" id="ayuda-servicio">Las órdenes de esta zona llegan a este restaurante.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nombre</label>
                            <input type="text" maxlength="50" class="form-control" id="nombre" placeholder="Nombre de la zona">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Descripción</label>
                            <input type="text" maxlength="300" class="form-control" id="descripcion" placeholder="Descripción de la zona">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Abre</label>
                            <input type="time" class="form-control" id="horaabierto">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Cierra</label>
                            <input type="time" class="form-control" id="horacerrado">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Tiempo extra (min)</label>
                            <input type="number" min="0" max="500" step="1" class="form-control" id="tiempoextra" value="0">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Mínimo de compra ($)</label>
                            <input type="number" min="0" step="0.01" class="form-control" id="minimo" placeholder="0.00">
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block mb-2" style="margin-top: -8px">
                                El horario es del mismo día (la hora de cierre debe ser después de la de apertura).
                                El tiempo extra se suma al tiempo de entrega de cada orden de esta zona.
                            </small>
                        </div>
                    </div>

                    <div id="seccion-estado" style="display: none">
                        <hr>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-activo">
                                    <label class="custom-control-label" for="toggle-activo"><b>Visible en la app</b></label>
                                </div>
                                <small class="text-muted">Si está oculta, los clientes no pueden elegirla para una dirección.</small>
                                <div id="aviso-poligono" class="small text-danger" style="display: none">
                                    Esta zona no tiene polígono: dibújalo antes de hacerla visible.
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <div class="custom-control custom-switch custom-switch-on-danger">
                                    <input type="checkbox" class="custom-control-input" id="toggle-cerrada" onchange="cambioCerrada()">
                                    <label class="custom-control-label" for="toggle-cerrada"><b>Cerrar temporalmente</b></label>
                                </div>
                                <small class="text-muted">La zona sigue visible pero no recibe órdenes.</small>
                            </div>
                        </div>

                        <div class="form-group" id="grupo-mensaje" style="display: none">
                            <label>Mensaje de cierre (se muestra al cliente)</label>
                            <input type="text" maxlength="100" class="form-control" id="mensaje" placeholder="Ej.: Cerrado por mantenimiento de motocicletas">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="guardar()">Guardar</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    @include('backend.admin.configuracion.zonas.parciales.cierres_js')

    <script>
        var filtroZonas = 'todas';
        // null = todas las zonas; un id = ese restaurante; 'sin' = zonas sin restaurante
        var restauranteActual = @json($restaurante ? $restaurante->id : null);
        var restauranteCierres = typeof restauranteActual === 'number' ? restauranteActual : null;
        var mapaGeneral = null, capaZonas = null;


        // filtro de las tarjetas de resumen (cada fila trae data-estados="todas visible cerrada ...")
        $.fn.dataTable.ext.search.push(function (settings, data, indice) {
            if (settings.nTable.id !== 'tabla' || filtroZonas === 'todas') return true;
            var fila = settings.aoData[indice].nTr;
            return (' ' + fila.getAttribute('data-estados') + ' ').indexOf(' ' + filtroZonas + ' ') !== -1;
        });

        $(function(){
            recargar();

            $('#link-mapa').on('shown.bs.tab', function () { cargarMapaGeneral(); });
        });

        function recargar(){
            $('#tablaDatatable').load("{{ url('/admin/zonas/tablas/zona') }}" + (restauranteActual ? '?servicio=' + restauranteActual : ''));
            if (mapaGeneral) { cargarMapaGeneral(); }
        }

        function filtrarZonas(filtro){
            filtroZonas = filtro;
            $('.filtro-zona').removeClass('activo');
            $('.filtro-zona[data-filtro="' + filtro + '"]').addClass('activo');
            if ($.fn.dataTable.isDataTable('#tabla')) { $('#tabla').DataTable().draw(); }
        }

        function vistaPoligonos(id){
            window.location.href = "{{ url('/admin/zonas/poligono') }}/" + id;
        }

        // ---- cambios rapidos desde la tabla ----

        function enviarEstado(id, campo, valor, mensaje, interruptor){
            openLoading();
            axios.post('/admin/zonas/estado', { id: id, campo: campo, valor: valor, mensaje: mensaje || '' })
                .then(function (response) {
                    closeLoading();
                    var r = response.data;
                    if (r.success === 1) {
                        toastr.success('Zona actualizada');
                        recargar();
                        return;
                    }
                    interruptor.checked = !interruptor.checked;
                    if (r.success === 2) {
                        alertaMensaje('info', 'Sin polígono', 'Dibuja el polígono de la zona (mínimo 3 puntos) antes de hacerla visible.');
                    } else if (r.success === 4) {
                        alertaMensaje('info', 'Sin restaurante', 'Asigna el restaurante de la zona (botón Editar) antes de hacerla visible.');
                    } else if (r.success === 3) {
                        toastr.error('Escribe el mensaje de cierre');
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(function () {
                    closeLoading();
                    interruptor.checked = !interruptor.checked;
                    toastr.error('Error del servidor');
                });
        }

        function cambiarVisible(id, interruptor){
            enviarEstado(id, 'activo', interruptor.checked ? 1 : 0, '', interruptor);
        }

        function cambiarCerrada(id, interruptor){
            if (!interruptor.checked) {
                enviarEstado(id, 'saturacion', 0, '', interruptor);
                return;
            }

            // para cerrar se pide el mensaje que vera el cliente
            Swal.fire({
                title: 'Cerrar zona temporalmente',
                text: 'Mensaje que verá el cliente:',
                input: 'text',
                inputPlaceholder: 'Ej.: Cerrado por mantenimiento de motocicletas',
                inputAttributes: { maxlength: 100 },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Cerrar zona',
                cancelButtonText: 'Cancelar',
                inputValidator: function (valor) {
                    if (!valor || !valor.trim()) return 'Escribe el mensaje';
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    enviarEstado(id, 'saturacion', 1, result.value.trim(), interruptor);
                } else {
                    interruptor.checked = false;
                }
            });
        }

        // ---- borrar zona (solo si nunca tuvo ordenes ni direcciones) ----

        function preguntaBorrarZona(id, boton){
            var nombre = $(boton).data('nombre');
            Swal.fire({
                title: '¿Borrar la zona?',
                html: $('<div>').append($('<b>').text(nombre))
                    .append('<br>Se borran también su polígono y su restaurante asignado. No se puede deshacer.').html(),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, borrar'
            }).then(function (result) {
                if (result.isConfirmed) { borrarZona(id); }
            });
        }

        function borrarZona(id){
            openLoading();
            axios.post('/admin/zonas/borrar', { id: id })
                .then(function (response) {
                    closeLoading();
                    var r = response.data;
                    if (r.success === 1) {
                        toastr.success('Zona borrada');
                        recargar();
                    } else if (r.success === 2) {
                        alertaMensaje('info', 'No se puede borrar',
                            'La zona tiene ' + r.ordenes + ' órdenes y ' + r.direcciones + ' direcciones de clientes. Puedes ocultarla en su lugar.');
                        recargar();
                    } else {
                        toastr.error('Error al borrar');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        // ---- mapa de todas las zonas ----

        function cargarMapaGeneral(){
            if (!mapaGeneral) {
                mapaGeneral = L.map('mapa-general').setView([13.7, -89.2], 9);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapaGeneral);
                L.control.scale({ imperial: false }).addTo(mapaGeneral);
                capaZonas = L.featureGroup().addTo(mapaGeneral);
            }
            mapaGeneral.invalidateSize();

            axios.get('/admin/zonas/mapa-general')
                .then(function (response) {
                    capaZonas.clearLayers();
                    var sinPoligono = [];

                    var propias = L.featureGroup();
                    response.data.zonas.forEach(function (z) {
                        var esPropia = !restauranteActual
                            || (restauranteActual === 'sin' ? z.id_servicio === null : z.id_servicio === restauranteActual);

                        if (!esPropia) {
                            z.areas.forEach(function (puntos) {
                                L.polygon(puntos, { color: '#adb5bd', weight: 1, fillOpacity: 0.12 })
                                    .bindTooltip($('<div>').text(z.nombre + ' — ' + z.restaurante).html())
                                    .addTo(capaZonas);
                            });
                            return;
                        }
                        if (!z.areas.length) { sinPoligono.push(z.nombre); return; }

                        // una zona puede tener varias areas: todas con el mismo color y nombre
                        var color = z.activo !== 1 ? '#6c757d' : (z.saturacion === 1 ? '#dc3545' : '#28a745');
                        z.areas.forEach(function (puntos) {
                            L.polygon(puntos, { color: color, weight: 2, fillOpacity: 0.25, dashArray: z.activo !== 1 ? '4' : null })
                                .bindTooltip($('<div>').append($('<div>').text(z.nombre + ' #' + z.id))
                                    .append($('<div class="font-weight-normal">').text(z.restaurante)).html(),
                                    { permanent: true, direction: 'center', className: 'etiqueta-zona' })
                                .on('click', function () { vistaPoligonos(z.id); })
                                .addTo(capaZonas).addTo(propias);
                        });
                    });

                    var encuadre = propias.getLayers().length ? propias : capaZonas;
                    if (encuadre.getLayers().length) {
                        mapaGeneral.fitBounds(encuadre.getBounds(), { padding: [20, 20] });
                    }
                    if (sinPoligono.length) {
                        toastr.warning('Sin polígono: ' + $('<span>').text(sinPoligono.join(', ')).html());
                    }
                })
                .catch(function () { toastr.error('No se pudo cargar el mapa'); });
        }

        // ---- crear / editar ----

        function cambioCerrada(){
            $('#grupo-mensaje').toggle(document.getElementById('toggle-cerrada').checked);
        }

        function abrirModalAgregar(){
            document.getElementById("formulario-zona").reset();
            $('#id-zona').val('');
            $('#titulo-modal').text('Nueva Zona');
            $('#seccion-estado').hide();
            $('#servicio').val(restauranteCierres || '').prop('disabled', !!restauranteCierres);
            $('#ayuda-servicio').text('Las órdenes de esta zona llegan a este restaurante.');
            $('#modalZona').modal('show');
        }

        function verInformacion(id){
            document.getElementById("formulario-zona").reset();
            openLoading();

            axios.post('/admin/zonas/informacion-zona', { 'id': id })
                .then((response) => {
                    closeLoading();
                    if(response.data.success !== 1){
                        toastr.error('Zona no encontrada');
                        return;
                    }

                    var z = response.data.zona;
                    $('#id-zona').val(z.id);
                    $('#titulo-modal').text('Editar Zona');
                    $('#nombre').val(z.nombre);
                    $('#descripcion').val(z.descripcion);
                    $('#horaabierto').val(z.hora_abierto_delivery);
                    $('#horacerrado').val(z.hora_cerrado_delivery);
                    $('#tiempoextra').val(z.tiempo_extra);
                    $('#minimo').val(z.minimo);
                    $('#mensaje').val(z.mensaje_bloqueo);

                    // el restaurante solo se elige una vez: cambiarlo afectaria carritos y direcciones de clientes
                    $('#servicio').val(z.id_servicio || '').prop('disabled', !!z.id_servicio);
                    $('#ayuda-servicio').text(z.id_servicio
                        ? 'Ya asignado: no se puede cambiar.'
                        : 'Esta zona no tiene restaurante: asígnalo para poder hacerla visible.');

                    $('#toggle-activo').prop('checked', Number(z.activo) === 1);
                    $('#toggle-cerrada').prop('checked', Number(z.saturacion) === 1);
                    $('#aviso-poligono').toggle(Number(z.puntos) < 3);
                    cambioCerrada();

                    $('#seccion-estado').show();
                    $('#modalZona').modal('show');
                })
                .catch(() => {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        function valor(id){
            return document.getElementById(id).value.trim();
        }

        function guardar(){
            var id = valor('id-zona');
            var datos = {
                nombre: valor('nombre'),
                descripcion: valor('descripcion'),
                horaabierto: valor('horaabierto'),
                horacerrado: valor('horacerrado'),
                tiempoextra: valor('tiempoextra'),
                minimo: valor('minimo'),
            };
            if(id === '' || !document.getElementById('servicio').disabled){
                datos.servicio = valor('servicio');
            }

            if(id === '' && !datos.servicio){ toastr.error('Selecciona el restaurante'); return; }
            if(datos.nombre === ''){ toastr.error('Nombre es requerido'); return; }
            if(datos.descripcion === ''){ toastr.error('Descripción es requerida'); return; }
            if(datos.horaabierto === ''){ toastr.error('Hora de apertura es requerida'); return; }
            if(datos.horacerrado === ''){ toastr.error('Hora de cierre es requerida'); return; }
            if(datos.horacerrado.substring(0, 5) <= datos.horaabierto.substring(0, 5)){
                toastr.error('La hora de cierre debe ser después de la de apertura');
                return;
            }

            if(!/^\d+$/.test(datos.tiempoextra) || Number(datos.tiempoextra) > 500){
                toastr.error('Tiempo extra debe ser un número entero entre 0 y 500');
                return;
            }

            if(!/^\d+(\.\d{1,2})?$/.test(datos.minimo) || Number(datos.minimo) > 1000000){
                toastr.error('Mínimo de compra debe ser un número con máximo 2 decimales');
                return;
            }

            var ruta = '/admin/zonas/registro/nueva';

            if(id !== ''){
                ruta = '/admin/zonas/editar-zona';
                datos.id = id;
                datos.togglea = document.getElementById('toggle-activo').checked ? 1 : 0;
                datos.togglep = document.getElementById('toggle-cerrada').checked ? 1 : 0;
                datos.mensaje = valor('mensaje');

                if(datos.togglep === 1 && datos.mensaje === ''){
                    toastr.error('Escribe el mensaje de cierre para el cliente');
                    return;
                }
            }

            var formData = new FormData();
            Object.keys(datos).forEach(function(k){ formData.append(k, datos[k]); });

            openLoading();
            axios.post(ruta, formData)
                .then((response) => {
                    closeLoading();
                    var r = response.data;

                    if(r.success === 1){
                        $('#modalZona').modal('hide');
                        recargar();

                        if(id === ''){
                            // zona nueva: ofrecer dibujar su poligono
                            Swal.fire({
                                title: 'Zona creada',
                                text: 'Se creó oculta. ¿Dibujar su polígono ahora?',
                                icon: 'success',
                                showCancelButton: true,
                                confirmButtonColor: '#28a745',
                                cancelButtonColor: '#6c757d',
                                cancelButtonText: 'Después',
                                confirmButtonText: 'Dibujar polígono'
                            }).then((result) => {
                                if(result.isConfirmed){ vistaPoligonos(r.id); }
                            });
                        }else{
                            toastr.success('Zona actualizada');
                        }
                    }
                    else if(r.success === 2 && id !== ''){
                        recargar();
                        alertaMensaje('info', 'Sin polígono', 'No se puede hacer visible la zona porque no tiene un polígono (mínimo 3 puntos).');
                    }
                    else if(r.success === 4 && id !== ''){
                        recargar();
                        alertaMensaje('info', 'Sin restaurante', 'No se puede hacer visible la zona porque no tiene restaurante asignado.');
                    }
                    else if(r.success === 0){
                        toastr.error(r.mensaje || 'Datos no válidos');
                    }
                    else{
                        toastr.error('Error al guardar');
                    }
                })
                .catch(() => {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }
    </script>
@stop
