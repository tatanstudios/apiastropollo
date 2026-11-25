@extends('adminlte::page')

@section('title', 'WhatsApp')

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
                <h1><i class="fab fa-whatsapp text-success"></i> WhatsApp</h1>
            </div>
            <br>

            @unless($configurado)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    WhatsApp no está configurado en el servidor (token y número emisor en el .env). No se enviarán mensajes aunque haya saldo.
                </div>
            @endunless

            <button type="button" class="btn btn-success btn-sm" onclick="modalAjuste('agregar')">
                <i class="fas fa-plus"></i>
                Agregar mensajes
            </button>

            <button type="button" class="btn btn-danger btn-sm" onclick="modalAjuste('quitar')">
                <i class="fas fa-minus"></i>
                Quitar mensajes
            </button>

            {{-- TEMPORAL: solo mientras se arranca en produccion --}}
            <button type="button" class="btn btn-outline-danger btn-sm ml-3" onclick="modalBorrarVentas()">
                <i class="fas fa-trash-alt"></i>
                Borrar Ventas
            </button>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-success elevation-1"><i class="fab fa-whatsapp"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Mensajes disponibles</span>
                            <span class="info-box-number" style="font-size: 1.6rem" id="txt-disponibles">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-paper-plane"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Enviados hoy</span>
                            <span class="info-box-number" style="font-size: 1.6rem" id="txt-enviados-hoy">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-history"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Enviados en total</span>
                            <span class="info-box-number" style="font-size: 1.6rem" id="txt-enviados">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted">
                Cada orden nueva de un restaurante con número registrado gasta 1 mensaje.
                Con saldo en 0 no se envía nada. Si el envío falla, el mensaje no se descuenta.
                El número se edita en <a href="{{ route('index.servicios.listado') }}">Restaurantes</a>.
            </p>

            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Números registrados por restaurante</h3>
                </div>
                <div class="card-body">
                    <div id="tablaNumeros"></div>
                </div>
            </div>

            <div class="card card-success card-outline card-tabs">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" data-ver="ajustes" onclick="verHistorial(this); return false;">
                                <i class="fas fa-user-edit"></i> Historial de ajustes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-ver="envios" onclick="verHistorial(this); return false;">
                                <i class="fas fa-paper-plane"></i> Mensajes enviados
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div id="tablaHistorial"></div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalAjuste">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="titulo-ajuste">Agregar mensajes</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formulario-ajuste" onsubmit="return false;">
                        <input type="hidden" id="tipo-ajuste">
                        <p class="mb-2">Disponibles ahora: <b id="txt-disponibles-modal">0</b></p>
                        <div class="form-group">
                            <label>Cantidad</label>
                            <input type="number" min="1" step="1" class="form-control" id="cantidad-ajuste" autocomplete="off" placeholder="Ej. 500">
                        </div>
                        <div class="form-group">
                            <label>Nota <small class="text-muted">(opcional)</small></label>
                            <input type="text" maxlength="150" class="form-control" id="nota-ajuste" autocomplete="off" placeholder="Ej. Recarga de noviembre">
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="boton-ajuste" onclick="guardarAjuste()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- TEMPORAL: borrar ventas de prueba --}}
    <div class="modal fade" id="modalBorrarVentas">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h4 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Borrar Ventas</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Se borrarán <b>todas las órdenes</b> de todos los restaurantes, con sus productos, opciones y direcciones.
                        La numeración de órdenes vuelve a empezar en <b>#1</b> y el uso de los cupones queda en 0.</p>
                    <p class="text-danger mb-2"><b>No se puede deshacer.</b></p>
                    <p class="mb-2">El saldo y el historial de WhatsApp no se tocan.</p>
                    <div class="form-group mb-0">
                        <label>Escribe <b>BORRAR</b> para confirmar</label>
                        <input type="text" class="form-control" id="confirmar-borrar" autocomplete="off" placeholder="BORRAR">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="borrarVentas()">Borrar todo</button>
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
            recargarTodo();
            document.getElementById("divcontenedor").style.display = "block";
        });
    </script>

    <script>
        var disponibles = 0;
        var historialVer = 'ajustes';

        function recargarTodo(){
            cargarResumen();
            $('#tablaNumeros').load("{{ url('/admin/whatsapp/numeros/tabla') }}");
            cargarHistorial();
        }

        function cargarResumen(){
            axios.get("{{ url('/admin/whatsapp/resumen') }}")
                .then((response) => {
                    if(response.data.success === 1){
                        disponibles = response.data.disponibles;
                        $('#txt-disponibles').text(disponibles.toLocaleString());
                        $('#txt-enviados').text(response.data.enviados.toLocaleString());
                        $('#txt-enviados-hoy').text(response.data.enviados_hoy.toLocaleString());
                    }
                });
        }

        function cargarHistorial(){
            $('#tablaHistorial').load("{{ url('/admin/whatsapp/historial/tabla') }}?ver=" + historialVer);
        }

        function verHistorial(el){
            $(el).closest('.nav-tabs').find('.nav-link').removeClass('active');
            $(el).addClass('active');
            historialVer = $(el).data('ver');
            cargarHistorial();
        }

        function modalAjuste(tipo){
            document.getElementById('formulario-ajuste').reset();
            $('#tipo-ajuste').val(tipo);
            $('#txt-disponibles-modal').text(disponibles.toLocaleString());

            if(tipo === 'agregar'){
                $('#titulo-ajuste').text('Agregar mensajes');
                $('#boton-ajuste').removeClass('btn-danger').addClass('btn-success').text('Agregar');
            }else{
                $('#titulo-ajuste').text('Quitar mensajes');
                $('#boton-ajuste').removeClass('btn-success').addClass('btn-danger').text('Quitar');
            }

            $('#modalAjuste').modal('show');
            setTimeout(function(){ $('#cantidad-ajuste').focus(); }, 400);
        }

        function guardarAjuste(){
            var tipo = $('#tipo-ajuste').val();
            var cantidad = $('#cantidad-ajuste').val().trim();
            var nota = $('#nota-ajuste').val().trim();

            if(!/^\d+$/.test(cantidad) || parseInt(cantidad) < 1){
                toastr.error('Escribe una cantidad entera mayor a 0');
                return;
            }

            if(tipo === 'quitar' && parseInt(cantidad) > disponibles){
                toastr.error('Solo hay ' + disponibles + ' mensajes disponibles');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('tipo', tipo);
            formData.append('cantidad', cantidad);
            formData.append('nota', nota);

            axios.post("{{ url('/admin/whatsapp/ajustar') }}", formData)
                .then((response) => {
                    closeLoading();

                    if(response.data.success === 2){
                        toastr.success(tipo === 'agregar' ? 'Mensajes agregados' : 'Mensajes quitados');
                        $('#modalAjuste').modal('hide');
                        recargarTodo();
                    }
                    else if(response.data.success === 1){
                        toastr.error('Solo hay ' + response.data.disponibles + ' mensajes disponibles');
                        cargarResumen();
                    }
                    else{
                        toastr.error('Datos no válidos');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al guardar');
                });
        }

        // TEMPORAL: borrar ventas de prueba
        function modalBorrarVentas(){
            $('#confirmar-borrar').val('');
            $('#modalBorrarVentas').modal('show');
            setTimeout(function(){ $('#confirmar-borrar').focus(); }, 400);
        }

        function borrarVentas(){
            var confirmar = $('#confirmar-borrar').val().trim();
            if(confirmar !== 'BORRAR'){
                toastr.error('Escribe BORRAR para confirmar');
                return;
            }

            openLoading();
            var formData = new FormData();
            formData.append('confirmar', confirmar);

            axios.post("{{ url('/admin/ventas/borrar') }}", formData)
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        toastr.success('Se borraron ' + response.data.borradas + ' órdenes');
                        $('#modalBorrarVentas').modal('hide');
                        recargarTodo();
                    }else{
                        toastr.error('No se borró nada');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al borrar');
                });
        }
    </script>
@stop
