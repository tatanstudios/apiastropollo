@extends('adminlte::page')

@section('title', 'Clientes que estan asociados a un Restaurante')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/buttons_estilo.css') }}" rel="stylesheet">
@stop

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="col-sm-12">
            <h1>Clientes que estan asociados a un Restaurante</h1>
        </div>

    </div>
</section>

<section class="content">
    <div class="container-fluid" style="margin-left: 15px">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Listado</h3>
                    </div>
                    <form>
                        <div class="card-body">

                            @foreach($listaRestaurantes as $dato)


                                <p>Restaurante: {{ $dato->nombre }}</p>
                                <p>Registrados: {{ $dato->conteo }}</p>

                                <br>
                                <hr>

                            @endforeach



                        </div>

                    </form>
                </div>

            </div>

        </div>
    </div>
</section>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>






    </script>
@stop
