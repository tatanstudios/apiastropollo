<table id="tabla" class="table table-bordered table-striped table-hover" style="width: 100%">
    <thead>
    <tr>
        <th>Restaurante</th>
        <th>Nombre</th>
        <th>Usuario</th>
        <th style="width: 190px">Último ingreso a la app</th>
        <th style="width: 100px">Estado</th>
        <th style="width: 170px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($lista as $dato)
        <tr class="{{ $dato->bloqueado ? 'text-muted' : '' }}" data-bloqueado="{{ $dato->bloqueado }}" data-servicio="{{ $dato->id_servicios }}">
            <td>{{ $dato->restaurante }}</td>
            <td><b>{{ $dato->nombre }}</b></td>
            <td><code>{{ $dato->usuario }}</code></td>
            <td data-order="{{ $dato->fecha_entroapp ? strtotime($dato->fecha_entroapp) : 0 }}">
                @if($dato->fecha_entroapp)
                    {{ $dato->ingreso_hace }}
                    @if(!$dato->bloqueado && $dato->ingreso_dias >= 30)
                        <span class="badge badge-light border text-danger" title="Hace más de un mes que no entra a la app">sin uso</span>
                    @endif
                    <div class="small text-muted">{{ $dato->ingreso_texto }}</div>
                @else
                    <span class="text-muted">Nunca ha entrado</span>
                @endif
            </td>
            <td data-order="{{ $dato->bloqueado }}">
                @if($dato->bloqueado)
                    <span class="badge bg-secondary">Bloqueado</span>
                @else
                    <span class="badge bg-success">Activo</span>
                @endif
            </td>
            <td>
                <button type="button" class="btn btn-primary btn-xs" onclick="abrirEditar({{ $dato->id }})">
                    <i class="fas fa-pencil-alt"></i>&nbsp; Editar
                </button>
                @if($dato->bloqueado)
                    <button type="button" class="btn btn-default btn-xs" onclick="cambiarBloqueo({{ $dato->id }}, 0, this)" data-nombre="{{ $dato->usuario }}">
                        <i class="fas fa-unlock"></i>&nbsp; Desbloquear
                    </button>
                @else
                    <button type="button" class="btn btn-danger btn-xs" onclick="cambiarBloqueo({{ $dato->id }}, 1, this)" data-nombre="{{ $dato->usuario }}">
                        <i class="fas fa-lock"></i>&nbsp; Bloquear
                    </button>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('backend.partials.datatable_es')
<script>
    $(function () {
        // usuarios agrupados por restaurante (columna 0, oculta)
        iniciarTabla('#tabla', {
            "pageLength": 50,
            "orderFixed": [[0, 'asc']],
            "order": [[4, 'asc'], [1, 'asc']],
            "columnDefs": [
                { "visible": false, "targets": [0] },
                { "orderable": false, "targets": [5] }
            ],
            "drawCallback": function () {
                var api = this.api(), filas = api.rows({ page: 'current' }).nodes(), anterior = null;
                var activos = {}, bloqueados = {};
                api.rows({ search: 'applied' }).every(function () {
                    var r = this.data()[0], cuenta = this.node().getAttribute('data-bloqueado') === '1' ? bloqueados : activos;
                    cuenta[r] = (cuenta[r] || 0) + 1;
                });

                api.column(0, { page: 'current' }).data().each(function (restaurante, i) {
                    if (restaurante === anterior) return;
                    anterior = restaurante;
                    var nActivos = activos[restaurante] || 0, nBloqueados = bloqueados[restaurante] || 0;
                    var idServicio = $(filas).eq(i).data('servicio');

                    var celda = $('<td colspan="5" class="bg-light">')
                        .append($('<i class="fas fa-store mr-2">'))
                        .append($('<b>').text(restaurante))
                        .append(nActivos
                            ? $('<span class="text-muted ml-2">').text(nActivos + (nActivos === 1 ? ' activo' : ' activos'))
                            : $('<span class="badge bg-warning ml-2">').text('Sin usuario activo'));
                    if (nBloqueados) {
                        celda.append($('<span class="text-muted ml-2">').text('· ' + nBloqueados + (nBloqueados === 1 ? ' bloqueado' : ' bloqueados')));
                    }

                    $('<span class="float-right">')
                        .append($('<button type="button" class="btn btn-success btn-xs">')
                            .html('<i class="fas fa-plus"></i>&nbsp; Agregar usuario')
                            .on('click', function () { abrirNuevo(idServicio); }))
                        .appendTo(celda);

                    $('<tr class="grupo-restaurante">').append(celda).insertBefore($(filas).eq(i));
                });
            }
        });
    });
</script>
