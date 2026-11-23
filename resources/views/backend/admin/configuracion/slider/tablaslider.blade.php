<style>
    .tabla-slider { margin-bottom:0; }
    .tabla-slider thead th {
        background:#f8f9fa; color:#495057; font-size:.78rem; text-transform:uppercase;
        letter-spacing:.04em; border-top:0; border-bottom:1px solid #dee2e6; white-space:nowrap;
    }
    .tabla-slider td { vertical-align:middle; }
    .tabla-slider tbody tr { transition:background-color .15s; }
    .tabla-slider tbody tr:hover { background:#f5f9ff; }
    .tabla-slider .ui-sortable-helper { background:#fff; box-shadow:0 4px 14px rgba(0,0,0,.15); display:table; }
    .sl-drag { cursor:move; color:#adb5bd; width:56px; white-space:nowrap; }
    .sl-drag .pos {
        display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px;
        border-radius:50%; background:#e9ecef; color:#495057; font-weight:600; font-size:.8rem; margin-left:6px;
    }
    .sl-thumb {
        width:150px; height:84px; flex:0 0 150px; border-radius:8px; object-fit:cover;
        background:#f1f3f5; border:1px solid #e9ecef; cursor:zoom-in;
    }
    .sl-nombre { font-weight:600; color:#343a40; }
    .sl-inactivo .sl-thumb, .sl-inactivo .sl-nombre { opacity:.5; }
    .sl-detalle { font-size:.85rem; white-space:nowrap; }
    .sl-acciones { white-space:nowrap; text-align:right; }
    .sl-vacio { padding:56px 16px; text-align:center; color:#6c757d; }
    .sl-vacio .icono {
        width:64px; height:64px; border-radius:50%; background:#eef5ff; color:#007bff;
        display:inline-flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:12px;
    }
    .sl-vacio h5 { color:#343a40; font-weight:600; }
</style>

@if($slider->isEmpty())
    <div class="sl-vacio">
        <div class="icono"><i class="fas fa-images"></i></div>
        <h5>Este restaurante aún no tiene banners</h5>
        <p class="mb-3">Los banners aparecen arriba del menú en la app. Sin banners, la app no muestra ese espacio.</p>
        <button type="button" onclick="modalNuevo()" class="btn btn-success btn-sm">
            <i class="fas fa-plus mr-1"></i> Crear el primero
        </button>
    </div>
@else
<div class="table-responsive">
    <table id="table" class="table tabla-slider">
        <thead>
        <tr>
            <th>#</th>
            <th>Banner</th>
            <th>Al tocarlo</th>
            <th>Horario</th>
            <th>Estado</th>
            <th class="text-right">Opciones</th>
        </tr>
        </thead>
        <tbody id="tablecontents" data-total="{{ $slider->count() }}">
        @foreach($slider as $dato)
            <tr class="row1 {{ $dato->activo == 0 ? 'sl-inactivo' : '' }}" data-id="{{ $dato->id }}">

                <td class="sl-drag" title="Arrastrar para ordenar">
                    <i class="fas fa-grip-vertical"></i><span class="pos">{{ $loop->iteration }}</span>
                </td>

                <td>
                    <div class="d-flex align-items-center">
                        <img class="sl-thumb mr-3" alt="{{ $dato->nombre }}" src="{{ url('storage/imagenes/'.$dato->imagen) }}"
                             loading="lazy" onclick="verImagen(this.src)" title="Ver imagen completa" />
                        <span class="sl-nombre">{{ $dato->nombre ?: 'Sin descripción' }}</span>
                    </div>
                </td>

                <td class="sl-detalle">
                    @if($dato->redireccionamiento == 1 && $dato->producto)
                        <i class="fas fa-external-link-alt text-primary mr-1"></i>Abre <b>{{ $dato->producto }}</b>
                        @if($dato->producto_activo === 0)
                            <div class="small" style="color:#856404"><i class="fas fa-exclamation-triangle mr-1"></i>El producto está oculto</div>
                        @endif
                    @elseif($dato->redireccionamiento == 1)
                        <span style="color:#856404"><i class="fas fa-exclamation-triangle mr-1"></i>Producto no encontrado</span>
                    @else
                        <span class="text-muted"><i class="far fa-image mr-1"></i>Solo imagen</span>
                    @endif
                </td>

                <td class="sl-detalle">
                    @if($dato->usa_horario == 1)
                        <span class="badge badge-pill badge-info mb-1"><i class="far fa-clock mr-1"></i>Horario propio</span><br>
                        {{ $dato->hora_abre }} – {{ $dato->hora_cierra }}
                    @else
                        <span class="text-muted"><i class="far fa-clock mr-1"></i>Todo el día</span>
                    @endif
                </td>

                <td>
                    @if($dato->activo == 0)
                        <span class="badge badge-pill badge-secondary"><i class="fas fa-eye-slash mr-1"></i>Oculto</span>
                    @else
                        <span class="badge badge-pill badge-success"><i class="fas fa-check mr-1"></i>Visible</span>
                    @endif
                </td>

                <td class="sl-acciones">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="informacion({{ $dato->id }})" title="Editar banner">
                        <i class="fas fa-pen"></i><span class="d-none d-md-inline ml-1">Editar</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="modalBorrar({{ $dato->id }})" title="Borrar banner">
                        <i class="fas fa-trash-alt"></i>
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
            handle: ".sl-drag",
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

            axios.post('/admin/slider/ordenar',  {
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
