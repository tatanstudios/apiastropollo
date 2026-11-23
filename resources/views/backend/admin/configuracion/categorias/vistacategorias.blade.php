@extends('adminlte::page')

@section('title', 'Categorías')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')
<style>
    .cat-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
    .cat-header h1 { margin:0; font-size:1.75rem; }
    .cat-header .subtitulo { color:#6c757d; font-size:.95rem; margin-top:2px; }
    .cat-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; }
    .cat-toolbar .input-group { max-width:320px; }
    .cat-hint { color:#6c757d; font-size:.85rem; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="cat-header">
            <div>
                <h1>Categorías</h1>
                @if(!empty($restaurante))
                    <div class="subtitulo"><i class="fas fa-store mr-1"></i>{{ $restaurante }}</div>
                @endif
            </div>
            <div>
                <a href="{{ route('index.servicios.listado') }}" class="btn btn-outline-secondary btn-sm mr-1">
                    <i class="fas fa-arrow-left mr-1"></i> Restaurantes
                </a>
                <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nueva Categoría
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="cat-toolbar">
                    <span class="cat-hint"><i class="fas fa-arrows-alt-v mr-1"></i>Arrastra las filas para cambiar el orden en el menú</span>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="text" id="buscar-categoria" class="form-control" placeholder="Buscar categoría..." autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="tablaDatatable"></div>
            </div>
        </div>
    </div>
</section>

<style>
    .cat-modal .modal-header { border-bottom:0; padding-bottom:0; }
    .cat-modal .modal-title { font-weight:600; }
    .cat-modal .modal-title small { display:block; font-size:.85rem; font-weight:400; color:#6c757d; margin-top:2px; }
    .cat-modal .modal-footer { border-top:1px solid #f1f1f1; }
    .cat-modal label { font-weight:600; font-size:.9rem; }
    .cat-seccion { background:#f8f9fa; border:1px solid #eef0f2; border-radius:10px; padding:14px 16px; margin-bottom:16px; }
    .cat-seccion .custom-control-label { font-weight:600; cursor:pointer; }
    .cat-seccion .ayuda { color:#6c757d; font-size:.82rem; margin:2px 0 0; }
    .cat-horas { margin-top:12px; }
    .cat-imagen {
        position:relative; display:flex; flex-direction:column; align-items:center; justify-content:center;
        height:210px; width:100%; border:2px dashed #ced4da; border-radius:12px; background:#fffaf3;
        cursor:pointer; text-align:center; color:#6c757d; font-weight:400 !important;
        transition:border-color .15s, background .15s; overflow:hidden;
    }
    .cat-imagen:hover, .cat-imagen.arrastrando { border-color:#007bff; background:#f2f8ff; }
    .cat-imagen img { max-width:85%; max-height:150px; object-fit:contain; }
    .cat-imagen .vacio i, .cat-imagen .vacio svg { font-size:2rem; margin:0 auto 6px; color:#adb5bd; display:block; }
    .cat-imagen .cambiar { margin-top:8px; font-size:.8rem; }
    .cat-imagen input[type=file] { display:none; }
</style>

<!-- modal agregar -->
<div class="modal fade cat-modal" id="modalAgregar">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Nueva Categoría
                    @if(!empty($restaurante))<small><i class="fas fa-store mr-1"></i>{{ $restaurante }}</small>@endif
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-nuevo" onsubmit="return false">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label>Imagen <span class="text-danger">*</span></label>
                            <label class="cat-imagen" id="caja-imagen-nuevo" for="imagen-nuevo">
                                <div class="vacio">
                                    <i class="fas fa-image d-block"></i>
                                    <div>Haz clic o arrastra una imagen</div>
                                    <small>PNG o JPG</small>
                                </div>
                                <img alt="Vista previa" style="display:none">
                                <span class="cambiar btn btn-light btn-sm" style="display:none"><i class="fas fa-sync-alt mr-1"></i>Cambiar</span>
                                <input type="file" id="imagen-nuevo" accept="image/jpeg, image/jpg, image/png"/>
                            </label>
                        </div>

                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="nombre-nuevo">Nombre <span class="text-danger">*</span></label>
                                <input type="text" maxlength="100" class="form-control" autocomplete="off" id="nombre-nuevo" placeholder="Ej. Combos familiares">
                            </div>

                            <div class="cat-seccion">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-horario">
                                    <label class="custom-control-label" for="toggle-horario">Horario propio</label>
                                </div>
                                <p class="ayuda">Si está apagado, la categoría se muestra siempre que el restaurante esté abierto.</p>

                                <div class="row cat-horas" id="horas-nuevo" style="display:none">
                                    <div class="col-6">
                                        <label for="hora-abre">Abre</label>
                                        <input type="time" class="form-control" id="hora-abre">
                                    </div>
                                    <div class="col-6">
                                        <label for="hora-cierra">Cierra</label>
                                        <input type="time" class="form-control" id="hora-cierra">
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-light border small mb-0">
                                <i class="fas fa-info-circle text-info mr-1"></i>
                                La categoría se crea <b>oculta</b>. Actívala desde <b>Editar</b> cuando tenga productos.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarRegistro()"><i class="fas fa-check mr-1"></i>Guardar</button>
            </div>
        </div>
    </div>
</div>


<!-- modal editar -->
<div class="modal fade cat-modal" id="modalEditar">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Editar Categoría
                    @if(!empty($restaurante))<small><i class="fas fa-store mr-1"></i>{{ $restaurante }}</small>@endif
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-editar" onsubmit="return false">
                    <input type="hidden" id="id-editar">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label>Imagen</label>
                            <label class="cat-imagen" id="caja-imagen-editar" for="imagen-editar">
                                <div class="vacio">
                                    <i class="fas fa-image d-block"></i>
                                    <div>Haz clic o arrastra una imagen</div>
                                    <small>PNG o JPG</small>
                                </div>
                                <img alt="Vista previa" style="display:none">
                                <span class="cambiar btn btn-light btn-sm" style="display:none"><i class="fas fa-sync-alt mr-1"></i>Cambiar</span>
                                <input type="file" id="imagen-editar" accept="image/jpeg, image/jpg, image/png"/>
                            </label>
                        </div>

                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="nombre-editar">Nombre <span class="text-danger">*</span></label>
                                <input type="text" maxlength="100" class="form-control" autocomplete="off" id="nombre-editar" placeholder="Nombre">
                            </div>

                            <div class="cat-seccion">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-activo">
                                    <label class="custom-control-label" for="toggle-activo">Visible en el menú</label>
                                </div>
                                <p class="ayuda">Apágalo para ocultar la categoría a los clientes sin borrarla.</p>
                            </div>

                            <div class="cat-seccion">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-horario-editar">
                                    <label class="custom-control-label" for="toggle-horario-editar">Horario propio</label>
                                </div>
                                <p class="ayuda">Si está apagado, la categoría se muestra siempre que el restaurante esté abierto.</p>

                                <div class="row cat-horas" id="horas-editar" style="display:none">
                                    <div class="col-6">
                                        <label for="hora-abre-editar">Abre</label>
                                        <input type="time" class="form-control" id="hora-abre-editar">
                                    </div>
                                    <div class="col-6">
                                        <label for="hora-cierra-editar">Cierra</label>
                                        <input type="time" class="form-control" id="hora-cierra-editar">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="editar()"><i class="fas fa-check mr-1"></i>Guardar cambios</button>
            </div>
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

    <script type="text/javascript">
        $(document).ready(function(){
            var id = {{ $id }};
            var ruta = "{{ URL::to('/admin/categorias/listado/tabla/') }}/"+id;
            $('#tablaDatatable').load(ruta);
        });
    </script>

    <script>

        function recargar(){
            var id = {{ $id }};
            var ruta = "{{ URL::to('/admin/categorias/listado/tabla/') }}/"+id;
            $('#tablaDatatable').load(ruta, filtrarCategorias);
        }

        // filtro rapido por nombre
        function filtrarCategorias(){
            var texto = ($('#buscar-categoria').val() || '').toLowerCase().trim();
            var visibles = 0;
            $('#tablecontents tr.row1').each(function(){
                var coincide = $(this).data('nombre').toString().indexOf(texto) !== -1;
                $(this).toggle(coincide);
                if(coincide) visibles++;
            });
            $('#sin-resultados').toggle(visibles === 0 && texto !== '');
        }

        $(document).on('input', '#buscar-categoria', filtrarCategorias);

        // ---- utilidades de los modales ----
        var HORA_ABRE_DEFECTO = '00:00';
        var HORA_CIERRA_DEFECTO = '23:59';

        // muestra u oculta los campos de hora segun el switch
        function sincronizarHoras(toggleId, horasId){
            $('#' + horasId).toggle($('#' + toggleId).is(':checked'));
        }
        $(document).on('change', '#toggle-horario', function(){ sincronizarHoras('toggle-horario', 'horas-nuevo'); });
        $(document).on('change', '#toggle-horario-editar', function(){ sincronizarHoras('toggle-horario-editar', 'horas-editar'); });

        // vista previa de imagen (src = null limpia la caja)
        function mostrarImagen(cajaId, src){
            var $caja = $('#' + cajaId);
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

        function previsualizar(input, cajaId){
            if(!(input.files && input.files[0])) return;
            if(!imagenValida(input.files[0])){
                toastr.error(errorImagen(input.files[0]));
                input.value = '';
                return;
            }
            var lector = new FileReader();
            lector.onload = function(e){ mostrarImagen(cajaId, e.target.result); };
            lector.readAsDataURL(input.files[0]);
        }

        $(document).on('change', '#imagen-nuevo', function(){ previsualizar(this, 'caja-imagen-nuevo'); });
        $(document).on('change', '#imagen-editar', function(){ previsualizar(this, 'caja-imagen-editar'); });

        // arrastrar y soltar imagen sobre la caja
        $(document).on('dragover dragenter', '.cat-imagen', function(e){
            e.preventDefault(); $(this).addClass('arrastrando');
        });
        $(document).on('dragleave dragend drop', '.cat-imagen', function(e){
            e.preventDefault(); $(this).removeClass('arrastrando');
        });
        $(document).on('drop', '.cat-imagen', function(e){
            var archivos = e.originalEvent.dataTransfer.files;
            if(!archivos.length) return;
            var input = $(this).find('input[type=file]')[0];
            input.files = archivos;
            $(input).trigger('change');
        });

        // abrir modal
        function modalNuevo(){
            document.getElementById("formulario-nuevo").reset();
            mostrarImagen('caja-imagen-nuevo', null);
            sincronizarHoras('toggle-horario', 'horas-nuevo');
            $('#modalAgregar').modal('show');
        }

        $('#modalAgregar').on('shown.bs.modal', function(){ $('#nombre-nuevo').trigger('focus'); });

        //nueva categoria
        function guardarRegistro(){

            var nombre = document.getElementById('nombre-nuevo').value;
            var horaabre = document.getElementById('hora-abre').value;
            var horacierra = document.getElementById('hora-cierra').value;
            var cbtoggle = document.getElementById('toggle-horario').checked;
            var imagen = document.getElementById('imagen-nuevo');

            var toggleHorario = cbtoggle? 1 : 0;
            var id = {{ $id }};

            if(nombre === '') {
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 100){
                toastr.error('Nombre máximo 100 caracteres');
                return;
            }


            if(cbtoggle){
                if (horaabre === '') {
                    toastr.error("Hora de apertura es requerida");
                    return;
                }
                if (horacierra === '') {
                    toastr.error("Hora de cierre es requerida");
                    return;
                }
            }else{
                // sin horario propio la API ignora las horas; se guarda un valor por defecto
                if(horaabre === '') horaabre = HORA_ABRE_DEFECTO;
                if(horacierra === '') horacierra = HORA_CIERRA_DEFECTO;
            }

            if(imagen.files && imagen.files[0]){ // si trae imagen
                if (!imagenValida(imagen.files[0])){
                    toastr.error(errorImagen(imagen.files[0]));
                    return;
                }
            }else{
                toastr.error('Imagen es requerido');
                return;
            }

            openLoading();

            var formData = new FormData();
            formData.append('id', id);
            formData.append('nombre', nombre);
            formData.append('imagen', imagen.files[0]);
            formData.append('horaabre', horaabre);
            formData.append('horacierra', horacierra);
            formData.append('toggle', toggleHorario);

            axios.post('/admin/categorias/nuevo', formData, {
            })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        $('#modalAgregar').modal('hide');
                        toastr.success('Registrado correctamente');
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

        function informacion(id){

            document.getElementById("formulario-editar").reset();
            mostrarImagen('caja-imagen-editar', null);
            openLoading();

            axios.post('/admin/categorias/informacion',{
                'id': id
            })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){

                        $('#modalEditar').modal('show');
                        $('#id-editar').val(id);
                        $('#nombre-editar').val(response.data.categoria.nombre);

                        if(response.data.categoria.usa_horario === 0){
                            $("#toggle-horario-editar").prop("checked", false);
                        }else{
                            $("#toggle-horario-editar").prop("checked", true);
                        }

                        $('#hora-abre-editar').val(response.data.categoria.hora_abre);
                        $('#hora-cierra-editar').val(response.data.categoria.hora_cierra);


                        if(response.data.categoria.activo === 0){
                            $("#toggle-activo").prop("checked", false);
                        }else{
                            $("#toggle-activo").prop("checked", true);
                        }

                        sincronizarHoras('toggle-horario-editar', 'horas-editar');

                        if(response.data.categoria.imagen){
                            mostrarImagen('caja-imagen-editar', "{{ url('storage/imagenes') }}/" + response.data.categoria.imagen);
                        }

                    }else{
                        toastr.error('Error al buscar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al buscar');
                    closeLoading();
                });
        }

        function editar(){

            var id = document.getElementById('id-editar').value;
            var nombre = document.getElementById('nombre-editar').value;
            var cbactivo = document.getElementById('toggle-activo').checked;

            var toggleActivo = cbactivo ? 1 : 0;

            var cbhorario = document.getElementById('toggle-horario-editar').checked;
            var toggleHorario = cbhorario ? 1 : 0;

            var horaabre = document.getElementById('hora-abre-editar').value;
            var horacierra = document.getElementById('hora-cierra-editar').value;
            var imagen = document.getElementById('imagen-editar');

            if(nombre === '') {
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 100){
                toastr.error('Nombre máximo 100 caracteres');
                return;
            }

            if(cbhorario){
                if(horaabre === ''){
                    toastr.error('Hora de apertura es requerida');
                    return;
                }
                if(horacierra === ''){
                    toastr.error('Hora de cierre es requerida');
                    return;
                }
            }else{
                if(horaabre === '') horaabre = HORA_ABRE_DEFECTO;
                if(horacierra === '') horacierra = HORA_CIERRA_DEFECTO;
            }

            if(imagen.files && imagen.files[0]){ // si trae imagen
                if (!imagenValida(imagen.files[0])){
                    toastr.error(errorImagen(imagen.files[0]));
                    return;
                }
            }


            openLoading();
            var formData = new FormData();
            formData.append('id', id);
            formData.append('nombre', nombre);
            formData.append('cbactivo', toggleActivo);
            formData.append('cbhorario', toggleHorario);
            formData.append('horaabre', horaabre);
            formData.append('horacierra', horacierra);
            if(imagen.files && imagen.files[0]){
                formData.append('imagen', imagen.files[0]);
            }

            axios.post('/admin/categorias/editar', formData, {
            })
                .then((response) => {
                    closeLoading();

                    if (response.data.success === 1) {
                        $('#modalEditar').modal('hide');
                        toastr.success('Actualizado correctamente');
                        recargar();
                    }
                    else {
                        toastr.error(response.data.mensaje || 'Error al Editar');
                    }
                })
                .catch((error) => {
                    toastr.error('Error al Editar');
                    closeLoading();
                });
        }

        function verSubCategorias(id) {
            window.location.href="{{ url('/admin/sub/categorias/listado') }}/"+id;
        }


    </script>
@stop
