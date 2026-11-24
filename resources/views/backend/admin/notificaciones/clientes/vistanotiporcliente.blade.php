@extends('adminlte::page')

@section('title', 'Direcciones de Cliente')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/estiloToggle.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="col-sm-12">
            <h1>Direcciones de Cliente</h1>
        </div>

    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-warning card-outline">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center">
                    <strong class="mr-3"><i class="fas fa-vial mr-1"></i> Notificación de prueba</strong>
                    <div class="input-group input-group-sm mr-2" style="width: 210px;">
                        <div class="input-group-prepend"><span class="input-group-text">cliente_</span></div>
                        <input type="number" min="1" class="form-control" id="id-prueba" value="12">
                    </div>
                    <button type="button" class="btn btn-warning btn-sm" onclick="enviarPrueba()">
                        <i class="fas fa-paper-plane"></i> Enviar prueba
                    </button>
                    <small class="text-muted ml-3">Envía directo al External ID de OneSignal, a todos sus teléfonos.</small>
                </div>
            </div>
        </div>

        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Direcciones</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="tablaDatatable">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<div class="modal fade" id="modalEditar">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Envío Notificación</h4>
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
                                    <label>Teléfono </label>
                                    <input type="hidden" id="id-editar">
                                    <input type="text" autocomplete="off" disabled class="form-control" id="telefono">
                                </div>

                                <div class="form-group">
                                    <label>Título (máximo 100 caracteres)</label>
                                    <input type="text" maxlength="100" autocomplete="off" class="form-control" id="titulo" placeholder="Título">
                                </div>

                                <div class="form-group">
                                    <label>Mensaje (máximo 125 caracteres)</label>
                                    <input type="text" maxlength="125" autocomplete="off" class="form-control" id="mensaje" placeholder="Mensaje">
                                </div>


                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="preguntaEnviar()">Enviar</button>
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

    <script type="text/javascript">
        $(document).ready(function(){
            var ruta = "{{ URL::to('admin/notificaciones/vista/porcliente/tabla') }}";
            $('#tablaDatatable').load(ruta);
        });
    </script>

    <script>



        function modalNotificacion(id){


            openLoading();
            document.getElementById("formulario-editar").reset();

            axios.post('/admin/notificacion/cliente/informacion', {
                'id': id
            })
                .then((response) => {
                    closeLoading()

                    if (response.data.success === 1) {

                        $('#id-editar').val(id);


                        $('#telefono').val(response.data.info.telefono);


                        $('#modalEditar').modal('show');
                    }

                    else if (response.data.success === 2) {

                        // CLIENTE NO TIENE TOKEN FCM
                        Swal.fire({
                            title: 'Sin Identificador',
                            text: "El cliente seleccionado no se encontro identificador para enviar Notificación",
                            icon: 'info',
                            showCancelButton: false,
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#d33',
                            cancelButtonText: 'Cancelar',
                            confirmButtonText: 'Aceptar'
                        }).then((result) => {
                            if (result.isConfirmed) {

                            }
                        })
                    }

                    else {
                        toastr.error('Error al buscar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }



        function preguntaEnviar(){

            Swal.fire({
                title: 'Enviar Notificación',
                text: "",
                icon: 'info',
                showCancelButton: true,
                allowOutsideClick: false,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Si',
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarNotifiacion();
                }
            })
        }

        function enviarNotifiacion(){

            var idcliente = document.getElementById('id-editar').value;

            var titulo = document.getElementById('titulo').value;
            var mensaje = document.getElementById('mensaje').value;


            if(titulo === '') {
                toastr.error('Título es requerido');
                return;
            }

            if(titulo.length > 100){
                toastr.error('Título máximo 100 caracteres');
                return;
            }


            if(mensaje === '') {
                toastr.error('Mensaje es requerido');
                return;
            }

            if(mensaje.length > 125){
                toastr.error('Mensaje máximo 125 caracteres');
                return;
            }

                openLoading();

                var formData = new FormData();
                formData.append('id', idcliente);
                formData.append('titulo', titulo);
                formData.append('mensaje', mensaje);

                axios.post('/admin/notificaciones/enviar/porcliente', formData, {
                })
                    .then((response) => {
                        closeLoading();

                        if (response.data.success === 1) {

                            $('#modalEditar').modal('hide');

                            alertaEnviados();
                        }
                        else {
                            toastr.error('Error al enviar');
                        }
                    })
                    .catch((error) => {
                        seguroEnviar = true;
                        toastr.error('Error al enviar');
                        closeLoading();
                    });
        }


        function enviarPrueba(){
            var id = $('#id-prueba').val();
            if (!id || Number(id) < 1) { toastr.error('Escribe el ID del cliente'); return; }

            openLoading();
            axios.post('/admin/notificaciones/enviar/prueba', { idcliente: id })
                .then(function (response) {
                    closeLoading();
                    var r = response.data;
                    if (r.success === 1) {
                        toastr.success('Prueba enviada a ' + r.externalId);
                    } else if (r.success === 2) {
                        toastr.warning(r.externalId + ' no tiene teléfonos suscritos en OneSignal');
                    } else if (r.success === 0) {
                        toastr.error(r.mensaje);
                    } else {
                        toastr.error('OneSignal rechazó el envío (revisa storage/logs/laravel.log)');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }


        function alertaEnviados(){

            Swal.fire({
                title: 'Enviado Correctamente',
                text: "",
                icon: 'success',
                showCancelButton: false,
                allowOutsideClick: false,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Aceptar',
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {

                }
            })
        }











    </script>
@stop
