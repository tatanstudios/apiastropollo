<table id="tabla" class="table table-bordered table-striped" style="width: 100%">
    <thead>
    <tr>
        <th style="width: 60px">ID</th>
        <th>Rol</th>
        <th style="width: 100px">Permisos</th>
        <th style="width: 100px">Usuarios</th>
        <th style="width: 340px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($roles as $rol)
        <tr>
            <td>{{ $rol->id }}</td>
            <td>{{ $rol->name }}</td>
            <td><span class="badge bg-info">{{ $rol->permissions_count }}</span></td>
            <td><span class="badge bg-secondary">{{ $rol->users_count }}</span></td>
            <td>
                <button type="button" class="btn btn-success btn-xs" onclick="verInformacion({{ $rol->id }})">
                    <i class="fas fa-key" title="Permisos"></i>&nbsp; Permisos
                </button>

                <button type="button" class="btn btn-primary btn-xs" onclick="modalEditar({{ $rol->id }}, @js($rol->name))">
                    <i class="fas fa-pencil-alt" title="Editar"></i>&nbsp; Editar
                </button>

                <button type="button" class="btn btn-danger btn-xs" onclick="modalBorrar({{ $rol->id }}, @js($rol->name), {{ $rol->users_count }})">
                    <i class="fas fa-trash-alt" title="Eliminar"></i>&nbsp; Eliminar
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
            "columnDefs": [{ "orderable": false, "targets": [4] }]
        });
    });
</script>
