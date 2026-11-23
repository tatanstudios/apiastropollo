@extends('adminlte::page')

@section('title', 'Clientes')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/responsive.bootstrap4.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <style>
        .filtro-cliente { cursor: pointer; border: 2px solid transparent; }
        .filtro-cliente:hover { border-color: #ced4da; }
        .filtro-cliente.activo { border-color: #007bff; }
        #tabla_processing { z-index: 5; }
    </style>
@stop

@php
    $tarjetas = [
        'todos' => ['Todos', 'bg-info', 'fas fa-users'],
        'activo' => ['Activos', 'bg-success', 'fas fa-user-check'],
        'desactivado' => ['Desactivados', 'bg-secondary', 'fas fa-user-slash'],
        'nuevo' => ['Nuevos (30 días)', 'bg-primary', 'fas fa-user-plus'],
        'sinordenes' => ['Nunca han pedido', 'bg-warning', 'fas fa-receipt'],
    ];
@endphp

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="mb-0">Clientes registrados</h1>
        <p class="text-muted mb-0">Personas con cuenta en la app. Un cliente desactivado no puede entrar ni hacer órdenes.</p>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            @foreach($tarjetas as $clave => [$texto, $color, $icono])
                <div class="col-6 col-md">
                    <div class="info-box mb-3 filtro-cliente {{ $clave === 'todos' ? 'activo' : '' }}" data-filtro="{{ $clave }}" onclick="filtrarClientes('{{ $clave }}')" title="Filtrar la tabla">
                        <span class="info-box-icon {{ $color }} elevation-1"><i class="{{ $icono }}"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ $texto }}</span>
                            <span class="info-box-number">{{ number_format($resumen[$clave]) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card card-primary card-outline">
            <div class="card-body">
                <table id="tabla" class="table table-bordered table-striped table-hover" style="width: 100%">
                    <thead>
                    <tr>
                        <th>Cliente</th>
                        <th style="width: 160px">Registro</th>
                        <th style="width: 140px">App</th>
                        <th style="width: 150px">Estado</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</section>
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
        var filtroClientes = 'todos';
        var tabla;

        function esc(texto) {
            return $('<div>').text(texto == null ? '' : String(texto)).html();
        }

        $(function () {
            tabla = iniciarTabla('#tabla', {
                "processing": true,
                "serverSide": true,
                "searchDelay": 400,
                "pageLength": 25,
                // con miles de clientes no se ofrece "Todo"
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "order": [[1, 'desc']],
                "ajax": {
                    "url": "{{ url('/admin/clientes/listado/tabla') }}",
                    "data": function (d) { d.filtro = filtroClientes; },
                    "error": function () { toastr.error('No se pudieron cargar los clientes'); }
                },
                "columns": [
                    { "data": "usuario", "render": function (v, t, c) {
                        return '<b>' + esc(c.usuario) + '</b>'
                            + (c.nuevo ? ' <span class="badge bg-primary">nuevo</span>' : '')
                            + '<div class="small text-muted">' + (c.correo ? esc(c.correo) : 'Sin correo') + '</div>';
                    } },
                    { "data": "fecha", "render": esc },
                    { "data": "version", "render": function (v, t, c) {
                        var icono = c.sistema === 'android' ? '<i class="fab fa-android fa-lg text-success mr-1" title="Android"></i>'
                            : (c.sistema === 'ios' ? '<i class="fab fa-apple fa-lg mr-1" title="iPhone"></i>'
                            : '<i class="fas fa-question-circle text-muted mr-1" title="Esta versión de la app no guardaba si era Android o iPhone"></i>');
                        return icono + (c.version ? esc(c.version) : '—');
                    } },
                    { "data": "activo", "render": function (v, t, c) {
                        return '<div class="custom-control custom-switch">'
                            + '<input type="checkbox" class="custom-control-input" id="activo-' + c.id + '"' + (v ? ' checked' : '')
                            + ' onchange="cambiarActivo(' + c.id + ', this)" data-nombre="' + esc(c.usuario) + '">'
                            + '<label class="custom-control-label" for="activo-' + c.id + '">' + (v ? 'Activo' : 'Desactivado') + '</label>'
                            + '</div>';
                    } }
                ]
            });
        });

        function filtrarClientes(filtro) {
            filtroClientes = filtro;
            $('.filtro-cliente').removeClass('activo');
            $('.filtro-cliente[data-filtro="' + filtro + '"]').addClass('activo');
            if (tabla) { tabla.ajax.reload(); }
        }

        function enviarActivo(id, activo, interruptor) {
            openLoading();
            axios.post('/admin/clientes/informacion/editar', { id: id, activo: activo })
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success(activo ? 'Cliente activado' : 'Cliente desactivado');
                        $(interruptor).next('label').text(activo ? 'Activo' : 'Desactivado');
                    } else {
                        interruptor.checked = !interruptor.checked;
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(function () {
                    closeLoading();
                    interruptor.checked = !interruptor.checked;
                    toastr.error('Error del servidor');
                });
        }

        function cambiarActivo(id, interruptor) {
            if (interruptor.checked) { enviarActivo(id, 1, interruptor); return; }

            Swal.fire({
                titleText: '¿Desactivar a ' + $(interruptor).data('nombre') + '?',
                text: 'Se cierra su sesión en la app y no podrá volver a entrar ni hacer órdenes hasta que lo actives.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, desactivar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) { enviarActivo(id, 0, interruptor); }
                else { interruptor.checked = true; }
            });
        }
    </script>
@stop
