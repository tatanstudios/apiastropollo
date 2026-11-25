<table id="tabla-numeros" class="table table-bordered table-striped" style="width: 100%">
    <thead>
    <tr>
        <th style="width: 60px">ID</th>
        <th>Restaurante</th>
        <th>WhatsApp</th>
        <th style="width: 110px">Enviados</th>
        <th style="width: 160px">Último envío</th>
    </tr>
    </thead>
    <tbody>
    @foreach($restaurantes as $r)
        <tr>
            <td>{{ $r->id }}</td>
            <td>{{ $r->nombre }}</td>
            <td>
                @if(filled($r->whatsapp))
                    <i class="fab fa-whatsapp text-success"></i> +{{ $r->whatsapp }}
                @else
                    <span class="badge bg-secondary">Sin número</span>
                @endif
            </td>
            <td><span class="badge bg-info">{{ $r->enviados }}</span></td>
            <td data-order="{{ $r->ultimo }}">{{ $r->ultimo ? date('d/m/Y h:i A', strtotime($r->ultimo)) : '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('backend.partials.datatable_es')
<script>
    $(function () {
        iniciarTabla('#tabla-numeros', { "order": [[1, "asc"]] });
    });
</script>
