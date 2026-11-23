@php
    $minimoPuntos = \App\Http\Controllers\Backend\Configuracion\ZonasController::MINIMO_PUNTOS;
    $resumen = [
        'todas' => $zonas->count(),
        'visible' => $zonas->where('activo', 1)->count(),
        'oculta' => $zonas->where('activo', '!=', 1)->count(),
        'cerrada' => $zonas->where('saturacion', 1)->count(),
        'sinpoligono' => $zonas->where('puntos', '<', $minimoPuntos)->count(),
        'sinrestaurante' => $zonas->whereNull('restaurante')->count(),
    ];
    $tarjetas = [
        'todas' => ['Todas', 'bg-info', 'fas fa-map'],
        'visible' => ['Visibles en la app', 'bg-success', 'fas fa-eye'],
        'oculta' => ['Ocultas', 'bg-secondary', 'fas fa-eye-slash'],
        'cerrada' => ['Cerradas ahora', 'bg-danger', 'fas fa-store-slash'],
        'sinpoligono' => ['Sin polígono', 'bg-warning', 'fas fa-draw-polygon'],
        'sinrestaurante' => ['Sin restaurante', 'bg-warning', 'fas fa-store'],
    ];
    if($porRestaurante){ unset($tarjetas['sinrestaurante']); }
@endphp

<div class="row">
    @foreach($tarjetas as $clave => [$texto, $color, $icono])
        <div class="col-6 col-md">
            <div class="info-box mb-3 filtro-zona {{ $clave === 'todas' ? 'activo' : '' }}" data-filtro="{{ $clave }}" onclick="filtrarZonas('{{ $clave }}')" title="Filtrar la tabla">
                <span class="info-box-icon {{ $color }} elevation-1"><i class="{{ $icono }}"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ $texto }}</span>
                    <span class="info-box-number">{{ $resumen[$clave] }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

<table id="tabla" class="table table-bordered table-striped table-hover" style="width: 100%">
    <thead>
    <tr>
        <th>Restaurante</th>
        <th>Zona</th>
        <th style="width: 80px">Mínimo</th>
        <th style="width: 170px">Horario</th>
        <th style="width: 90px">Polígono</th>
        <th style="width: 110px">Visible en la app</th>
        <th style="width: 200px">Cerrada temporalmente</th>
        <th style="width: 210px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($zonas as $dato)
        @php
            $estados = ['todas'];
            $estados[] = $dato->activo == 1 ? 'visible' : 'oculta';
            if($dato->saturacion == 1) $estados[] = 'cerrada';
            if($dato->puntos < $minimoPuntos) $estados[] = 'sinpoligono';
            if(!$dato->restaurante) $estados[] = 'sinrestaurante';
        @endphp
        <tr data-estados="{{ implode(' ', $estados) }}" data-servicio="{{ $dato->id_servicio }}">
            <td>{{ $dato->restaurante ?? 'Sin restaurante asignado' }}</td>
            <td data-order="{{ $dato->nombre }}">
                <b>{{ $dato->nombre }}</b> <small class="text-muted">#{{ $dato->id }}</small>
                @if($dato->descripcion)
                    <div class="small text-muted">{{ $dato->descripcion }}</div>
                @endif
            </td>
            <td data-order="{{ $dato->minimo }}">{{ $dato->minimo_texto }}</td>
            <td data-order="{{ $dato->hora_abierto_delivery }}">
                {{ $dato->hora_abierto_texto }} – {{ $dato->hora_cerrado_texto }}
                <div>
                    @if($dato->en_horario)
                        <span class="badge badge-light border text-success">En horario</span>
                    @else
                        <span class="badge badge-light border text-muted">Fuera de horario</span>
                    @endif
                    @if($dato->tiempo_extra > 0)
                        <span class="badge badge-light border" title="Minutos que se suman al tiempo de entrega">+{{ $dato->tiempo_extra }} min</span>
                    @endif
                </div>
            </td>
            <td data-order="{{ $dato->puntos }}">
                @if($dato->puntos >= $minimoPuntos)
                    @if($dato->areas > 1)
                        <span class="badge bg-info">{{ $dato->areas }} áreas</span>
                        <div class="small text-muted">{{ $dato->puntos }} puntos</div>
                    @else
                        <span class="badge bg-info">{{ $dato->puntos }} puntos</span>
                    @endif
                @else
                    <span class="badge bg-warning">Sin polígono</span>
                @endif
            </td>
            <td data-order="{{ $dato->activo }}">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="activo-{{ $dato->id }}"
                           {{ $dato->activo == 1 ? 'checked' : '' }}
                           onchange="cambiarVisible({{ $dato->id }}, this)">
                    <label class="custom-control-label" for="activo-{{ $dato->id }}">{{ $dato->activo == 1 ? 'Sí' : 'No' }}</label>
                </div>
            </td>
            <td data-order="{{ $dato->saturacion }}">
                <div class="custom-control custom-switch custom-switch-on-danger">
                    <input type="checkbox" class="custom-control-input" id="cerrada-{{ $dato->id }}"
                           {{ $dato->saturacion == 1 ? 'checked' : '' }}
                           onchange="cambiarCerrada({{ $dato->id }}, this)">
                    <label class="custom-control-label" for="cerrada-{{ $dato->id }}">{{ $dato->saturacion == 1 ? 'Cerrada' : 'Recibe órdenes' }}</label>
                </div>
                @if($dato->saturacion == 1 && $dato->mensaje_bloqueo)
                    <div class="small text-muted">{{ $dato->mensaje_bloqueo }}</div>
                @endif
                @if($dato->cierre_hoy)
                    <div class="mt-1" title="Mensaje para el cliente: {{ $dato->cierre_hoy->mensaje }}">
                        <span class="badge bg-danger"><i class="far fa-calendar-times"></i> Cerrada hoy por cierre programado</span>
                        <div class="small text-muted">Ver en "Cierres programados"</div>
                    </div>
                @endif
            </td>
            <td>
                <button type="button" class="btn btn-primary btn-xs" onclick="verInformacion({{ $dato->id }})">
                    <i class="fas fa-pencil-alt"></i>&nbsp; Editar
                </button>
                <button type="button" class="btn btn-success btn-xs" onclick="vistaPoligonos({{ $dato->id }})">
                    <i class="fas fa-draw-polygon"></i>&nbsp; Polígono
                </button>
                @if($dato->usos == 0)
                    <button type="button" class="btn btn-danger btn-xs" onclick="preguntaBorrarZona({{ $dato->id }}, this)" data-nombre="{{ $dato->nombre }} #{{ $dato->id }}">
                        <i class="fas fa-trash-alt"></i>&nbsp; Borrar
                    </button>
                @else
                    <span class="d-inline-block" tabindex="0" title="No se puede borrar: tiene {{ $dato->usos }} órdenes o direcciones de clientes">
                        <button type="button" class="btn btn-danger btn-xs" disabled style="pointer-events: none">
                            <i class="fas fa-lock"></i>&nbsp; Borrar
                        </button>
                    </span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('backend.partials.datatable_es')
<script>
    $(function () {
        // zonas agrupadas por el restaurante que las atiende (columna 0, oculta)
        iniciarTabla('#tabla', {
            "orderFixed": [[0, 'asc']],
            "order": [[1, 'asc']],
            "columnDefs": [
                { "visible": false, "targets": [0] },
                { "orderable": false, "targets": [7] }
            ],
            "drawCallback": function () {
                if (@json($porRestaurante)) return; // ya se esta dentro de un restaurante: sin filas de grupo
                var api = this.api(), filas = api.rows({ page: 'current' }).nodes(), anterior = null;
                var totales = {};
                api.column(0, { search: 'applied' }).data().each(function (r) { totales[r] = (totales[r] || 0) + 1; });

                api.column(0, { page: 'current' }).data().each(function (restaurante, i) {
                    if (restaurante === anterior) return;
                    anterior = restaurante;
                    var total = totales[restaurante] || 0;
                    var sinAsignar = restaurante === 'Sin restaurante asignado';
                    var idServicio = $(filas).eq(i).data('servicio');

                    var celda = $('<td colspan="7">').addClass(sinAsignar ? 'bg-warning' : 'bg-light')
                        .append($('<i class="fas fa-store mr-2">'))
                        .append($('<b>').text(restaurante))
                        .append($('<span class="text-muted ml-2">').text(total + (total === 1 ? ' zona' : ' zonas')));

                    // cerrar / abrir todas las zonas de este restaurante
                    if (idServicio) {
                        $('<span class="float-right">')
                            .append($('<button type="button" class="btn btn-danger btn-xs mr-1">')
                                .html('<i class="fas fa-store-slash"></i>&nbsp; Cerrar restaurante')
                                .on('click', function () { cerrarRestaurante(idServicio, restaurante); }))
                            .append($('<button type="button" class="btn btn-default btn-xs">')
                                .html('<i class="fas fa-store"></i>&nbsp; Abrir restaurante')
                                .on('click', function () { abrirRestaurante(idServicio, restaurante); }))
                            .appendTo(celda);
                    }

                    $('<tr class="grupo-restaurante">').append(celda).insertBefore($(filas).eq(i));
                });
            }
        });
        filtrarZonas(filtroZonas);
    });
</script>
