@extends('adminlte::page')

@section('title', 'Banners')

@section('css')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/select2.min.css') }}" type="text/css" rel="stylesheet">
    <link href="{{ asset('css/select2-bootstrap-5-theme.min.css') }}" type="text/css" rel="stylesheet">
@stop

@section('content')
<style>
    .sl-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
    .sl-header h1 { margin:0; font-size:1.75rem; }
    .sl-ruta { color:#6c757d; font-size:.92rem; margin-top:2px; }
    .sl-hint { color:#6c757d; font-size:.85rem; }

    .sl-modal .modal-header { border-bottom:0; padding-bottom:0; }
    .sl-modal .modal-title { font-weight:600; }
    .sl-modal .modal-title small { display:block; font-size:.85rem; font-weight:400; color:#6c757d; margin-top:2px; }
    .sl-modal .modal-footer { border-top:1px solid #f1f1f1; }
    .sl-modal label { font-weight:600; font-size:.9rem; }
    .sl-seccion { background:#f8f9fa; border:1px solid #eef0f2; border-radius:10px; padding:12px 14px; margin-bottom:14px; }
    .sl-seccion .custom-control-label { font-weight:600; cursor:pointer; }
    .sl-seccion .ayuda { color:#6c757d; font-size:.82rem; margin:2px 0 0; }

    .sl-imagen {
        position:relative; display:flex; flex-direction:column; align-items:center; justify-content:center;
        width:100%; aspect-ratio:16 / 9; border:2px dashed #ced4da; border-radius:12px; background:#f8f9fa;
        cursor:pointer; text-align:center; color:#6c757d; font-weight:400 !important;
        transition:border-color .15s, background .15s; overflow:hidden; margin-bottom:0;
    }
    .sl-imagen:hover, .sl-imagen.arrastrando { border-color:#007bff; background:#f2f8ff; }
    .sl-imagen img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .sl-imagen .vacio i, .sl-imagen .vacio svg { font-size:2rem; margin:0 auto 6px; color:#adb5bd; display:block; }
    .sl-imagen .cambiar { position:absolute; right:8px; bottom:8px; font-size:.78rem; box-shadow:0 1px 4px rgba(0,0,0,.2); }
    .sl-imagen input[type=file] { display:none; }

    /* select2 dentro del modal con el mismo alto que los inputs */
    .sl-modal .select2-container { width:100% !important; }

    #visor-imagen .modal-content { background:transparent; border:0; }
    #visor-imagen img { width:100%; border-radius:10px; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="sl-header">
            <div>
                <h1>Banners</h1>
                <div class="sl-ruta"><i class="fas fa-store mr-1"></i>{{ $restaurante ?: 'Restaurante' }}</div>
            </div>
            <div>
                <a href="{{ route('index.servicios.listado') }}" class="btn btn-outline-secondary btn-sm mr-1">
                    <i class="fas fa-arrow-left mr-1"></i> Restaurantes
                </a>
                <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nuevo Banner
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <span class="sl-hint"><i class="fas fa-arrows-alt-v mr-1"></i>Arrastra las filas para cambiar el orden en que se muestran en la app</span>
            </div>
            <div class="card-body p-0">
                <div id="tablaDatatable"></div>
            </div>
        </div>
    </div>
</section>

<!-- modal nuevo / editar -->
<div class="modal fade sl-modal" id="modalBanner">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><span id="titulo-modal">Nuevo Banner</span>
                    <small><i class="fas fa-store mr-1"></i>{{ $restaurante ?: 'Restaurante' }}</small>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-banner" onsubmit="return false">
                    <input type="hidden" id="id-editar">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Imagen <span class="text-danger" id="imagen-requerida">*</span></label>
                            <label class="sl-imagen" id="caja-imagen" for="imagen-banner">
                                <div class="vacio">
                                    <i class="fas fa-image"></i>
                                    <div>Haz clic o arrastra una imagen</div>
                                    <small>PNG o JPG · horizontal</small>
                                </div>
                                <img alt="Vista previa" style="display:none">
                                <span class="cambiar btn btn-light btn-sm" style="display:none"><i class="fas fa-sync-alt mr-1"></i>Cambiar</span>
                                <input type="file" id="imagen-banner" accept="image/jpeg, image/jpg, image/png"/>
                            </label>

                            <div class="form-group mt-3 mb-0">
                                <label for="nombre-banner">Descripción <span class="text-muted font-weight-normal">(solo para el panel)</span></label>
                                <input type="text" autocomplete="off" maxlength="200" class="form-control" id="nombre-banner" placeholder="Ej. Promo de bienvenida">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="sl-seccion" id="seccion-activo" style="display:none">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-activo">
                                    <label class="custom-control-label" for="toggle-activo">Visible en la app</label>
                                </div>
                                <p class="ayuda">Apágalo para ocultar el banner sin borrarlo.</p>
                            </div>

                            <div class="sl-seccion">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-redireccion">
                                    <label class="custom-control-label" for="toggle-redireccion">Abrir un producto al tocarlo</label>
                                </div>
                                <p class="ayuda">Si está apagado, el banner es solo una imagen.</p>
                                <div class="mt-2" id="grupo-producto" style="display:none">
                                    <label for="select-producto">Producto <span class="text-danger">*</span></label>
                                    <select class="form-control" id="select-producto">
                                        <option value="">Buscar producto...</option>
                                        @foreach($grupoProductos as $grupo => $lista)
                                            <optgroup label="{{ $grupo }}">
                                                @foreach($lista as $pp)
                                                    <option value="{{ $pp->id }}">{{ $pp->nombre }}{{ $pp->activo ? '' : ' (oculto)' }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="sl-seccion mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-horario">
                                    <label class="custom-control-label" for="toggle-horario">Horario propio</label>
                                </div>
                                <p class="ayuda">Si está apagado, el banner se muestra todo el día.</p>
                                <div class="row mt-2" id="grupo-horas" style="display:none">
                                    <div class="col-6">
                                        <label for="hora-abre">Desde</label>
                                        <input type="time" class="form-control" id="hora-abre">
                                    </div>
                                    <div class="col-6">
                                        <label for="hora-cierra">Hasta</label>
                                        <input type="time" class="form-control" id="hora-cierra">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardar()"><i class="fas fa-check mr-1"></i><span id="texto-guardar">Guardar</span></button>
            </div>
        </div>
    </div>
</div>

<!-- visor de imagen completa -->
<div class="modal fade" id="visor-imagen" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" data-dismiss="modal" style="cursor:zoom-out">
            <img alt="Banner">
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
        // sin horario propio la app no usa las horas; la BD las exige, se guarda un valor por defecto
        const HORA_ABRE_DEFECTO = '00:00';
        const HORA_CIERRA_DEFECTO = '23:59';

        let tieneImagenGuardada = false;

        $(document).ready(function(){
            recargar();

            $('#select-producto').select2({
                theme: "bootstrap-5",
                dropdownParent: $('#modalBanner'),
                placeholder: 'Buscar producto...',
                language: { noResults: function(){ return "Búsqueda no encontrada"; } },
            });
        });

        function recargar(){
            $('#tablaDatatable').load("{{ URL::to('/admin/slider/listado/tabla') }}/" + ID_SERVICIO);
        }

        // ---- utilidades del modal ----
        function sincronizarSecciones(){
            $('#grupo-producto').toggle($('#toggle-redireccion').is(':checked'));
            $('#grupo-horas').toggle($('#toggle-horario').is(':checked'));
        }
        $(document).on('change', '#toggle-redireccion, #toggle-horario', sincronizarSecciones);

        function mostrarImagen(src){
            var $caja = $('#caja-imagen');
            if(src){
                $caja.find('img').attr('src', src).show();
                $caja.find('.cambiar').show();
                $caja.find('.vacio').hide();
            }else{
                $caja.find('img').removeAttr('src').hide();
                $caja.find('.cambiar').hide();
                $caja.find('.vacio').show();
            }
        }

        // mismo limite que el servidor (App\Services\Imagenes::PESO_MAXIMO_KB)
        const IMAGEN_PESO_MAXIMO_MB = 8;

        // mensaje de error de la imagen o null si esta bien
        function errorImagen(file){
            if(!file.type.match('image/jpeg|image/jpg|image/png')) return 'Formato de imagen permitido: .png .jpg .jpeg';
            if(file.size > IMAGEN_PESO_MAXIMO_MB * 1024 * 1024){
                return 'La imagen pesa ' + (file.size / 1048576).toFixed(1) + ' MB; el máximo es ' + IMAGEN_PESO_MAXIMO_MB + ' MB';
            }
            return null;
        }

        function imagenValida(file){
            return errorImagen(file) === null;
        }

        $(document).on('change', '#imagen-banner', function(){
            var input = this;
            if(!(input.files && input.files[0])) return;
            if(!imagenValida(input.files[0])){
                toastr.error(errorImagen(input.files[0]));
                input.value = '';
                return;
            }
            var lector = new FileReader();
            lector.onload = function(e){ mostrarImagen(e.target.result); };
            lector.readAsDataURL(input.files[0]);
        });

        // arrastrar y soltar imagen sobre la caja
        $(document).on('dragover dragenter', '.sl-imagen', function(e){ e.preventDefault(); $(this).addClass('arrastrando'); });
        $(document).on('dragleave dragend drop', '.sl-imagen', function(e){ e.preventDefault(); $(this).removeClass('arrastrando'); });
        $(document).on('drop', '.sl-imagen', function(e){
            var archivos = e.originalEvent.dataTransfer.files;
            if(!archivos.length) return;
            var input = document.getElementById('imagen-banner');
            input.files = archivos;
            $(input).trigger('change');
        });

        function verImagen(src){
            $('#visor-imagen img').attr('src', src);
            $('#visor-imagen').modal('show');
        }

        function limpiarModal(){
            document.getElementById('formulario-banner').reset();
            $('#id-editar').val('');
            $('#select-producto').val('').trigger('change');
            mostrarImagen(null);
            tieneImagenGuardada = false;
        }

        // ---- nuevo ----
        function modalNuevo(){
            limpiarModal();
            $('#titulo-modal').text('Nuevo Banner');
            $('#texto-guardar').text('Guardar');
            $('#imagen-requerida').show();
            $('#seccion-activo').hide();
            sincronizarSecciones();
            $('#modalBanner').modal('show');
        }

        // ---- editar ----
        function informacion(id){
            limpiarModal();
            openLoading();

            axios.post('/admin/slider/informacion', { 'id': id })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){
                        var s = response.data.slider;

                        $('#titulo-modal').text('Editar Banner');
                        $('#texto-guardar').text('Guardar cambios');
                        $('#imagen-requerida').hide();
                        $('#seccion-activo').show();

                        $('#id-editar').val(id);
                        $('#nombre-banner').val(s.nombre);
                        $('#toggle-activo').prop('checked', s.activo !== 0);
                        $('#toggle-redireccion').prop('checked', s.redireccionamiento !== 0);
                        $('#select-producto').val(s.id_producto ? String(s.id_producto) : '').trigger('change');
                        $('#toggle-horario').prop('checked', s.usa_horario !== 0);
                        $('#hora-abre').val((s.hora_abre || '').substring(0, 5));
                        $('#hora-cierra').val((s.hora_cierra || '').substring(0, 5));

                        if(s.imagen){
                            tieneImagenGuardada = true;
                            mostrarImagen("{{ url('storage/imagenes') }}/" + s.imagen);
                        }

                        sincronizarSecciones();
                        $('#modalBanner').modal('show');
                    }else{
                        toastr.error('Error al buscar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error de servidor');
                    closeLoading();
                });
        }

        // ---- guardar (nuevo o editar) ----
        function guardar(){

            var idslider = $('#id-editar').val();
            var esNuevo = idslider === '';
            var imagen = document.getElementById('imagen-banner');
            var producto = $('#select-producto').val() || '';
            var nombre = $('#nombre-banner').val().trim();
            var toggleRedireccion = $('#toggle-redireccion').is(':checked') ? 1 : 0;
            var toggleHorario = $('#toggle-horario').is(':checked') ? 1 : 0;
            var horaabre = $('#hora-abre').val();
            var horacierra = $('#hora-cierra').val();

            if(nombre.length > 200){
                toastr.error('La descripción debe tener 200 caracteres máximo');
                return;
            }

            if(toggleRedireccion === 1 && producto === ''){
                toastr.error('Selecciona el producto que se abre al tocar el banner');
                return;
            }

            var tieneArchivo = imagen.files && imagen.files[0];
            if(tieneArchivo){
                if(!imagenValida(imagen.files[0])){
                    toastr.error(errorImagen(imagen.files[0]));
                    return;
                }
            }else if(esNuevo || !tieneImagenGuardada){
                toastr.error('Sube la imagen del banner');
                return;
            }

            if(toggleHorario === 1){
                if(horaabre === ''){ toastr.error('Indica desde qué hora se muestra'); return; }
                if(horacierra === ''){ toastr.error('Indica hasta qué hora se muestra'); return; }
            }else{
                if(horaabre === '') horaabre = HORA_ABRE_DEFECTO;
                if(horacierra === '') horacierra = HORA_CIERRA_DEFECTO;
            }

            openLoading();

            var formData = new FormData();
            if(tieneArchivo){
                formData.append('imagen', imagen.files[0]);
            }
            formData.append('producto', toggleRedireccion === 1 ? producto : '');
            formData.append('nombre', nombre);
            formData.append('toggledireccion', toggleRedireccion);
            formData.append('togglehorario', toggleHorario);
            formData.append('horaabre', horaabre);
            formData.append('horacierra', horacierra);

            var url = '/admin/slider/nuevo';
            if(esNuevo){
                formData.append('idservicio', ID_SERVICIO);
            }else{
                url = '/admin/slider/editar';
                formData.append('idslider', idslider);
                formData.append('toggleactivo', $('#toggle-activo').is(':checked') ? 1 : 0);
            }

            axios.post(url, formData)
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        $('#modalBanner').modal('hide');
                        toastr.success(esNuevo ? 'Banner creado' : 'Banner actualizado');
                        recargar();
                    }
                    else {
                        toastr.error(response.data.mensaje || 'Error al guardar');
                    }
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al guardar');
                });
        }

        // ---- borrar ----
        function modalBorrar(id){
            Swal.fire({
                title: '¿Borrar banner?',
                text: 'Se borrará el banner y su imagen. No se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Borrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    borrarSlider(id);
                }
            })
        }

        function borrarSlider(id){
            openLoading();
            var formData = new FormData();
            formData.append('id', id);

            axios.post('/admin/slider/borrar', formData)
                .then((response) => {
                    closeLoading();

                    if (response.data.success === 2) {
                        toastr.success('Banner borrado');
                        recargar();
                    }
                    else {
                        toastr.error('Error al borrar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al borrar');
                    closeLoading();
                });
        }

    </script>
@stop
