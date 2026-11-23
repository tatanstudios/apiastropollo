@extends('adminlte::page')

@section('title', 'Productos')

@section('css')
    <link href="{{ asset('css/dataTables.bootstrap4.css') }}" type="text/css" rel="stylesheet" />
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')
<style>
    .prod-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
    .prod-header h1 { margin:0; font-size:1.75rem; }
    .prod-ruta { color:#6c757d; font-size:.92rem; margin-top:2px; }
    .prod-ruta a { color:#6c757d; }
    .prod-ruta a:hover { color:#007bff; }
    .prod-ruta .sep { margin:0 6px; font-size:.7rem; }
    .prod-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; }
    .prod-toolbar .input-group { max-width:320px; }
    .prod-hint { color:#6c757d; font-size:.85rem; }

    .prod-modal .modal-header { border-bottom:0; padding-bottom:0; }
    .prod-modal .modal-title { font-weight:600; }
    .prod-modal .modal-title small { display:block; font-size:.85rem; font-weight:400; color:#6c757d; margin-top:2px; }
    .prod-modal .modal-footer { border-top:1px solid #f1f1f1; }
    .prod-modal label { font-weight:600; font-size:.9rem; }
    .prod-seccion { background:#f8f9fa; border:1px solid #eef0f2; border-radius:10px; padding:12px 14px; margin-bottom:14px; }
    .prod-seccion .custom-control-label { font-weight:600; cursor:pointer; }
    .prod-seccion .ayuda { color:#6c757d; font-size:.82rem; margin:2px 0 0; }
    .prod-modal label.con-contador { display:flex; justify-content:space-between; align-items:baseline; }
    .prod-contador { font-weight:400; color:#adb5bd; font-size:.78rem; }

    .prod-imagen {
        position:relative; display:flex; flex-direction:column; align-items:center; justify-content:center;
        width:100%; aspect-ratio:3 / 2; border:2px dashed #ced4da; border-radius:12px; background:#f8f9fa;
        cursor:pointer; text-align:center; color:#6c757d; font-weight:400 !important;
        transition:border-color .15s, background .15s; overflow:hidden; margin-bottom:6px;
    }
    .prod-imagen:hover, .prod-imagen.arrastrando { border-color:#007bff; background:#f2f8ff; }
    .prod-imagen img { position:absolute; inset:0; width:100%; height:100%; object-fit:contain; padding:10px; background:#fff; }
    .prod-imagen .vacio i, .prod-imagen .vacio svg { font-size:2rem; margin:0 auto 6px; color:#adb5bd; display:block; }
    .prod-imagen .cambiar { position:absolute; right:8px; bottom:8px; font-size:.78rem; box-shadow:0 1px 4px rgba(0,0,0,.2); }
    .prod-imagen input[type=file] { display:none; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="prod-header">
            <div>
                <h1>Productos</h1>
                <div class="prod-ruta">
                    <i class="fas fa-store mr-1"></i>
                    @if($idservicio)
                        <a href="{{ url('/admin/categorias/listado/'.$idservicio) }}">{{ $restaurante ?: 'Restaurante' }}</a>
                        <i class="fas fa-chevron-right sep"></i>
                    @endif
                    <a href="{{ url('/admin/sub/categorias/listado/'.$idcategoria) }}">{{ $nomcategoria }}</a>
                    <i class="fas fa-chevron-right sep"></i>
                    <b class="text-dark">{{ $nomsubcategoria }}</b>
                </div>
            </div>
            <div>
                <a href="{{ url('/admin/sub/categorias/listado/'.$idcategoria) }}" class="btn btn-outline-secondary btn-sm mr-1">
                    <i class="fas fa-arrow-left mr-1"></i> Sub Categorías
                </a>
                <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nuevo Producto
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="prod-toolbar">
                    <span class="prod-hint"><i class="fas fa-arrows-alt-v mr-1"></i>Arrastra las filas para cambiar el orden en el menú</span>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="text" id="buscar-prod" class="form-control" placeholder="Buscar producto..." autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="tablaDatatable"></div>
            </div>
        </div>
    </div>
</section>

<!-- modal agregar -->
<div class="modal fade prod-modal" id="modalAgregar">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Nuevo Producto
                    <small><i class="fas fa-layer-group mr-1"></i>{{ $nomcategoria }} › {{ $nomsubcategoria }}</small>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formulario-nuevo" onsubmit="return false">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label>Imagen <span class="text-muted font-weight-normal">(opcional)</span></label>
                            <label class="prod-imagen" id="caja-imagen-nuevo" for="imagen-nuevo">
                                <div class="vacio">
                                    <i class="fas fa-image"></i>
                                    <div>Haz clic o arrastra una imagen</div>
                                    <small>PNG o JPG · recomendado 600 × 400 px</small>
                                </div>
                                <img alt="Vista previa" style="display:none">
                                <span class="cambiar btn btn-light btn-sm" style="display:none"><i class="fas fa-sync-alt mr-1"></i>Cambiar</span>
                                <input type="file" id="imagen-nuevo" accept="image/jpeg, image/jpg, image/png"/>
                            </label>

                            <div class="prod-seccion mt-2">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-imagen">
                                    <label class="custom-control-label" for="toggle-imagen">Mostrar imagen en la app</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="form-row">
                                <div class="form-group col-sm-8">
                                    <label for="nombre-nuevo">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="150" autocomplete="off" class="form-control" id="nombre-nuevo" placeholder="Ej. Combo 2 piezas">
                                </div>
                                <div class="form-group col-sm-4">
                                    <label for="precio-nuevo">Precio <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="number" min="0" step="0.01" class="form-control" id="precio-nuevo" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="con-contador" for="descripcion-nuevo">Descripción <span class="prod-contador" data-para="descripcion-nuevo">0 / 2000</span></label>
                                <textarea maxlength="2000" rows="3" class="form-control" id="descripcion-nuevo" placeholder="Qué incluye el producto"></textarea>
                            </div>

                            <div class="prod-seccion mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-nota">
                                    <label class="custom-control-label" for="toggle-nota">Pedir nota al cliente</label>
                                </div>
                                <p class="ayuda">El cliente podrá escribir una nota al agregar el producto (ej. sabor de bebida).</p>
                                <div class="mt-2" id="grupo-nota-nuevo" style="display:none">
                                    <label for="nota-nuevo">Indicación para el cliente <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="500" class="form-control" id="nota-nuevo" placeholder="Ej. Indica el sabor de tu bebida">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="nuevo()"><i class="fas fa-check mr-1"></i>Guardar</button>
            </div>
        </div>
    </div>
</div>


<!-- modal editar-->
<div class="modal fade prod-modal" id="modalEditar" >
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Editar Producto
                    <small><i class="fas fa-layer-group mr-1"></i>{{ $nomcategoria }} › {{ $nomsubcategoria }}</small>
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
                            <label class="prod-imagen" id="caja-imagen-editar" for="imagen-editar">
                                <div class="vacio">
                                    <i class="fas fa-image"></i>
                                    <div>Haz clic o arrastra una imagen</div>
                                    <small>PNG o JPG · recomendado 600 × 400 px</small>
                                </div>
                                <img alt="Vista previa" style="display:none">
                                <span class="cambiar btn btn-light btn-sm" style="display:none"><i class="fas fa-sync-alt mr-1"></i>Cambiar</span>
                                <input type="file" id="imagen-editar" accept="image/jpeg, image/jpg, image/png"/>
                            </label>

                            <div class="prod-seccion mt-2">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-imagen-editar">
                                    <label class="custom-control-label" for="toggle-imagen-editar">Mostrar imagen en la app</label>
                                </div>
                            </div>

                            <div class="prod-seccion mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-activo">
                                    <label class="custom-control-label" for="toggle-activo">Visible en el menú</label>
                                </div>
                                <p class="ayuda">Apágalo si el producto está agotado o ya no se vende.</p>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="form-row">
                                <div class="form-group col-sm-8">
                                    <label for="nombre-editar">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="150" autocomplete="off" class="form-control" id="nombre-editar" placeholder="Nombre">
                                </div>
                                <div class="form-group col-sm-4">
                                    <label for="precio-editar">Precio <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="number" min="0" step="0.01" class="form-control" id="precio-editar" placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="con-contador" for="descripcion-editar">Descripción <span class="prod-contador" data-para="descripcion-editar">0 / 2000</span></label>
                                <textarea maxlength="2000" rows="3" class="form-control" id="descripcion-editar" placeholder="Qué incluye el producto"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="select-mover">Sub categoría</label>
                                <select class="form-control" id="select-mover"></select>
                                <small class="text-muted">Cámbiala para mover el producto a otra sub categoría.</small>
                            </div>

                            <div class="prod-seccion mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggle-nota-editar">
                                    <label class="custom-control-label" for="toggle-nota-editar">Pedir nota al cliente</label>
                                </div>
                                <p class="ayuda">El cliente podrá escribir una nota al agregar el producto (ej. sabor de bebida).</p>
                                <div class="mt-2" id="grupo-nota-editar" style="display:none">
                                    <label for="nota-editar">Indicación para el cliente <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="500" class="form-control" id="nota-editar" placeholder="Ej. Indica el sabor de tu bebida">
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
            recargar();
        });
    </script>

    <script>

        // si el producto en edicion ya tiene imagen guardada
        var tieneImagenGuardada = false;

        function recargar(){
            var id = {{ $id }};
            var ruta = "{{ URL::to('/admin/productos/listado/tabla') }}/"+id;
            $('#tablaDatatable').load(ruta, filtrarProductos);
        }

        // filtro rapido por nombre o descripcion
        function filtrarProductos(){
            var texto = ($('#buscar-prod').val() || '').toLowerCase().trim();
            var visibles = 0;
            $('#tablecontents tr.row1').each(function(){
                var coincide = $(this).data('nombre').toString().indexOf(texto) !== -1;
                $(this).toggle(coincide);
                if(coincide) visibles++;
            });
            $('#sin-resultados').toggle(visibles === 0 && texto !== '');
        }

        $(document).on('input', '#buscar-prod', filtrarProductos);

        // ---- utilidades de los modales ----

        // muestra el campo de nota solo si se pide nota
        function sincronizarNota(toggleId, grupoId){
            $('#' + grupoId).toggle($('#' + toggleId).is(':checked'));
        }
        $(document).on('change', '#toggle-nota', function(){ sincronizarNota('toggle-nota', 'grupo-nota-nuevo'); });
        $(document).on('change', '#toggle-nota-editar', function(){ sincronizarNota('toggle-nota-editar', 'grupo-nota-editar'); });

        // contador de caracteres de la descripcion
        function actualizarContador(id){
            var largo = ($('#' + id).val() || '').length;
            $('.prod-contador[data-para="' + id + '"]').text(largo + ' / 2000');
        }
        $(document).on('input', '#descripcion-nuevo, #descripcion-editar', function(){ actualizarContador(this.id); });

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

        function previsualizar(input, cajaId, toggleId){
            if(!(input.files && input.files[0])) return;
            if(!imagenValida(input.files[0])){
                toastr.error(errorImagen(input.files[0]));
                input.value = '';
                return;
            }
            var lector = new FileReader();
            lector.onload = function(e){ mostrarImagen(cajaId, e.target.result); };
            lector.readAsDataURL(input.files[0]);
            // al subir una imagen lo normal es querer mostrarla
            $('#' + toggleId).prop('checked', true);
        }

        $(document).on('change', '#imagen-nuevo', function(){ previsualizar(this, 'caja-imagen-nuevo', 'toggle-imagen'); });
        $(document).on('change', '#imagen-editar', function(){ previsualizar(this, 'caja-imagen-editar', 'toggle-imagen-editar'); });

        // arrastrar y soltar imagen sobre la caja
        $(document).on('dragover dragenter', '.prod-imagen', function(e){
            e.preventDefault(); $(this).addClass('arrastrando');
        });
        $(document).on('dragleave dragend drop', '.prod-imagen', function(e){
            e.preventDefault(); $(this).removeClass('arrastrando');
        });
        $(document).on('drop', '.prod-imagen', function(e){
            var archivos = e.originalEvent.dataTransfer.files;
            if(!archivos.length) return;
            var input = $(this).find('input[type=file]')[0];
            input.files = archivos;
            $(input).trigger('change');
        });

        // valida el precio y devuelve un mensaje de error o null
        function errorPrecio(precio){
            if(precio === '') return 'Precio es requerido';
            if(!precio.match(/^[0-9]\d*(\.\d+)?$/)) return 'Precio debe ser un número (ej. 4.50)';
            if(parseFloat(precio) > 1000000) return 'Precio máximo 1 millón';
            return null;
        }

        // abrir modal
        function modalNuevo(){
            document.getElementById("formulario-nuevo").reset();
            mostrarImagen('caja-imagen-nuevo', null);
            sincronizarNota('toggle-nota', 'grupo-nota-nuevo');
            actualizarContador('descripcion-nuevo');
            $('#modalAgregar').modal('show');
        }

        $('#modalAgregar').on('shown.bs.modal', function(){ $('#nombre-nuevo').trigger('focus'); });

        //nuevo producto
        function nuevo(){

            var nombre = document.getElementById('nombre-nuevo').value.trim();
            var descripcion = document.getElementById('descripcion-nuevo').value;
            var precio = document.getElementById('precio-nuevo').value.trim();
            var imagen = document.getElementById('imagen-nuevo');
            var cbimagen = document.getElementById('toggle-imagen').checked;
            var cbnota = document.getElementById('toggle-nota').checked;
            var nota = document.getElementById('nota-nuevo').value.trim();

            var check_imagen = cbimagen ? 1 : 0;
            var check_nota = cbnota ? 1 : 0;

            if(nombre === '') {
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 150){
                toastr.error('Nombre máximo 150 caracteres');
                return;
            }

            var msgPrecio = errorPrecio(precio);
            if(msgPrecio){
                toastr.error(msgPrecio);
                return;
            }

            if(descripcion.length > 2000){
                toastr.error('Descripción máximo 2000 caracteres');
                return;
            }

            if(check_nota === 1 && nota === ''){
                toastr.error('Escribe la indicación de la nota para el cliente');
                return;
            }

            if(nota.length > 500){
                toastr.error('Nota máximo 500 caracteres');
                return;
            }

            var tieneArchivo = imagen.files && imagen.files[0];
            if(tieneArchivo){
                if (!imagenValida(imagen.files[0])){
                    toastr.error(errorImagen(imagen.files[0]));
                    return;
                }
            }else if(check_imagen === 1){
                toastr.error('Para mostrar imagen en la app primero sube una imagen');
                return;
            }

            openLoading();

            var formData = new FormData();
            formData.append('idsubcategoria', {{ $id }});
            formData.append('nombre', nombre);
            if(tieneArchivo){
                formData.append('imagen', imagen.files[0]);
            }
            formData.append('descripcion', descripcion);
            formData.append('precio', precio);
            formData.append('cbnota', check_nota);
            formData.append('cbimagen', check_imagen);
            formData.append('nota', nota);

            axios.post('/admin/productos/nuevo', formData, {
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
            tieneImagenGuardada = false;
            openLoading();

            axios.post('/admin/productos/informacion',{
                'id': id
            })
                .then((response) => {
                    closeLoading();
                    if(response.data.success === 1){

                        var producto = response.data.producto;

                        $('#modalEditar').modal('show');
                        $('#id-editar').val(id);
                        $('#nombre-editar').val(producto.nombre);
                        $('#descripcion-editar').val(producto.descripcion);
                        $('#precio-editar').val(producto.precio);
                        $('#nota-editar').val(producto.nota);

                        $("#toggle-activo").prop("checked", producto.activo !== 0);
                        $("#toggle-nota-editar").prop("checked", producto.utiliza_nota !== 0);
                        $("#toggle-imagen-editar").prop("checked", producto.utiliza_imagen !== 0);

                        sincronizarNota('toggle-nota-editar', 'grupo-nota-editar');
                        actualizarContador('descripcion-editar');

                        if(producto.imagen){
                            tieneImagenGuardada = true;
                            mostrarImagen('caja-imagen-editar', "{{ url('storage/imagenes') }}/" + producto.imagen);
                        }

                        var $select = $('#select-mover').empty();
                        $.each(response.data.arraysub, function( key, val ){
                            $select.append($('<option>', {
                                value: val.id,
                                text: val.nombre,
                                selected: producto.id_subcategorias == val.id
                            }));
                        });

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
            var nombre = document.getElementById('nombre-editar').value.trim();
            var descripcion = document.getElementById('descripcion-editar').value;
            var precio = document.getElementById('precio-editar').value.trim();
            var imagen = document.getElementById('imagen-editar');
            var cbimagen = document.getElementById('toggle-imagen-editar').checked;
            var cbnota = document.getElementById('toggle-nota-editar').checked;
            var cbactivo = document.getElementById('toggle-activo').checked;
            var nota = document.getElementById('nota-editar').value.trim();

            // para mover de sub categorias
            var idsubcate = document.getElementById('select-mover').value;

            var check_imagen = cbimagen ? 1 : 0;
            var check_nota = cbnota ? 1 : 0;
            var check_activo = cbactivo ? 1 : 0;

            if(idsubcate === '') {
                toastr.error('Sub categoría es requerida');
                return;
            }

            if(nombre === '') {
                toastr.error('Nombre es requerido');
                return;
            }

            if(nombre.length > 150){
                toastr.error('Nombre máximo 150 caracteres');
                return;
            }

            var msgPrecio = errorPrecio(precio);
            if(msgPrecio){
                toastr.error(msgPrecio);
                return;
            }

            if(descripcion.length > 2000){
                toastr.error('Descripción máximo 2000 caracteres');
                return;
            }

            if(check_nota === 1 && nota === ''){
                toastr.error('Escribe la indicación de la nota para el cliente');
                return;
            }

            if(nota.length > 500){
                toastr.error('Nota máximo 500 caracteres');
                return;
            }

            var tieneArchivo = imagen.files && imagen.files[0];
            if(tieneArchivo){
                if (!imagenValida(imagen.files[0])){
                    toastr.error(errorImagen(imagen.files[0]));
                    return;
                }
            }else if(check_imagen === 1 && !tieneImagenGuardada){
                toastr.error('Para mostrar imagen en la app primero sube una imagen');
                return;
            }

            openLoading();

            var formData = new FormData();
            formData.append('id', id);
            formData.append('nombre', nombre);
            if(tieneArchivo){
                formData.append('imagen', imagen.files[0]);
            }
            formData.append('descripcion', descripcion);
            formData.append('precio', precio);
            formData.append('cbnota', check_nota);
            formData.append('cbimagen', check_imagen);
            formData.append('cbactivo', check_activo);
            formData.append('nota', nota);
            formData.append('idsubcate', idsubcate);

            axios.post('/admin/productos/editar', formData, {
            })
                .then((response) => {
                    closeLoading();
                    if (response.data.success === 1) {
                        $('#modalEditar').modal('hide');
                        toastr.success('Actualizado correctamente');
                        recargar();
                    }
                    else if (response.data.success === 3) {
                        toastr.error('No se puede mostrar imagen si no hay una guardada');
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

    </script>
@stop
