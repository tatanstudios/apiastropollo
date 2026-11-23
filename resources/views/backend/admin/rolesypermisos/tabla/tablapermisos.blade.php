<table id="tabla" class="table table-bordered table-striped" style="width: 100%">
    <thead>
    <tr>
        <th>Nombre</th>
        <th>Usuario</th>
        <th>Rol</th>
        <th style="width: 90px">Estado</th>
        <th style="width: 110px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($usuarios as $dato)
        <tr>
            <td>{{ $dato->nombre }}</td>
            <td>{{ $dato->usuario }}</td>
            <td>
                @forelse($dato->roles as $rol)
                    <span class="badge bg-info">{{ $rol->name }}</span>
                @empty
                    <span class="badge bg-warning">Sin rol</span>
                @endforelse
            </td>
            <td>
                @if($dato->activo == 0)
                    <span class="badge bg-danger">Inactivo</span>
                @else
                    <span class="badge bg-success">Activo</span>
                @endif
            </td>
            <td>
                <button type="button" class="btn btn-primary btn-xs" onclick="verInformacion({{ $dato->id }})">
                    <i class="fas fa-pencil-alt" title="Editar"></i>&nbsp; Editar
                </button>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('backend.partials.datatable_es')
<script>
    $(function () {
        iniciarTablaRoles('#tabla', {
            "order": [[0, 'asc']],
            "columnDefs": [{ "orderable": false, "targets": [4] }]
        });
    });
</script>
