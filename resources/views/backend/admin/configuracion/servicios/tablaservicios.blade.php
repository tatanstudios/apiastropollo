<table id="tabla" class="table table-bordered table-striped table-hover" style="width: 100%">
    <thead>
    <tr>
        <th>Restaurante</th>
        <th style="width: 200px">Hoy</th>
        <th>Zonas que atiende</th>
        <th style="width: 170px">Usuario de la app</th>
        <th style="width: 110px">Cupones</th>
        <th style="width: 250px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($servicios as $dato)
        <tr>
            <td data-order="{{ $dato->nombre }}">
                <b>{{ $dato->nombre }}</b>
            </td>

            <td data-order="{{ $dato->abierto_ahora && !$dato->cierre_hoy ? 0 : 1 }}">
                @if($dato->cierre_hoy)
                    <span class="badge bg-danger" title="Mensaje para el cliente: {{ $dato->cierre_hoy->mensaje }}">
                        <i class="far fa-calendar-times"></i> Cerrado hoy por cierre programado
                    </span>
                @elseif($dato->cerrado_hoy)
                    <span class="badge bg-secondary">No abre hoy</span>
                @elseif($dato->abierto_ahora)
                    <span class="badge bg-success">Abierto ahora</span>
                @else
                    <span class="badge bg-light border text-muted">Fuera de horario</span>
                @endif
                @if($dato->horario_hoy)
                    <div class="small text-muted mt-1">{{ $dato->horario_hoy }}</div>
                @endif
            </td>

            <td data-order="{{ count($dato->zonas) }}">
                @if(count($dato->zonas))
                    <a href="{{ route('index.zonas.restaurante', $dato->id) }}" title="Ver y administrar sus zonas">
                        {{ count($dato->zonas) }} {{ count($dato->zonas) === 1 ? 'zona' : 'zonas' }}
                    </a>
                    @if($dato->zonas_visibles < count($dato->zonas))
                        <span class="badge badge-light border text-muted">{{ count($dato->zonas) - $dato->zonas_visibles }} ocultas</span>
                    @endif
                    @if($dato->zonas_cerradas)
                        <span class="badge badge-light border text-danger">{{ $dato->zonas_cerradas }} cerradas</span>
                    @endif
                    <div class="small text-muted">{{ implode(', ', $dato->zonas) }}</div>
                @else
                    <span class="badge bg-warning">Sin zonas</span>
                    <div class="small"><a href="{{ route('index.zonas.restaurante', $dato->id) }}">Crear su primera zona</a></div>
                @endif
            </td>

            <td>
                @if($dato->usuarios->count() === 1)
                    {{ $dato->usuarios->first()->nombre }}
                    <div class="small text-muted">{{ $dato->usuarios->first()->usuario }}</div>
                @elseif($dato->usuarios->count() > 1)
                    <a href="{{ route('index.usuarios.restaurantes') }}" title="{{ $dato->usuarios->pluck('nombre')->implode(', ') }}">{{ $dato->usuarios->count() }} usuarios</a>
                    <div class="small text-muted text-truncate" style="max-width: 180px">{{ $dato->usuarios->pluck('nombre')->implode(', ') }}</div>
                @else
                    <span class="badge bg-warning">Sin usuario</span>
                    <div class="small"><a href="{{ route('index.usuarios.restaurantes', ['nuevo' => $dato->id]) }}">Crear usuario</a></div>
                @endif
            </td>

            <td data-order="{{ $dato->utiliza_cupon }}">
                @if($dato->utiliza_cupon == 1)
                    <span class="badge bg-success">Acepta</span>
                @else
                    <span class="badge bg-secondary">No acepta</span>
                @endif
                @if($dato->cupones)
                    <div class="small mt-1">
                        <a href="{{ route('index.cupones.listado') }}">{{ $dato->cupones }} {{ $dato->cupones === 1 ? 'cupón activo' : 'cupones activos' }}</a>
                    </div>
                @endif
            </td>

            <td>
                <button type="button" class="btn btn-primary btn-xs" onclick="abrirEditar({{ $dato->id }})">
                    <i class="fas fa-pencil-alt"></i>&nbsp; Editar
                </button>
                <a href="{{ route('index.zonas.restaurante', $dato->id) }}" class="btn btn-success btn-xs">
                    <i class="fas fa-map-marked-alt"></i>&nbsp; Zonas
                </a>
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-utensils"></i>&nbsp; Menú y promociones
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="{{ url('/admin/categorias/listado/' . $dato->id) }}"><i class="fas fa-list fa-fw mr-1"></i> Categorías y productos</a>
                        <a class="dropdown-item" href="{{ url('/admin/slider/listado/' . $dato->id) }}"><i class="far fa-images fa-fw mr-1"></i> Slider</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ url('/admin/productos/servicio/principales/' . $dato->id) }}"><i class="fas fa-fire fa-fw mr-1"></i> Productos populares</a>
                    </div>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('backend.partials.datatable_es')
<script>
    $(function () {
        iniciarTabla('#tabla', {
            "order": [[0, 'asc']],
            "columnDefs": [{ "orderable": false, "targets": [5] }]
        });
    });
</script>
