<style>
    .tabla-pop { margin-bottom:0; }
    .tabla-pop thead th {
        background:#f8f9fa; color:#495057; font-size:.78rem; text-transform:uppercase;
        letter-spacing:.04em; border-top:0; border-bottom:1px solid #dee2e6; white-space:nowrap;
    }
    .tabla-pop td { vertical-align:middle; }
    .tabla-pop tbody tr { transition:background-color .15s; }
    .tabla-pop tbody tr:hover { background:#f5f9ff; }
    .tabla-pop .ui-sortable-helper { background:#fff; box-shadow:0 4px 14px rgba(0,0,0,.15); display:table; }
    .pop-drag { cursor:move; color:#adb5bd; width:56px; white-space:nowrap; }
    .pop-drag .pos {
        display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
        border-radius:50%; background:#fff4e6; color:#d9480f; font-weight:600; font-size:.8rem; margin-left:6px;
    }
    .pop-thumb {
        width:64px; height:48px; flex:0 0 64px; border-radius:8px; object-fit:contain; padding:2px;
        background:#f1f3f5; border:1px solid #e9ecef;
    }
    .pop-thumb-vacio { display:flex; align-items:center; justify-content:center; color:#ced4da; font-size:1.2rem; }
    .pop-nombre { font-weight:600; color:#343a40; }
    .pop-ruta { color:#6c757d; font-size:.8rem; }
    .pop-no-visible .pop-thumb, .pop-no-visible .pop-nombre, .pop-no-visible .pop-precio { opacity:.5; }
    .pop-precio { font-weight:600; white-space:nowrap; }
    .pop-motivo { color:#856404; font-size:.82rem; }
    .pop-acciones { white-space:nowrap; text-align:right; }
    .pop-vacio { padding:56px 16px; text-align:center; color:#6c757d; }
    .pop-vacio .icono {
        width:64px; height:64px; border-radius:50%; background:#fff4e6; color:#fd7e14;
        display:inline-flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:12px;
    }
    .pop-vacio h5 { color:#343a40; font-weight:600; }
</style>

@if($listado->isEmpty())
    <div class="pop-vacio">
        <div class="icono"><i class="fas fa-fire"></i></div>
        <h5>Aún no hay productos populares</h5>
        <p class="mb-3">Aparecen destacados en el inicio de la app. Sin populares, la app no muestra ese espacio.</p>
        <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
            <i class="fas fa-plus mr-1"></i> Agregar el primero
        </button>
    </div>
@else
<div class="table-responsive">
    <table id="table" class="table tabla-pop">
        <thead>
        <tr>
            <th>#</th>
            <th>Producto</th>
            <th>Precio</th>
            <th>En la app</th>
            <th class="text-right">Opciones</th>
        </tr>
        </thead>
        <tbody id="tablecontents">
        @foreach($listado as $dato)
            <tr class="row1 {{ $dato->motivo ? 'pop-no-visible' : '' }}" data-id="{{ $dato->id }}" data-producto="{{ $dato->id_productos }}">

                <td class="pop-drag" title="Arrastrar para ordenar">
                    <i class="fas fa-grip-vertical"></i><span class="pos">{{ $loop->iteration }}</span>
                </td>

                <td>
                    <div class="d-flex align-items-center">
                        @if($dato->imagen)
                            <img class="pop-thumb mr-3" alt="{{ $dato->nombre }}" src="{{ url('storage/imagenes/'.$dato->imagen) }}" loading="lazy" />
                        @else
                            <div class="pop-thumb pop-thumb-vacio mr-3"><i class="fas fa-image"></i></div>
                        @endif
                        <div style="min-width:0">
                            <div class="pop-nombre">{{ $dato->nombre ?? 'Producto borrado' }}</div>
                            @if($dato->categoria)
                                <div class="pop-ruta">{{ $dato->categoria }} › {{ $dato->subcategoria }}</div>
                            @endif
                        </div>
                    </div>
                </td>

                <td class="pop-precio">{{ $dato->nombre ? $dato->precio : '—' }}</td>

                <td>
                    @if($dato->motivo)
                        <span class="badge badge-pill badge-secondary"><i class="fas fa-eye-slash mr-1"></i>No se muestra</span>
                        <div class="pop-motivo mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>{{ $dato->motivo }}</div>
                    @else
                        <span class="badge badge-pill badge-success"><i class="fas fa-check mr-1"></i>Visible</span>
                    @endif
                </td>

                <td class="pop-acciones">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="borrarPrincipal({{ $dato->id }}, @json($dato->nombre ?? 'Producto borrado'))" title="Quitar de populares">
                        <i class="fas fa-times"></i><span class="d-none d-md-inline ml-1">Quitar</span>
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        $( "#tablecontents" ).sortable({
            items: "tr.row1",
            handle: ".pop-drag",
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

            axios.post('/admin/productos/servicio/principales/ordenar',  {
                'order': order
            })
                .then((response) => {
                    closeLoading();
                    toastr.success('Orden actualizado');
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
