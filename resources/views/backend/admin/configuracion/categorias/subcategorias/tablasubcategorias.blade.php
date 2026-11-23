<style>
    .tabla-sub { margin-bottom:0; }
    .tabla-sub thead th {
        background:#f8f9fa; color:#495057; font-size:.78rem; text-transform:uppercase;
        letter-spacing:.04em; border-top:0; border-bottom:1px solid #dee2e6; white-space:nowrap;
    }
    .tabla-sub td { vertical-align:middle; }
    .tabla-sub tbody tr { transition:background-color .15s; }
    .tabla-sub tbody tr:hover { background:#f5f9ff; }
    .tabla-sub .ui-sortable-helper { background:#fff; box-shadow:0 4px 14px rgba(0,0,0,.15); display:table; }
    .sub-drag { cursor:move; color:#adb5bd; width:56px; white-space:nowrap; }
    .sub-drag .pos {
        display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
        border-radius:50%; background:#e9ecef; color:#495057; font-weight:600; font-size:.8rem; margin-left:6px;
    }
    .sub-nombre { font-weight:600; color:#343a40; }
    .sub-inactiva .sub-nombre { opacity:.55; }
    .sub-productos { font-size:.88rem; white-space:nowrap; }
    .sub-acciones { white-space:nowrap; text-align:right; }
    .sub-vacio { padding:56px 16px; text-align:center; color:#6c757d; }
    .sub-vacio .icono {
        width:64px; height:64px; border-radius:50%; background:#eef5ff; color:#007bff;
        display:inline-flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:12px;
    }
    .sub-vacio h5 { color:#343a40; font-weight:600; }
</style>

@if($lista->isEmpty())
    <div class="sub-vacio">
        <div class="icono"><i class="fas fa-layer-group"></i></div>
        <h5>Esta categoría aún no tiene sub categorías</h5>
        <p class="mb-3">Las sub categorías agrupan los productos dentro de la categoría (ej. "Combos de 2 piezas").</p>
        <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
            <i class="fas fa-plus mr-1"></i> Crear la primera
        </button>
    </div>
@else
<div class="table-responsive">
    <table id="table" class="table tabla-sub">
        <thead>
        <tr>
            <th>#</th>
            <th>Sub categoría</th>
            <th>Productos</th>
            <th>Estado</th>
            <th class="text-right">Opciones</th>
        </tr>
        </thead>
        <tbody id="tablecontents">
        @foreach($lista as $dato)
            <tr class="row1 {{ $dato->activo == 0 ? 'sub-inactiva' : '' }}" data-id="{{ $dato->id }}" data-nombre="{{ mb_strtolower($dato->nombre) }}" data-activos="{{ $dato->productos_activos }}">

                <td class="sub-drag" title="Arrastrar para ordenar">
                    <i class="fas fa-grip-vertical"></i><span class="pos">{{ $loop->iteration }}</span>
                </td>

                <td><span class="sub-nombre">{{ $dato->nombre }}</span></td>

                <td class="sub-productos">
                    @if($dato->total_productos == 0)
                        <span class="text-muted"><i class="fas fa-box-open mr-1"></i>Sin productos</span>
                    @else
                        <b>{{ $dato->total_productos }}</b> {{ $dato->total_productos == 1 ? 'producto' : 'productos' }}
                        <span class="text-muted">· {{ $dato->productos_activos }} {{ $dato->productos_activos == 1 ? 'activo' : 'activos' }}</span>
                    @endif
                </td>

                <td>
                    @if($dato->activo == 0)
                        <span class="badge badge-pill badge-secondary"><i class="fas fa-eye-slash mr-1"></i>Oculta</span>
                    @else
                        <span class="badge badge-pill badge-success"><i class="fas fa-check mr-1"></i>Visible</span>
                    @endif
                </td>

                <td class="sub-acciones">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="informacion({{ $dato->id }})" title="Editar sub categoría">
                        <i class="fas fa-pen"></i><span class="d-none d-md-inline ml-1">Editar</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="verProductos({{ $dato->id }})" title="Ver productos">
                        <i class="fas fa-hamburger"></i><span class="d-none d-md-inline ml-1">Productos</span>
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div id="sin-resultados" class="text-center text-muted py-4" style="display:none">
        No se encontraron sub categorías con ese nombre.
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        $( "#tablecontents" ).sortable({
            items: "tr.row1",
            handle: ".sub-drag",
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

            axios.post('/admin/sub/categorias/ordenar',  {
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
