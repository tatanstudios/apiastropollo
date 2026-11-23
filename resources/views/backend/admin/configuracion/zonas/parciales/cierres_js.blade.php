{{-- cerrar/abrir varias zonas y cierres programados; la pagina que lo incluye define recargar() y restauranteCierres --}}
<script>
        // ---- cerrar / abrir varias zonas a la vez ----

        function cerrarVarias(datos, titulo){
            Swal.fire({
                title: titulo,
                text: 'Mensaje que verá el cliente:',
                input: 'text',
                inputPlaceholder: 'Ej.: Cerrado por inventario',
                inputAttributes: { maxlength: 100 },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Cerrar',
                cancelButtonText: 'Cancelar',
                inputValidator: function (valor) {
                    if (!valor || !valor.trim()) return 'Escribe el mensaje';
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    enviarVarias($.extend({ valor: 1, mensaje: result.value.trim() }, datos));
                }
            });
        }

        function abrirVarias(datos, titulo){
            Swal.fire({
                title: titulo,
                text: 'Las zonas vuelven a recibir órdenes (las cerradas por fecha siguen cerradas hasta que pase la fecha).',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Abrir',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (result.isConfirmed) { enviarVarias($.extend({ valor: 0 }, datos)); }
            });
        }

        function enviarVarias(datos){
            openLoading();
            axios.post('/admin/zonas/cerrar-varias', datos)
                .then(function (response) {
                    closeLoading();
                    var r = response.data;
                    if (r.success === 1) {
                        toastr.success((datos.valor === 1 ? 'Zonas cerradas: ' : 'Zonas abiertas: ') + r.total);
                        recargar();
                    } else if (r.success === 3) {
                        toastr.error('Escribe el mensaje de cierre');
                    } else {
                        toastr.error('Error al actualizar');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        function cerrarTodo(){ cerrarVarias({ alcance: 'todas' }, 'Cerrar TODAS las zonas'); }
        function abrirTodo(){ abrirVarias({ alcance: 'todas' }, '¿Abrir todas las zonas?'); }

        function cerrarRestaurante(idServicio, nombre){
            cerrarVarias({ alcance: 'restaurante', servicio: idServicio }, 'Cerrar ' + nombre);
        }
        function abrirRestaurante(idServicio, nombre){
            abrirVarias({ alcance: 'restaurante', servicio: idServicio }, '¿Abrir ' + nombre + '?');
        }

        // ---- cierres programados por fecha ----

        function abrirModalCierres(){
            document.getElementById('formulario-cierre').reset();
            // dentro de un restaurante: ese restaurante ya elegido
            if (restauranteCierres) { $('#cierre-servicio').val(restauranteCierres); }
            cargarCierres();
            $('#modalCierres').modal('show');
        }

        function cargarCierres(){
            var cuerpo = $('#lista-cierres').html('<tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>');

            axios.get('/admin/zonas/cierres')
                .then(function (response) {
                    cuerpo.empty();
                    // dentro de un restaurante solo sus cierres y los de todos los restaurantes
                    var lista = response.data.cierres.filter(function (c) {
                        return !restauranteCierres || c.id_servicio === null || c.id_servicio === restauranteCierres;
                    });
                    if (!lista.length) {
                        cuerpo.html('<tr><td colspan="5" class="text-center text-muted">No hay cierres programados</td></tr>');
                        return;
                    }

                    var estados = {
                        hoy: '<span class="badge bg-danger">Cerrado hoy</span>',
                        pendiente: '<span class="badge bg-info">Próximo</span>',
                        vencido: '<span class="badge bg-secondary">Ya pasó</span>'
                    };

                    lista.forEach(function (c) {
                        var fechas = c.inicio === c.fin ? c.inicio : c.inicio + ' al ' + c.fin;
                        $('<tr>').toggleClass('text-muted', c.estado === 'vencido')
                            .append($('<td>').text(c.restaurante))
                            .append($('<td>').text(fechas).append(c.anual ? ' <span class="badge badge-light border">cada año</span>' : ''))
                            .append($('<td>').text(c.mensaje))
                            .append($('<td>').html(estados[c.estado]))
                            .append($('<td class="text-center">').append(
                                $('<button type="button" class="btn btn-danger btn-xs" title="Quitar">')
                                    .html('<i class="fas fa-trash-alt"></i>')
                                    .on('click', function () { borrarCierre(c.id); })))
                            .appendTo(cuerpo);
                    });
                })
                .catch(function () {
                    cuerpo.html('<tr><td colspan="5" class="text-center text-danger">Error al cargar</td></tr>');
                });
        }

        function guardarCierre(){
            var campo = function (id) { return document.getElementById(id).value.trim(); };
            var datos = {
                servicio: campo('cierre-servicio'),
                inicio: campo('cierre-inicio'),
                fin: campo('cierre-fin'),
                mensaje: campo('cierre-mensaje'),
                anual: document.getElementById('cierre-anual').checked ? 1 : 0
            };

            if (datos.inicio === '') { toastr.error('Elige la fecha de inicio'); return; }
            if (datos.fin === '') { datos.fin = datos.inicio; $('#cierre-fin').val(datos.inicio); }
            if (!datos.anual && datos.fin < datos.inicio) { toastr.error('La fecha final debe ser igual o después de la inicial'); return; }
            if (datos.mensaje === '') { toastr.error('Escribe el mensaje para el cliente'); return; }

            enviarCierre(datos);
        }

        function enviarCierre(datos){
            openLoading();
            axios.post('/admin/zonas/cierres/nuevo', datos)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Cierre programado');
                        document.getElementById('formulario-cierre').reset();
                        if (restauranteCierres) { $('#cierre-servicio').val(restauranteCierres); }
                        cargarCierres();
                        recargar();
                    } else if (response.data.success === 2) {
                        toastr.error('La fecha final debe ser igual o después de la inicial');
                    } else if (response.data.success === 3) {
                        avisoChoqueCierres(datos, response.data.choques);
                    } else {
                        toastr.error('Datos no válidos');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error del servidor');
                });
        }

        // ya hay cierres esos dias: mostrarlos y dejar guardar igual
        function avisoChoqueCierres(datos, choques){
            var lista = $('<div class="text-left">')
                .append($('<p>').text(choques.length === 1
                    ? 'Ya hay otro cierre que cae en esas fechas:'
                    : 'Ya hay ' + choques.length + ' cierres que caen en esas fechas:'));
            choques.forEach(function (c) {
                $('<div class="border rounded p-2 mb-2">')
                    .append($('<div>').append($('<b>').text(c.fechas)))
                    .append($('<div class="small text-muted">').text(c.restaurante))
                    .append($('<div class="small">').text('Mensaje: ' + c.mensaje))
                    .appendTo(lista);
            });
            $('<p class="small text-muted mb-0">')
                .text('Si guardas este también, el cliente verá el mensaje del cierre que se registró primero.')
                .appendTo(lista);

            Swal.fire({
                title: 'Fechas que chocan',
                html: lista[0],
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Guardar igual',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                enviarCierre($.extend({}, datos, { confirmar: 1 }));
            });
        }

        function borrarCierre(id){
            Swal.fire({
                title: '¿Quitar este cierre programado?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                axios.post('/admin/zonas/cierres/borrar', { id: id })
                    .then(function () { toastr.success('Cierre quitado'); cargarCierres(); recargar(); })
                    .catch(function () { toastr.error('Error del servidor'); });
            });
        }
</script>
