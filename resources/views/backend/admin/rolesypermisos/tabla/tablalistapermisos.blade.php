<table id="tabla" class="table table-bordered table-striped" style="width: 100%">
    <thead>
    <tr>
        <th style="width: 60px">ID</th>
        <th>Permiso</th>
        <th>Descripción</th>
        <th style="width: 90px">Roles</th>
        <th style="width: 130px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($permisos as $ll)
        <tr>
            <td>{{ $ll->id }}</td>
            <td>{{ $ll->name }}</td>
            <td>{{ $ll->description }}</td>
            <td><span class="badge bg-info">{{ $ll->roles_count }}</span></td>
            <td>
                @if($ll->name !== \App\Http\Controllers\Backend\Roles\PermisoController::PERMISO_ROLES)
                    <button type="button" class="btn btn-danger btn-xs" onclick="modalBorrar({{ $ll->id }}, @js($ll->name))">
                        <i class="fas fa-trash-alt" title="Eliminar Global"></i>&nbsp; Eliminar
                    </button>
                @else
                    <span class="badge bg-secondary">Protegido</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('backend.partials.datatable_es')
<script>
    $(function () {
        iniciarTablaRoles('#tabla', {
            "order": [[1, 'asc']],
            "columnDefs": [{ "orderable": false, "targets": [4] }]
        });
    });
</script>
