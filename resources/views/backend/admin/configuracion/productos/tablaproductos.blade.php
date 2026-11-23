<style>
    .tabla-prod { margin-bottom:0; }
    .tabla-prod thead th {
        background:#f8f9fa; color:#495057; font-size:.78rem; text-transform:uppercase;
        letter-spacing:.04em; border-top:0; border-bottom:1px solid #dee2e6; white-space:nowrap;
    }
    .tabla-prod td { vertical-align:middle; }
    .tabla-prod tbody tr { transition:background-color .15s; }
    .tabla-prod tbody tr:hover { background:#f5f9ff; }
    .tabla-prod .ui-sortable-helper { background:#fff; box-shadow:0 4px 14px rgba(0,0,0,.15); display:table; }
    .prod-drag { cursor:move; color:#adb5bd; width:56px; white-space:nowrap; }
    .prod-drag .pos {
        display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
        border-radius:50%; background:#e9ecef; color:#495057; font-weight:600; font-size:.8rem; margin-left:6px;
    }
    .prod-thumb {
        width:64px; height:48px; flex:0 0 64px; border-radius:8px; object-fit:contain; padding:2px;
        background:#f1f3f5; border:1px solid #e9ecef;
    }
    .prod-thumb-vacio { display:flex; align-items:center; justify-content:center; color:#ced4da; font-size:1.2rem; }
    .prod-nombre { font-weight:600; color:#343a40; }
    .prod-desc {
        color:#6c757d; font-size:.82rem; max-width:420px;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .prod-inactivo .prod-nombre, .prod-inactivo .prod-thumb, .prod-inactivo .prod-precio { opacity:.5; }
    .prod-precio { font-weight:600; white-space:nowrap; color:#212529; }
    .prod-extras .badge { font-weight:500; }
    .prod-acciones { white-space:nowrap; text-align:right; }
    .prod-vacio { padding:56px 16px; text-align:center; color:#6c757d; }
    .prod-vacio .icono {
        width:64px; height:64px; border-radius:50%; background:#fff4e6; color:#fd7e14;
        display:inline-flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:12px;
    }
    .prod-vacio h5 { color:#343a40; font-weight:600; }
</style>

@if($productos->isEmpty())
    <div class="prod-vacio">
        <div class="icono"><i class="fas fa-hamburger"></i></div>
        <h5>Esta sub categoría aún no tiene productos</h5>
        <p class="mb-3">Agrega los productos que verán los clientes en el menú.</p>
        <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
            <i class="fas fa-plus mr-1"></i> Agregar el primero
        </button>
    </div>
@else
<div class="table-responsive">
    <table id="table" class="table tabla-prod">
        <thead>
        <tr>
            <th>#</th>
            <th>Producto</th>
            <th>Precio</th>
            <th>Extras</th>
            <th>Estado</th>
            <th class="text-right">Opciones</th>
        </tr>
        </thead>
        <tbody id="tablecontents">
        @foreach($productos as $dato)
            <tr class="row1 {{ $dato->activo == 0 ? 'prod-inactivo' : '' }}" data-id="{{ $dato->id }}"
                data-nombre="{{ mb_strtolower($dato->nombre.' '.$dato->descripcion) }}"
                data-tiene-imagen="{{ $dato->imagen ? 1 : 0 }}">

                <td class="prod-drag" title="Arrastrar para ordenar">
                    <i class="fas fa-grip-vertical"></i><span class="pos">{{ $loop->iteration }}</span>
                </td>

                <td>
                    <div class="d-flex align-items-center">
                        @if($dato->imagen)
                            <img class="prod-thumb mr-3" alt="{{ $dato->nombre }}" src="{{ url('storage/imagenes/'.$dato->imagen) }}" loading="lazy" />
                        @else
                            <div class="prod-thumb prod-thumb-vacio mr-3" title="Sin imagen"><i class="fas fa-image"></i></div>
                        @endif
                        <div style="min-width:0">
                            <div class="prod-nombre">{{ $dato->nombre }}</div>
                            @if($dato->descripcion)
                                <div class="prod-desc" title="{{ $dato->descripcion }}">{{ $dato->descripcion }}</div>
                            @endif
                        </div>
                    </div>
                </td>

                <td class="prod-precio">$ {{ $dato->precio }}</td>

                <td class="prod-extras">
                    @if($dato->imagen && $dato->utiliza_imagen == 1)
                        <span class="badge badge-light border" title="La imagen se muestra en la app"><i class="fas fa-image mr-1 text-primary"></i>Imagen</span>
                    @elseif($dato->imagen)
                        <span class="badge badge-light border text-muted" title="Tiene imagen pero no se muestra en la app"><i class="fas fa-image mr-1"></i>Imagen oculta</span>
                    @endif
                    @if($dato->utiliza_nota == 1)
                        <span class="badge badge-light border" title="Indicación: {{ $dato->nota }}"><i class="fas fa-comment-dots mr-1 text-info"></i>Pide nota</span>
                    @endif
                    @if(!$dato->imagen && $dato->utiliza_nota != 1)
                        <span class="text-muted">—</span>
                    @endif
                </td>

                <td>
                    @if($dato->activo == 0)
                        <span class="badge badge-pill badge-secondary"><i class="fas fa-eye-slash mr-1"></i>Oculto</span>
                    @else
                        <span class="badge badge-pill badge-success"><i class="fas fa-check mr-1"></i>Visible</span>
                    @endif
                </td>

                <td class="prod-acciones">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="informacion({{ $dato->id }})" title="Editar producto">
                        <i class="fas fa-pen"></i><span class="d-none d-md-inline ml-1">Editar</span>
                    </button>
                    <a class="btn btn-primary btn-sm" href="{{ url('/admin/productos/modificadores/'.$dato->id) }}" title="Opciones que elige el cliente">
                        <i class="fas fa-sliders-h"></i><span class="d-none d-md-inline ml-1">Modificadores</span>
                        @if(($dato->total_modificadores ?? 0) > 0)
                            <span class="badge badge-light ml-1">{{ $dato->total_modificadores }}</span>
                        @endif
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div id="sin-resultados" class="text-center text-muted py-4" style="display:none">
        No se encontraron productos con ese nombre.
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        $( "#tablecontents" ).sortable({
            items: "tr.row1",
            handle: ".prod-drag",
            cursor: 'move',
            opacity: 0.8,
            helper: function(e, tr) {
                // mantener el ancho de las celdas al arrastrar
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function(index) {
                    $(this).width($originals.eq(index).outerWidth());
                });
                return $helper;
            },
            update: function() {
                sendOrderToServer();
            }
        });

        function sendOrderToServer() {

            var order = [];
            $('tr.row1').each(function(index,element) {
                order.push({
                    id: $(this).attr('data-id'),
                    posicion: index+1
                });
            });

            openLoading();

            axios.post('/admin/productos/ordenar',  {
                'order': order
            })
                .then((response) => {
                    closeLoading();
                    toastr.success('Actualizado correctamente');
                    recargar();
                })
                .catch((error) => {
                    closeLoading();
                    toastr.error('Error al actualizar');
                });
        }
    });

</script>
@endif
