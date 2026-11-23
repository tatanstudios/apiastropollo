<table id="tabla" class="table table-bordered table-striped" style="width: 100%">
    <thead>
    <tr>
        <th style="width: 60px">ID</th>
        <th>Permiso</th>
        <th>Descripción</th>
        <th style="width: 120px">Opciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($permisos as $permiso)
        <tr>
            <td>{{ $permiso->id }}</td>
            <td>{{ $permiso->name }}</td>
            <td>{{ $permiso->description }}</td>
            <td>
                <button type="button" class="btn btn-danger btn-xs" onclick="modalBorrar({{ $permiso->id }}, @js($permiso->name))">
                    <i class="fas fa-trash-alt" title="Quitar"></i>&nbsp; Quitar
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
            "order": [[1, 'asc']],
            "columnDefs": [{ "orderable": false, "targets": [3] }]
        });
    });
</script>
