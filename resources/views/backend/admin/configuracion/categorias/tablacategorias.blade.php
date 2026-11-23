<style>
    .tabla-categorias { margin-bottom:0; }
    .tabla-categorias thead th {
        background:#f8f9fa; color:#495057; font-size:.78rem; text-transform:uppercase;
        letter-spacing:.04em; border-top:0; border-bottom:1px solid #dee2e6; white-space:nowrap;
    }
    .tabla-categorias td { vertical-align:middle; }
    .tabla-categorias tbody tr { transition:background-color .15s; }
    .tabla-categorias tbody tr:hover { background:#f5f9ff; }
    .tabla-categorias .ui-sortable-helper { background:#fff; box-shadow:0 4px 14px rgba(0,0,0,.15); display:table; }
    .cat-drag { cursor:move; color:#adb5bd; width:56px; white-space:nowrap; }
    .cat-drag .pos {
        display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
        border-radius:50%; background:#e9ecef; color:#495057; font-weight:600; font-size:.8rem; margin-left:6px;
    }
    .cat-thumb {
        width:56px; height:56px; object-fit:contain; border-radius:10px;
        background:#fff8ef; border:1px solid #f1e3d0; padding:4px;
    }
    .cat-nombre { font-weight:600; color:#343a40; }
    .cat-inactiva .cat-nombre, .cat-inactiva .cat-thumb { opacity:.5; }
    .cat-horario { white-space:nowrap; font-size:.9rem; }
    .cat-horario .text-muted { font-size:.8rem; }
    .cat-acciones { white-space:nowrap; text-align:right; }
</style>

<div class="table-responsive">
    <table id="table" class="table tabla-categorias">
        <thead>
        <tr>
            <th>#</th>
            <th>Categoría</th>
            <th>Estado</th>
            <th>Horario</th>
            <th class="text-right">Opciones</th>
        </tr>
        </thead>
        <tbody id="tablecontents">
        @forelse($categorias as $dato)
            <tr class="row1 {{ $dato->activo == 0 ? 'cat-inactiva' : '' }}" data-id="{{ $dato->id }}" data-nombre="{{ mb_strtolower($dato->nombre) }}">

                <td class="cat-drag" title="Arrastrar para ordenar">
                    <i class="fas fa-grip-vertical"></i><span class="pos">{{ $loop->iteration }}</span>
                </td>

                <td>
                    <div class="d-flex align-items-center">
                        <img class="cat-thumb mr-3" alt="{{ $dato->nombre }}" src="{{ url('storage/imagenes/'.$dato->imagen) }}" loading="lazy" />
                        <span class="cat-nombre">{{ $dato->nombre }}</span>
                    </div>
                </td>

                <td>
                    @if($dato->activo == 0)
                        <span class="badge badge-pill badge-secondary"><i class="fas fa-eye-slash mr-1"></i>Oculta</span>
                    @else
                        <span class="badge badge-pill badge-success"><i class="fas fa-check mr-1"></i>Visible</span>
                    @endif
                </td>

                <td class="cat-horario">
                    @if($dato->usa_horario == 0)
                        <span class="text-muted"><i class="far fa-clock mr-1"></i>Siempre disponible</span>
                    @else
                        <span class="badge badge-pill badge-info mb-1"><i class="far fa-clock mr-1"></i>Horario propio</span><br>
                        {{ $dato->hora_abre }} – {{ $dato->hora_cierra }}
                    @endif
                </td>

                <td class="cat-acciones">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="informacion({{ $dato->id }})" title="Editar categoría">
                        <i class="fas fa-pen"></i><span class="d-none d-md-inline ml-1">Editar</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="verSubCategorias({{ $dato->id }})" title="Ver sub categorías">
                        <i class="fas fa-layer-group"></i><span class="d-none d-md-inline ml-1">Sub Categorías</span>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-5">
                    <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                    Aún no hay categorías. Crea la primera con <b>Nueva Categoría</b>.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div id="sin-resultados" class="text-center text-muted py-4" style="display:none">
        No se encontraron categorías con ese nombre.
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

        $( "#tablecontents" ).sortable({
            items: "tr.row1",
            handle: ".cat-drag",
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

            axios.post('/admin/categorias/ordenar',  {
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
