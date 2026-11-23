@extends('adminlte::page')

@section('title', 'Mi perfil')

@section('css')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <style>
        .perfil-avatar {
            width: 96px;
            height: 96px;
            margin: 0 auto 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #007bff, #6610f2);
            box-shadow: 0 6px 18px rgba(0, 123, 255, .3);
        }

        .perfil-dato {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid #f0f0f0;
            font-size: 14px;
        }

        .perfil-dato span:first-child { color: #6c757d; }
        .perfil-dato span:last-child { font-weight: 600; text-align: right; word-break: break-all; }

        .card-perfil .card-title i { width: 20px; }

        .ayuda {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 16px;
        }

        .input-group .btn-ver { border-color: #ced4da; }
    </style>
@stop

@section('content_header')
    <h1><i class="fas fa-user-circle mr-2 text-primary"></i>Mi perfil</h1>
@stop

@section('content')
<div class="row">

    {{-- ===== resumen de la cuenta ===== --}}
    <div class="col-lg-4">
        <div class="card card-primary card-outline">
            <div class="card-body text-center">
                <div class="perfil-avatar">{{ mb_strtoupper(mb_substr($usuario->nombre, 0, 1)) }}</div>
                <h4 class="mb-0">{{ $usuario->nombre }}</h4>
                <p class="text-muted mb-2">&#64;{{ $usuario->usuario }}</p>
                @if($rol)
                    <span class="badge badge-primary px-3 py-2">{{ $rol }}</span>
                @endif

                <div class="text-left mt-4">
                    <div class="perfil-dato">
                        <span>Usuario</span>
                        <span>{{ $usuario->usuario }}</span>
                    </div>
                    <div class="perfil-dato">
                        <span>Correo de recuperación</span>
                        <span id="correo-actual">{{ $usuario->correo ?: 'Sin correo' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">

        {{-- ===== correo de recuperacion ===== --}}
        <div class="card card-perfil">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-envelope text-primary"></i> Correo de recuperación</h3>
            </div>
            <form id="form-correo" autocomplete="off" novalidate>
                <div class="card-body">
                    <p class="ayuda">Si olvidas tu contraseña, te enviaremos a este correo el enlace para crear una nueva.</p>

                    <div class="form-group">
                        <label for="correo">Correo electrónico</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-at"></i></span></div>
                            <input type="email" class="form-control" id="correo" maxlength="100" value="{{ $usuario->correo }}" placeholder="nombre@correo.com">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="correo-clave">Contraseña actual</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                            <input type="password" class="form-control" id="correo-clave" maxlength="16" placeholder="Para confirmar que eres tú" autocomplete="current-password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary btn-ver" data-para="correo-clave" title="Mostrar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Guardar correo</button>
                </div>
            </form>
        </div>

        {{-- ===== contrasena ===== --}}
        <div class="card card-perfil">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-key text-primary"></i> Cambiar contraseña</h3>
            </div>
            <form id="form-clave" autocomplete="off" novalidate>
                <div class="card-body">
                    <div class="form-group">
                        <label for="clave-actual">Contraseña actual</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                            <input type="password" class="form-control" id="clave-actual" maxlength="16" autocomplete="current-password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary btn-ver" data-para="clave-actual" title="Mostrar"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-md-0">
                                <label for="clave-nueva">Nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="clave-nueva" maxlength="16" placeholder="Entre 4 y 16 caracteres" autocomplete="new-password">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary btn-ver" data-para="clave-nueva" title="Mostrar"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="clave-repetir">Repetir contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="clave-repetir" maxlength="16" autocomplete="new-password">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary btn-ver" data-para="clave-repetir" title="Mostrar"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-right">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Actualizar contraseña</button>
                </div>
            </form>
        </div>

    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>

    <script>
        // mostrar / ocultar contrasenas
        document.querySelectorAll('.btn-ver').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var input = document.getElementById(boton.dataset.para);
                var visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                boton.innerHTML = visible ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        });

        function errorRespuesta(success) {
            if (success === 2) {
                toastr.error('La contraseña actual es incorrecta');
            } else if (success === 3) {
                toastr.error('Ese correo ya lo usa otro usuario del panel');
            } else {
                toastr.error('Revisa los datos e intenta de nuevo');
            }
        }

        // ---- correo de recuperacion ----
        document.getElementById('form-correo').addEventListener('submit', function (e) {
            e.preventDefault();

            var correo = document.getElementById('correo').value.trim();
            var clave = document.getElementById('correo-clave').value;

            if (correo === '') { toastr.error('El correo es requerido'); return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) { toastr.error('Ingresa un correo válido'); return; }
            if (clave === '') { toastr.error('Escribe tu contraseña actual'); return; }

            openLoading();
            var datos = new FormData();
            datos.append('correo', correo);
            datos.append('password_actual', clave);

            axios.post('/admin/editar-perfil/correo', datos)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Correo de recuperación actualizado');
                        document.getElementById('correo').value = response.data.correo;
                        document.getElementById('correo-actual').textContent = response.data.correo;
                        document.getElementById('correo-clave').value = '';
                    } else {
                        errorRespuesta(response.data.success);
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error al actualizar');
                });
        });

        // ---- contrasena ----
        document.getElementById('form-clave').addEventListener('submit', function (e) {
            e.preventDefault();

            var actual = document.getElementById('clave-actual').value;
            var nueva = document.getElementById('clave-nueva').value;
            var repetir = document.getElementById('clave-repetir').value;

            if (actual === '') { toastr.error('Escribe tu contraseña actual'); return; }
            if (nueva.length < 4 || nueva.length > 16) { toastr.error('La nueva contraseña debe tener entre 4 y 16 caracteres'); return; }
            if (nueva !== repetir) { toastr.error('Las contraseñas nuevas no coinciden'); return; }

            openLoading();
            var datos = new FormData();
            datos.append('password_actual', actual);
            datos.append('password', nueva);

            axios.post('/admin/editar-perfil/actualizar', datos)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Contraseña actualizada');
                        document.getElementById('form-clave').reset();
                    } else {
                        errorRespuesta(response.data.success);
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error al actualizar');
                });
        });
    </script>
@stop
