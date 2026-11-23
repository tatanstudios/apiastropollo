<table id="tabla" class="table table-bordered table-striped table-hover" style="width: 100%">
    <thead>
    <tr>
        <th>Código</th>
        <th style="width: 200px">Usos</th>
        <th>Dónde vale y qué regala</th>
        <th style="width: 120px">Activo</th>
        <th style="width: 90px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($cupones as $dato)
        <tr>
            <td data-order="{{ $dato->texto_cupon }}">
                <code class="h6 mb-0">{{ $dato->texto_cupon }}</code>
                @if($dato->activo == 1 && $dato->agotado)
                    <div><span class="badge bg-warning">Agotado</span></div>
                @elseif($dato->activo == 1 && !$dato->premios->count())
                    <div><span class="badge bg-warning">Sin restaurantes</span></div>
                @endif
            </td>

            <td data-order="{{ $dato->porcentaje }}">
                {{ $dato->contador }} de {{ $dato->uso_limite }}
                <div class="progress progress-xs mt-1" title="{{ $dato->porcentaje }}% usado">
                    <div class="progress-bar {{ $dato->agotado ? 'bg-danger' : 'bg-success' }}" style="width: {{ $dato->porcentaje }}%"></div>
                </div>
                <small class="text-muted">{{ max(0, $dato->uso_limite - $dato->contador) }} disponibles</small>
            </td>

            <td>
                @forelse($dato->premios as $p)
                    <div>
                        <b>{{ $p->restaurante }}:</b> {{ $p->premio }}
                        @if(!$p->acepta)
                            <span class="badge badge-light border text-danger" title="Este restaurante tiene apagado 'Acepta cupones' (Restaurantes → Editar)">no acepta cupones</span>
                        @endif
                    </div>
                @empty
                    <span class="text-muted">Ningún restaurante</span>
                @endforelse
            </td>

            <td data-order="{{ $dato->activo }}">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="activo-{{ $dato->id }}"
                           {{ $dato->activo == 1 ? 'checked' : '' }}
                           onchange="cambiarActivo({{ $dato->id }}, this)">
                    <label class="custom-control-label" for="activo-{{ $dato->id }}">{{ $dato->activo == 1 ? 'Sí' : 'No' }}</label>
                </div>
            </td>

            <td>
                <button type="button" class="btn btn-primary btn-xs" onclick="abrirEditar({{ $dato->id }})">
                    <i class="fas fa-pencil-alt"></i>&nbsp; Editar
                </button>
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
            "columnDefs": [{ "orderable": false, "targets": [2, 4] }]
        });
    });
</script>
