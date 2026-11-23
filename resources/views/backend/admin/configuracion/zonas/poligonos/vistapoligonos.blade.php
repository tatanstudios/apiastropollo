@extends('adminlte::page')

@section('title', 'Polígono: ' . $zona->nombre)

@section('css')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" rel="stylesheet" />
    <style>
        #mapa {
            height: calc(100vh - 230px);
            min-height: 420px;
            border-radius: .25rem;
        }
        #lista-puntos {
            max-height: 220px;
            overflow-y: auto;
            font-family: monospace;
            font-size: .8rem;
        }
        #lista-puntos li { cursor: pointer; }
        #lista-puntos li:hover { background: rgba(0, 123, 255, .1); }

        /* vertices pequeños y redondos (leaflet.draw usa cuadros grandes en pantallas tactiles) */
        .leaflet-div-icon.vertice {
            border-radius: 50%;
            border: 2px solid #007bff;
            background: #fff;
        }
        .leaflet-div-icon.vertice:hover { background: #007bff; }

        .etiqueta-zona {
            background: rgba(255, 255, 255, .85);
            border: 1px solid #adb5bd;
            box-shadow: none;
            font-size: .75rem;
            font-weight: bold;
            color: #495057;
            padding: 1px 5px;
        }
        .etiqueta-zona::before { display: none; }

        .estado-zona { font-size: .85rem; }

        .etiqueta-area {
            background: #007bff;
            border: 0;
            box-shadow: none;
            color: #fff;
            font-size: .7rem;
            font-weight: bold;
            padding: 0 5px;
        }
        .etiqueta-area::before { display: none; }

        #lista-areas .btn { margin: 0 4px 4px 0; }
    </style>
@stop

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex flex-wrap align-items-center">
            <a href="{{ $rutaVolver }}" class="btn btn-default btn-sm mr-3">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <h1 class="mb-0 mr-3">Polígono: {{ $zona->nombre }} <small class="text-muted">#{{ $zona->id }}</small></h1>
            @if($zona->activo)
                <span class="badge bg-success">Visible en la app</span>
            @else
                <span class="badge bg-secondary">Oculta en la app</span>
            @endif
        </div>
        <p class="text-muted mb-0 mt-1">
            <i class="fas fa-store"></i> {{ $restaurante ?? 'Sin restaurante asignado' }}
            @if($zona->descripcion) · {{ $zona->descripcion }} @endif
        </p>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-9">
                <div class="card card-primary card-outline">
                    <div class="card-body p-2">
                        <div id="mapa"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="card card-primary card-outline">
                    <div class="card-body">

                        <div id="estado" class="estado-zona mb-3"></div>

                        <button type="button" class="btn btn-primary btn-sm btn-block" id="btn-dibujar" onclick="dibujarNuevo(false)">
                            <i class="fas fa-plus"></i> <span id="texto-dibujar">Dibujar polígono</span>
                        </button>
                        <button type="button" class="btn btn-default btn-sm btn-block" id="btn-cancelar-dibujo" style="display: none" onclick="cancelarDibujo()">
                            <i class="fas fa-times"></i> Cancelar dibujo
                        </button>
                        <button type="button" class="btn btn-default btn-sm btn-block" onclick="verZona()">
                            <i class="fas fa-search-location"></i> Ver la zona completa
                        </button>
                        <button type="button" class="btn btn-default btn-sm btn-block" onclick="modalPegar()">
                            <i class="fas fa-paste"></i> Pegar coordenadas
                        </button>

                        <details class="small text-muted mt-2">
                            <summary>¿Cómo se edita?</summary>
                            <ul class="pl-3 mb-0 mt-1">
                                <li>Arrastra un vértice para moverlo.</li>
                                <li>Arrastra el punto claro entre dos vértices para agregar uno.</li>
                                <li>Haz clic en un vértice para quitarlo.</li>
                                <li>Una zona puede tener varias áreas separadas con el mismo precio y horario. Haz clic en un área para seleccionarla.</li>
                                <li>Las demás zonas se ven en gris con su nombre.</li>
                            </ul>
                        </details>

                        <hr>

                        <div id="bloque-areas" style="display: none">
                            <b>Áreas de la zona</b>
                            <div id="lista-areas" class="mt-1 mb-2"></div>
                        </div>

                        <div id="bloque-area-actual" style="display: none">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <b><span id="titulo-area">Puntos</span>: <span id="total-puntos">0</span></b>
                                <button type="button" class="btn btn-default btn-xs" onclick="copiarCoordenadas()" title="Copiar coordenadas de esta área">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                            </div>
                            <ol id="lista-puntos" class="pl-4 mb-2" title="Clic para ver el punto en el mapa"></ol>

                            <div class="small mb-2">
                                <b>Área aproximada:</b> <span id="texto-area">—</span>
                            </div>

                            <div id="acciones-area" class="mb-3" style="display: none">
                                <button type="button" class="btn btn-default btn-xs" onclick="dibujarNuevo(true)">
                                    <i class="fas fa-draw-polygon"></i> Redibujar esta área
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-xs" onclick="quitarArea()">
                                    <i class="fas fa-times"></i> Quitar esta área
                                </button>
                            </div>
                        </div>

                        <div id="aviso-cambios" class="alert alert-warning py-1 px-2 small" style="display: none">
                            <i class="fas fa-exclamation-triangle"></i> Hay cambios sin guardar.
                        </div>

                        <button type="button" class="btn btn-success btn-block" onclick="guardar()">
                            <i class="fas fa-save"></i> Guardar polígono
                        </button>
                        <button type="button" class="btn btn-default btn-sm btn-block" id="btn-deshacer" onclick="deshacerCambios()" disabled>
                            <i class="fas fa-undo"></i> Deshacer cambios
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-block mt-3" onclick="preguntaBorrarPoligono()">
                            <i class="fas fa-trash-alt"></i> Borrar todo el polígono
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="modalPegar">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pegar coordenadas</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Un punto por línea: <code>latitud, longitud</code>. No se guarda hasta pulsar "Guardar polígono".</p>
                <textarea id="texto-pegar" class="form-control text-monospace" rows="10" placeholder="13.588659, -89.825898"></textarea>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <div>
                    <button type="button" class="btn btn-default" id="btn-pegar-reemplazar" onclick="aplicarPegado(true)">Reemplazar área seleccionada</button>
                    <button type="button" class="btn btn-primary" onclick="aplicarPegado(false)" id="btn-pegar-nueva">Agregar como área nueva</button>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

    <script>
        var idZona = {{ (int) $zona->id }};
        var minimoPuntos = {{ (int) $minimoPuntos }};
        var maximoAreas = {{ (int) $maximoAreas }};
        var areasGuardadas = @json($areas); // [ [[lat, lng], ...], ... ]
        var otrasZonas = @json($otrasZonas);
        var inicio = [13.7, -89.2]; // El Salvador, si la zona aun no tiene poligono

        var mapa, capas = [], seleccionada = -1, dibujo = null, reemplazando = -1, resaltado = null;
        var hayCambios = false;

        var estiloArea = { color: '#007bff', weight: 2, fillColor: '#007bff', fillOpacity: 0.15 };
        var estiloSeleccionada = { color: '#007bff', weight: 4, fillColor: '#007bff', fillOpacity: 0.3 };


        // vertices pequeños y redondos, tambien en pantallas tactiles
        var iconoVertice = new L.DivIcon({ iconSize: new L.Point(14, 14), className: 'leaflet-div-icon leaflet-editing-icon vertice' });
        L.Edit.PolyVerticesEdit.prototype.options.icon = iconoVertice;
        L.Edit.PolyVerticesEdit.prototype.options.touchIcon = iconoVertice;
        L.Draw.Polyline.prototype.options.icon = iconoVertice;
        L.Draw.Polyline.prototype.options.touchIcon = iconoVertice;

        L.drawLocal.draw.handlers.polygon.tooltip = {
            start: 'Haz clic para poner el primer vértice.',
            cont: 'Haz clic para poner el siguiente vértice.',
            end: 'Haz clic en el primer vértice para cerrar el polígono.'
        };
        L.drawLocal.draw.handlers.polyline.error = '<strong>Error:</strong> los lados no se pueden cruzar.';

        $(function () {
            mapa = L.map('mapa').setView(inicio, 9);

            var calles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(mapa);

            var satelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: '&copy; Esri'
            });

            L.control.layers({ 'Calles': calles, 'Satélite': satelite }, null, { position: 'topright' }).addTo(mapa);
            L.control.scale({ imperial: false }).addTo(mapa);

            // demas zonas en gris, con su nombre, como referencia
            otrasZonas.forEach(function (z) {
                if (z.puntos.length < 3) return;
                L.polygon(z.puntos, { color: '#6c757d', weight: 1, dashArray: '4', fillOpacity: 0.1 })
                    .bindTooltip(z.nombre, { permanent: true, direction: 'center', className: 'etiqueta-zona' })
                    .addTo(mapa);
            });

            mapa.on(L.Draw.Event.CREATED, function (e) {
                var puntos = e.layer.getLatLngs()[0].map(function (p) { return [p.lat, p.lng]; });
                if (reemplazando >= 0) {
                    reemplazarArea(reemplazando, puntos);
                } else {
                    agregarArea(puntos);
                    seleccionar(capas.length - 1);
                }
                marcarCambios();
            });

            mapa.on(L.Draw.Event.DRAWSTOP, function () {
                dibujo = null;
                reemplazando = -1;
                $('#btn-dibujar').show();
                $('#btn-cancelar-dibujo').hide();
                capas.forEach(function (c) { c.editing.enable(); });
                actualizarPanel();
            });

            ponerAreas(areasGuardadas);
            verZona();

            if (!capas.length) {
                toastr.info('Esta zona no tiene polígono. Pulsa "Dibujar polígono".');
            }

            window.addEventListener('beforeunload', function (e) {
                if (hayCambios) { e.preventDefault(); e.returnValue = ''; }
            });
        });

        // ---------- areas en el mapa ----------

        function crearCapa(puntos) {
            var capa = L.polygon(puntos, estiloArea).addTo(mapa);
            capa.editing.enable();
            capa.on('edit', function () { marcarCambios(); actualizarPanel(); });
            capa.on('click', function () { seleccionar(capas.indexOf(capa)); });
            return capa;
        }

        function agregarArea(puntos) {
            capas.push(crearCapa(puntos));
        }

        function reemplazarArea(i, puntos) {
            quitarCapa(capas[i]);
            capas[i] = crearCapa(puntos);
            seleccionar(i);
        }

        function quitarCapa(capa) {
            capa.editing.disable();
            mapa.removeLayer(capa);
        }

        // reemplaza todas las areas
        function ponerAreas(areas) {
            capas.forEach(quitarCapa);
            capas = [];
            areas.forEach(function (puntos) { if (puntos.length) agregarArea(puntos); });
            seleccionada = -1;
            seleccionar(capas.length ? 0 : -1);
        }

        function seleccionar(i) {
            seleccionada = i;
            capas.forEach(function (c, n) {
                c.setStyle(n === i ? estiloSeleccionada : estiloArea);
                c.unbindTooltip();
                if (capas.length > 1) {
                    c.bindTooltip('Área ' + (n + 1), { permanent: true, direction: 'center', className: 'etiqueta-area' });
                }
            });
            if (i >= 0) capas[i].bringToFront();
            actualizarPanel();
        }

        function puntosDe(capa) {
            return capa.getLatLngs()[0].map(function (p) {
                return [Number(p.lat.toFixed(8)), Number(p.lng.toFixed(8))];
            });
        }

        function todasLasAreas() {
            return capas.map(puntosDe);
        }

        // ---------- revision ----------

        // ¿se enciman dos poligonos? (misma regla que el servidor al guardar)
        // tocarse en el borde o compartir vertices no cuenta; puntos como [lat, lng]
        var TOLERANCIA = 1e-7; // ~1 cm

        function distanciaSegmento(p, a, b) {
            var dx = b[1] - a[1], dy = b[0] - a[0], largo = dx * dx + dy * dy;
            var t = largo ? Math.max(0, Math.min(1, ((p[1] - a[1]) * dx + (p[0] - a[0]) * dy) / largo)) : 0;
            var x = a[1] + t * dx - p[1], y = a[0] + t * dy - p[0];
            return Math.sqrt(x * x + y * y);
        }

        // dentro y no sobre el borde (ray casting)
        function puntoDentro(p, puntos) {
            var x = p[1], y = p[0], dentro = false;
            for (var i = 0, j = puntos.length - 1; i < puntos.length; j = i++) {
                if (distanciaSegmento(p, puntos[j], puntos[i]) < TOLERANCIA) return false;
                var xi = puntos[i][1], yi = puntos[i][0], xj = puntos[j][1], yj = puntos[j][0];
                if (((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi)) {
                    dentro = !dentro;
                }
            }
            return dentro;
        }

        function orientacion(a, b, c) {
            var v = (b[1] - a[1]) * (c[0] - a[0]) - (b[0] - a[0]) * (c[1] - a[1]);
            return Math.abs(v) < TOLERANCIA * TOLERANCIA ? 0 : (v > 0 ? 1 : -1);
        }

        // los segmentos se cruzan de verdad (no solo se tocan)
        function seCruzan(a, b, c, d) {
            var o1 = orientacion(a, b, c), o2 = orientacion(a, b, d), o3 = orientacion(c, d, a), o4 = orientacion(c, d, b);
            return o1 * o2 < 0 && o3 * o4 < 0;
        }

        function seEnciman(p1, p2) {
            if (p1.some(function (p) { return puntoDentro(p, p2); })) return true;
            if (p2.some(function (p) { return puntoDentro(p, p1); })) return true;
            for (var i = 0; i < p1.length; i++) {
                var a = p1[i], b = p1[(i + 1) % p1.length];
                for (var j = 0; j < p2.length; j++) {
                    if (seCruzan(a, b, p2[j], p2[(j + 1) % p2.length])) return true;
                }
            }
            return false;
        }

        // nombres de las otras zonas que se enciman con un area
        function encimadasCon(puntos) {
            var nombres = [];
            otrasZonas.forEach(function (z) {
                if (z.puntos.length < 3 || nombres.indexOf(z.nombre) >= 0) return;
                if (seEnciman(puntos, z.puntos)) nombres.push(z.nombre);
            });
            return nombres;
        }

        // por area: lista de zonas con las que choca
        function choquesPorArea() {
            return todasLasAreas().map(encimadasCon);
        }

        function km2(capa) {
            return L.GeometryUtil.geodesicArea(capa.getLatLngs()[0]) / 1000000;
        }

        // ---------- panel ----------

        function actualizarPanel() {
            var varias = capas.length > 1;

            $('#texto-dibujar').text(capas.length ? 'Agregar otra área' : 'Dibujar polígono');
            $('#btn-dibujar').prop('disabled', capas.length >= maximoAreas);

            // botones de areas (solo si hay mas de una)
            $('#bloque-areas').toggle(varias);
            var lista = $('#lista-areas').empty();
            capas.forEach(function (c, i) {
                $('<button type="button" class="btn btn-xs">')
                    .addClass(i === seleccionada ? 'btn-primary' : 'btn-outline-primary')
                    .text('Área ' + (i + 1) + ' · ' + puntosDe(c).length + ' pts')
                    .on('click', function () { seleccionar(i); mapa.fitBounds(c.getBounds(), { padding: [40, 40] }); })
                    .appendTo(lista);
            });

            // puntos del area seleccionada
            var capa = capas[seleccionada];
            $('#bloque-area-actual').toggle(!!capa);
            $('#acciones-area').toggle(varias);
            if (capa) {
                var puntos = puntosDe(capa);
                $('#titulo-area').text(varias ? 'Puntos del área ' + (seleccionada + 1) : 'Puntos');
                $('#total-puntos').text(puntos.length);

                var ol = $('#lista-puntos').empty();
                puntos.forEach(function (p, i) {
                    $('<li>').text(p[0] + ', ' + p[1]).on('click', function () { resaltarPunto(p); }).appendTo(ol);
                });

                var texto = km2(capa).toFixed(2) + ' km²';
                if (varias) {
                    var total = capas.reduce(function (s, c) { return s + km2(c); }, 0);
                    texto += ' (zona completa: ' + total.toFixed(2) + ' km²)';
                }
                $('#texto-area').text(texto);
            }

            actualizarEstado();
        }

        // resumen de problemas, siempre a la vista
        function actualizarEstado() {
            var avisos = [];

            var choques = choquesPorArea();

            // areas que chocan en rojo
            capas.forEach(function (c, n) {
                var estilo = n === seleccionada ? estiloSeleccionada : estiloArea;
                c.setStyle(choques[n].length ? $.extend({}, estilo, { color: '#dc3545', fillColor: '#dc3545' }) : estilo);
            });

            if (!capas.length) {
                avisos.push('<span class="text-danger"><i class="fas fa-times-circle"></i> Sin polígono: mínimo ' + minimoPuntos + ' puntos.</span>');
            } else {
                choques.forEach(function (nombres, n) {
                    if (!nombres.length) return;
                    avisos.push($('<span class="text-danger">')
                        .append('<i class="fas fa-times-circle"></i> ')
                        .append(document.createTextNode((capas.length > 1 ? 'El área ' + (n + 1) : 'El polígono')
                            + ' se encima con: ' + nombres.join(', ')))
                        .prop('outerHTML'));
                });
                if (avisos.length) {
                    avisos.push('<span class="small text-muted">Ajusta los vértices para que no se encime; así no se puede guardar.</span>');
                }
            }

            if (!avisos.length) {
                avisos.push('<span class="text-success"><i class="fas fa-check-circle"></i> '
                    + (capas.length > 1 ? 'Polígono correcto, ' + capas.length + ' áreas.' : 'Polígono correcto.') + '</span>');
            }

            $('#estado').html(avisos.join('<br>'));
        }

        function resaltarPunto(p) {
            if (resaltado) mapa.removeLayer(resaltado);
            resaltado = L.circleMarker(p, { radius: 14, color: '#dc3545', weight: 3, fill: false }).addTo(mapa);
            mapa.panTo(p);
            setTimeout(function () { if (resaltado) { mapa.removeLayer(resaltado); resaltado = null; } }, 1500);
        }

        function marcarCambios() {
            hayCambios = true;
            $('#aviso-cambios').show();
            $('#btn-deshacer').prop('disabled', false);
        }

        function limpiarCambios() {
            hayCambios = false;
            $('#aviso-cambios').hide();
            $('#btn-deshacer').prop('disabled', true);
        }

        function verZona() {
            var limites = L.latLngBounds([]);
            capas.forEach(function (c) { limites.extend(c.getBounds()); });

            if (limites.isValid()) {
                mapa.fitBounds(limites, { padding: [40, 40] });
            } else if (limitesOtrasZonas().isValid()) {
                mapa.fitBounds(limitesOtrasZonas(), { padding: [20, 20] });
            }
        }

        // area que cubren las demas zonas (para ubicarse al dibujar una zona nueva)
        function limitesOtrasZonas() {
            var limites = L.latLngBounds([]);
            otrasZonas.forEach(function (z) { z.puntos.forEach(function (p) { limites.extend(p); }); });
            return limites;
        }

        // ---------- acciones ----------

        // reemplazar = true redibuja el area seleccionada; false agrega una nueva
        function dibujarNuevo(reemplazar) {
            if (dibujo) return;
            if (!reemplazar && capas.length >= maximoAreas) {
                toastr.error('Máximo ' + maximoAreas + ' áreas por zona');
                return;
            }
            reemplazando = reemplazar ? seleccionada : -1;
            capas.forEach(function (c) { c.editing.disable(); });

            dibujo = new L.Draw.Polygon(mapa, { showArea: false, allowIntersection: false, shapeOptions: estiloSeleccionada });
            dibujo.enable();
            $('#btn-dibujar').hide();
            $('#btn-cancelar-dibujo').show();
            toastr.info(reemplazar
                ? 'Dibuja de nuevo el área ' + (seleccionada + 1) + '. Esc cancela y la deja como estaba.'
                : 'Haz clic en el mapa para cada vértice y en el primero para cerrar. Esc cancela.');
        }

        function cancelarDibujo() {
            if (dibujo) dibujo.disable(); // dispara DRAWSTOP
        }

        function quitarArea() {
            if (capas.length < 2 || seleccionada < 0) return;
            var n = seleccionada + 1;
            Swal.fire({
                title: '¿Quitar el área ' + n + '?',
                text: 'Se quita del mapa; el cambio se guarda al pulsar "Guardar polígono".',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                quitarCapa(capas[seleccionada]);
                capas.splice(seleccionada, 1);
                seleccionar(Math.min(seleccionada, capas.length - 1));
                marcarCambios();
            });
        }

        function deshacerCambios() {
            if (dibujo) dibujo.disable();
            ponerAreas(areasGuardadas);
            limpiarCambios();
            verZona();
        }

        function copiarCoordenadas() {
            var capa = capas[seleccionada];
            if (!capa) { toastr.info('No hay puntos'); return; }
            var texto = puntosDe(capa).map(function (p) { return p[0] + ', ' + p[1]; }).join('\n');
            navigator.clipboard.writeText(texto).then(function () { toastr.success('Coordenadas copiadas'); });
        }

        function modalPegar() {
            $('#texto-pegar').val('');
            $('#btn-pegar-reemplazar').toggle(capas.length > 0);
            $('#btn-pegar-nueva').text(capas.length ? 'Agregar como área nueva' : 'Aplicar');
            $('#modalPegar').modal('show');
        }

        function aplicarPegado(reemplazar) {
            var lineas = $('#texto-pegar').val().split(/\r?\n/);
            var puntos = [];

            for (var i = 0; i < lineas.length; i++) {
                var linea = lineas[i].trim();
                if (linea === '') continue;

                var partes = linea.split(/[\s,;]+/).filter(Boolean);
                var lat = parseFloat(partes[0]), lng = parseFloat(partes[1]);

                if (partes.length !== 2 || isNaN(lat) || isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                    toastr.error('Línea ' + (i + 1) + ' no válida: "' + linea + '"');
                    return;
                }
                puntos.push([lat, lng]);
            }

            if (puntos.length < minimoPuntos) {
                toastr.error('Se necesitan al menos ' + minimoPuntos + ' puntos');
                return;
            }
            if (!reemplazar && capas.length >= maximoAreas) {
                toastr.error('Máximo ' + maximoAreas + ' áreas por zona');
                return;
            }

            if (dibujo) dibujo.disable();
            if (reemplazar && seleccionada >= 0) {
                reemplazarArea(seleccionada, puntos);
            } else {
                agregarArea(puntos);
                seleccionar(capas.length - 1);
            }
            mapa.fitBounds(capas[seleccionada].getBounds(), { padding: [40, 40] });
            marcarCambios();
            $('#modalPegar').modal('hide');
        }

        // revisa las areas antes de guardar y pide confirmacion si hay algo raro
        function guardar() {
            if (dibujo) {
                toastr.error('Termina o cancela el dibujo antes de guardar');
                return;
            }
            if (!capas.length) {
                toastr.error('El polígono necesita al menos ' + minimoPuntos + ' puntos');
                return;
            }

            var areas = todasLasAreas();
            var corta = areas.findIndex(function (a) { return a.length < minimoPuntos; });
            if (corta >= 0) {
                toastr.error('El área ' + (corta + 1) + ' necesita al menos ' + minimoPuntos + ' puntos');
                return;
            }

            // no se guarda si se encima con otra zona (el cliente quedaria en dos zonas a la vez)
            var choques = choquesPorArea();
            var primera = choques.findIndex(function (n) { return n.length; });
            if (primera >= 0) {
                seleccionar(primera);
                mapa.fitBounds(capas[primera].getBounds(), { padding: [40, 40] });
                alertaMensaje('error', 'No se puede guardar',
                    (capas.length > 1 ? 'El área ' + (primera + 1) : 'El polígono') + ' se encima con ' + choques[primera].join(', ')
                    + '. Ajusta los vértices para que no se encime.');
                return;
            }

            enviarPoligono(areas);
        }

        function enviarPoligono(areas) {
            openLoading();
            axios.post('/admin/zonas/poligono/listado-nuevo', { id: idZona, areas: areas })
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success(areas.length > 1 ? 'Polígono guardado (' + areas.length + ' áreas)' : 'Polígono guardado');
                        areasGuardadas = areas;
                        limpiarCambios();
                    } else if (response.data.success === 2) {
                        alertaMensaje('error', 'No se puede guardar', 'Se encima con ' + response.data.zonas.join(', ')
                            + '. Ajusta los vértices para que no se encime.');
                    } else {
                        toastr.error('Datos no válidos, revisa los puntos');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error al guardar');
                });
        }

        function preguntaBorrarPoligono() {
            Swal.fire({
                title: capas.length > 1 ? '¿Borrar todas las áreas?' : '¿Borrar todo el polígono?',
                text: 'La zona se ocultará en la app hasta que se dibuje uno nuevo.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, borrar'
            }).then(function (result) {
                if (result.isConfirmed) { borrarPoligonos(); }
            });
        }

        function borrarPoligonos() {
            openLoading();
            var formData = new FormData();
            formData.append('id', idZona);

            axios.post('/admin/zonas/poligono/borrar', formData)
                .then(function (response) {
                    closeLoading();
                    if (response.data.success === 1) {
                        toastr.success('Polígono borrado; la zona quedó oculta');
                        areasGuardadas = [];
                        if (dibujo) dibujo.disable();
                        ponerAreas([]);
                        limpiarCambios();
                    } else {
                        toastr.error('Error al borrar');
                    }
                })
                .catch(function () {
                    closeLoading();
                    toastr.error('Error al borrar');
                });
        }
    </script>
@stop
