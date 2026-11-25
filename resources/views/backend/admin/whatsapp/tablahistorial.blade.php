<table id="tabla-historial" class="table table-bordered table-striped" style="width: 100%">
    <thead>
    <tr>
        <th style="width: 150px">Fecha</th>
        @if($ver === 'envios')
            <th>Restaurante</th>
            <th style="width: 90px">Orden</th>
        @else
            <th style="width: 110px">Acción</th>
            <th style="width: 90px">Cantidad</th>
            <th>Usuario</th>
            <th>Nota</th>
        @endif
        <th style="width: 90px">Saldo antes</th>
        <th style="width: 90px">Saldo después</th>
    </tr>
    </thead>
    <tbody>
    @foreach($movimientos as $m)
        <tr>
            <td data-order="{{ $m->id }}">{{ date('d/m/Y h:i A', strtotime($m->fecha)) }}</td>
            @if($ver === 'envios')
                <td>{{ $m->restaurante ?? '—' }}</td>
                <td>#{{ $m->id_orden }}</td>
            @else
                <td>
                    @if($m->tipo === 'agregar')
                        <span class="badge bg-success">Agregó</span>
                    @else
                        <span class="badge bg-danger">Quitó</span>
                    @endif
                </td>
                <td>{{ $m->tipo === 'agregar' ? '+' : '-' }}{{ $m->cantidad }}</td>
                <td>{{ $m->administrador ?? '—' }}</td>
                <td>{{ $m->nota }}</td>
            @endif
            <td>{{ $m->saldo_anterior }}</td>
            <td>{{ $m->saldo_nuevo }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('backend.partials.datatable_es')
<script>
    $(function () {
        iniciarTabla('#tabla-historial', { "order": [[0, "desc"]] });
    });
</script>
