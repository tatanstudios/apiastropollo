@extends('adminlte::page')

@section('title', 'Reportes')

@section('css')
    <link href="{{ asset('css/toastr.min.css') }}" type="text/css" rel="stylesheet" />
@stop

@section('content')
<style>
    .rep {
        --serie: #2a78d6;           /* color de las barras (validado contra fondo claro) */
        --serie-suave: #dbe8f8;
        --texto: #212529;
        --texto-2: #6c757d;
        --linea: #eceef1;
        --bueno: #1e7e34;
        --malo: #c82333;
    }
    .rep-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
    .rep-header h1 { margin:0; font-size:1.75rem; }
    .rep-sub { color:var(--texto-2); font-size:.92rem; margin-top:2px; }

    /* filtros en una fila */
    .rep-filtros { display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
    .rep-presets { display:inline-flex; flex-wrap:wrap; border:1px solid #d6d9de; border-radius:8px; padding:3px; background:#fff; }
    .rep-presets button { border:0; background:transparent; padding:4px 10px; border-radius:6px; font-size:.85rem; color:#495057; }
    .rep-presets button.activo { background:var(--serie); color:#fff; }
    .rep-filtros input[type=date] { width:150px; }
    .rep-filtros select { width:auto; min-width:180px; }
    .rep-filtros .sep { color:var(--texto-2); font-size:.85rem; }

    /* tarjetas */
    .rep-kpis { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:14px; margin-bottom:16px; }
    @media (max-width: 1199px) { .rep-kpis { grid-template-columns:repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .rep-kpis { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    .rep-kpi { background:#fff; border:1px solid var(--linea); border-radius:12px; padding:14px 16px; }
    .rep-kpi .titulo { color:var(--texto-2); font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
    .rep-kpi .valor { font-size:1.65rem; font-weight:700; color:var(--texto); line-height:1.2; margin-top:4px; font-variant-numeric:tabular-nums; }
    .rep-kpi .detalle { font-size:.8rem; color:var(--texto-2); margin-top:4px; }
    .rep-var { font-weight:600; white-space:nowrap; }
    .rep-var.sube { color:var(--bueno); }
    .rep-var.baja { color:var(--malo); }

    .rep-card { background:#fff; border:1px solid var(--linea); border-radius:12px; margin-bottom:16px; }
    .rep-card-cab { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding:12px 16px; border-bottom:1px solid var(--linea); }
    .rep-card-cab h3 { margin:0; font-size:1rem; font-weight:600; color:var(--texto); }
    .rep-card-cab .nota { color:var(--texto-2); font-size:.8rem; }
    .rep-card-cuerpo { padding:12px 16px; }

    .rep-seg { display:inline-flex; border:1px solid #d6d9de; border-radius:8px; padding:2px; }
    .rep-seg button { border:0; background:transparent; padding:3px 10px; border-radius:6px; font-size:.8rem; color:#495057; }
    .rep-seg button.activo { background:#495057; color:#fff; }

    /* graficos svg */
    .rep-grafico { position:relative; width:100%; }
    .rep-grafico svg { display:block; width:100%; overflow:visible; }
    .rep-grafico .eje { fill:var(--texto-2); font-size:11px; }
    .rep-grafico .grid { stroke:var(--linea); stroke-width:1; }
    .rep-grafico .barra { fill:var(--serie); }
    .rep-grafico .hit:hover + .barra, .rep-grafico .barra.hover { fill:#1d5fb0; }
    .rep-grafico .hit { fill:transparent; cursor:default; }
    .rep-tooltip {
        position:absolute; pointer-events:none; background:#343a40; color:#fff; font-size:.8rem;
        padding:6px 10px; border-radius:6px; white-space:nowrap; transform:translate(-50%, -100%);
        box-shadow:0 2px 8px rgba(0,0,0,.18); display:none; z-index:5;
    }
    .rep-tooltip b { font-variant-numeric:tabular-nums; }
    .rep-vacio { text-align:center; color:var(--texto-2); padding:40px 10px; }

    /* tablas */
    .rep-tabla { margin:0; }
    .rep-tabla thead th {
        background:#f8f9fa; color:#495057; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em;
        border-top:0; border-bottom:1px solid #dee2e6; white-space:nowrap;
    }
    .rep-tabla td { vertical-align:middle; font-variant-numeric:tabular-nums; }
    .rep-tabla .num { text-align:right; white-space:nowrap; }
    .rep-tabla tfoot td { font-weight:700; border-top:2px solid #dee2e6; }
    .rep-prod { display:flex; align-items:center; gap:10px; min-width:0; }
    .rep-prod img, .rep-prod .sin { width:36px; height:28px; border-radius:6px; object-fit:contain; background:#f1f3f5; flex:0 0 36px; }
    .rep-prod .sin { display:flex; align-items:center; justify-content:center; color:#ced4da; font-size:.8rem; }
    .rep-mini { height:6px; border-radius:3px; background:var(--serie-suave); margin-top:4px; }
    .rep-mini span { display:block; height:100%; border-radius:3px; background:var(--serie); }
    .rep-cargando { opacity:.5; transition:opacity .15s; pointer-events:none; }
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="rep-header">
            <div>
                <h1>Reportes</h1>
                <div class="rep-sub" id="texto-rango">Ventas, órdenes, restaurantes y productos</div>
            </div>
        </div>
    </div>
</section>

<section class="content rep">
    <div class="container-fluid">

        <!-- filtros -->
        <div class="rep-card">
            <div class="rep-card-cuerpo">
                <div class="rep-filtros">
                    <div class="rep-presets" id="presets">
                        <button type="button" data-preset="hoy">Hoy</button>
                        <button type="button" data-preset="7">7 días</button>
                        <button type="button" data-preset="30" class="activo">30 días</button>
                        <button type="button" data-preset="mes">Este mes</button>
                        <button type="button" data-preset="mesant">Mes pasado</button>
                        <button type="button" data-preset="anio">Este año</button>
                    </div>
                    <input type="date" class="form-control form-control-sm" id="desde" aria-label="Desde">
                    <span class="sep">a</span>
                    <input type="date" class="form-control form-control-sm" id="hasta" aria-label="Hasta">
                    <select class="form-control form-control-sm" id="servicio" aria-label="Restaurante">
                        <option value="">Todos los restaurantes</option>
                        @foreach($restaurantes as $r)
                            <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-outline-secondary btn-sm ml-auto" onclick="exportarCsv()">
                        <i class="fas fa-file-download mr-1"></i> Exportar CSV
                    </button>
                </div>
            </div>
        </div>

        <div id="contenido">
            <!-- tarjetas -->
            <div class="rep-kpis" id="kpis"></div>

            <!-- ventas en el tiempo -->
            <div class="rep-card">
                <div class="rep-card-cab">
                    <h3 id="titulo-serie">Ventas por día</h3>
                    <div class="rep-seg" id="seg-serie">
                        <button type="button" data-medida="ventas" class="activo">Ventas</button>
                        <button type="button" data-medida="ordenes">Órdenes</button>
                    </div>
                </div>
                <div class="rep-card-cuerpo">
                    <div class="rep-grafico" id="grafico-serie"></div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5">
                    <div class="rep-card">
                        <div class="rep-card-cab">
                            <h3>Órdenes por hora del día</h3>
                            <span class="nota">Para planear personal y horarios</span>
                        </div>
                        <div class="rep-card-cuerpo">
                            <div class="rep-grafico" id="grafico-horas"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="rep-card">
                        <div class="rep-card-cab">
                            <h3>Productos más vendidos</h3>
                            <span class="nota">Top 10 por unidades</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm rep-tabla">
                                <thead><tr><th style="width:36px">#</th><th>Producto</th><th class="num">Unidades</th><th class="num">Monto</th></tr></thead>
                                <tbody id="tabla-productos"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- por restaurante -->
            <div class="rep-card">
                <div class="rep-card-cab">
                    <h3>Por restaurante</h3>
                    <span class="nota">Clientes = clientes con su dirección elegida en una zona del restaurante (hoy)</span>
                </div>
                <div class="table-responsive">
                    <table class="table rep-tabla">
                        <thead>
                        <tr>
                            <th>Restaurante</th>
                            <th class="num">Iniciadas</th>
                            <th class="num">Ventas</th>
                            <th class="num">Ticket prom.</th>
                            <th class="num">Pendientes</th>
                            <th class="num">Canceladas</th>
                            <th class="num">Clientes</th>
                        </tr>
                        </thead>
                        <tbody id="tabla-restaurantes"></tbody>
                        <tfoot id="pie-restaurantes"></tfoot>
                    </table>
                </div>
            </div>

            <p class="text-muted small mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Ventas = órdenes <b>iniciadas por el restaurante</b> y no canceladas, con el total que pagó el cliente.
                Las pendientes (que el restaurante aún no inicia) se muestran aparte y no suman a ventas.
                El % de canceladas es sobre todas las órdenes recibidas.
                La variación compara con el mismo número de días justo antes del rango elegido.
            </p>
        </div>
    </div>
</section>
@stop

@section('js')
    <script src="{{ asset('js/toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/axios.min.js') }}" type="text/javascript"></script>

    <script>
        let datos = null;
        let medidaSerie = 'ventas';

        // ---------- formato ----------
        const dinero = (n) => '$' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const entero = (n) => Number(n || 0).toLocaleString('en-US');
        const esc = (t) => String(t ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const ordenesTxt = (n) => entero(n) + (Number(n) === 1 ? ' orden' : ' órdenes');
        const fechaLarga = (s) => new Date(s + 'T00:00:00').toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' });

        // variación con flecha + texto (no solo color)
        function variacion(v) {
            if (v === null || v === undefined) return '<span class="text-muted">sin periodo anterior</span>';
            if (v === 0) return '<span class="rep-var">= 0%</span> vs. periodo anterior';
            const sube = v > 0;
            return `<span class="rep-var ${sube ? 'sube' : 'baja'}"><i class="fas fa-arrow-${sube ? 'up' : 'down'}"></i> ${Math.abs(v)}%</span> vs. periodo anterior`;
        }

        // ---------- filtros ----------
        function aplicarPreset(p) {
            const hoy = new Date();
            let d = new Date(hoy), h = new Date(hoy);
            if (p === '7') d.setDate(hoy.getDate() - 6);
            else if (p === '30') d.setDate(hoy.getDate() - 29);
            else if (p === 'mes') d = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            else if (p === 'mesant') { d = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1); h = new Date(hoy.getFullYear(), hoy.getMonth(), 0); }
            else if (p === 'anio') d = new Date(hoy.getFullYear(), 0, 1);
            $('#desde').val(iso(d));
            $('#hasta').val(iso(h));
            $('#presets button').removeClass('activo');
            $(`#presets button[data-preset="${p}"]`).addClass('activo');
            cargar();
        }

        $('#presets').on('click', 'button', function () { aplicarPreset($(this).data('preset')); });
        $('#desde, #hasta').on('change', function () { $('#presets button').removeClass('activo'); cargar(); });
        $('#servicio').on('change', cargar);
        $('#seg-serie').on('click', 'button', function () {
            medidaSerie = $(this).data('medida');
            $('#seg-serie button').removeClass('activo');
            $(this).addClass('activo');
            pintarSerie();
        });

        function cargar() {
            $('#contenido').addClass('rep-cargando');
            axios.get('/admin/reportes/datos', { params: { desde: $('#desde').val(), hasta: $('#hasta').val(), servicio: $('#servicio').val() } })
                .then((r) => {
                    $('#contenido').removeClass('rep-cargando');
                    if (r.data.success !== 1) { toastr.error(r.data.mensaje || 'No se pudo cargar el reporte'); return; }
                    datos = r.data;
                    pintar();
                })
                .catch(() => { $('#contenido').removeClass('rep-cargando'); toastr.error('No se pudo cargar el reporte'); });
        }

        // ---------- pintar ----------
        function pintar() {
            const k = datos.kpi, rg = datos.rango;
            const restaurante = $('#servicio option:selected').text();
            $('#texto-rango').text(`${fechaLarga(rg.desde)} – ${fechaLarga(rg.hasta)} · ${rg.dias} ${rg.dias === 1 ? 'día' : 'días'} · ${restaurante}`);

            const tarjeta = (titulo, valor, detalle) => `<div class="rep-kpi"><div class="titulo">${titulo}</div><div class="valor">${valor}</div><div class="detalle">${detalle}</div></div>`;
            $('#kpis').html(
                tarjeta('Ventas', dinero(k.ventas), variacion(k.var_ventas))
                + tarjeta('Órdenes iniciadas', entero(k.ordenes), variacion(k.var_ordenes)
                    + (k.pendientes ? `<br>${entero(k.pendientes)} pendientes sin iniciar (no suman a ventas)` : ''))
                + tarjeta('Ticket promedio', dinero(k.ticket), variacion(k.var_ticket))
                + tarjeta('Canceladas', entero(k.canceladas) + ` <small class="text-muted" style="font-size:.9rem">${k.pct_canceladas}%</small>`,
                    `${entero(k.cancel_cliente)} por el cliente · ${entero(k.cancel_restaurante)} por el restaurante`)
                + tarjeta('Clientes que compraron', entero(k.clientes), `${entero(k.con_cupon)} órdenes con cupón`)
                + tarjeta('Clientes nuevos', entero(k.clientes_nuevos), 'Cuentas creadas en la app (todos los restaurantes)')
            );

            pintarSerie();

            barras('#grafico-horas', datos.horas.map((h) => ({
                etiqueta: String(h.hora).padStart(2, '0'),
                valor: h.ordenes,
                tooltip: `${formatoHora(h.hora)} – ${formatoHora((h.hora + 1) % 24)}<br><b>${ordenesTxt(h.ordenes)}</b>`,
            })), { formato: entero, alto: 220, cadaEtiqueta: 3 });

            // productos
            const maxCant = Math.max(1, ...datos.productos.map((p) => p.cantidad));
            const todos = !$('#servicio').val();
            $('#tabla-productos').html(datos.productos.length ? datos.productos.map((p, i) => `
                <tr>
                    <td class="text-muted">${i + 1}</td>
                    <td style="max-width:0;width:100%">
                        <div class="rep-prod">
                            ${p.imagen ? `<img src="${esc(p.imagen)}" alt="">` : '<div class="sin"><i class="fas fa-image"></i></div>'}
                            <div style="min-width:0;flex:1">
                                <div class="text-truncate">${esc(p.nombre)}${todos && p.restaurante ? ` <small class="text-muted">· ${esc(p.restaurante.replace(/^Astro Pollo\s*/i, ''))}</small>` : ''}</div>
                                <div class="rep-mini"><span style="width:${(p.cantidad * 100 / maxCant).toFixed(1)}%"></span></div>
                            </div>
                        </div>
                    </td>
                    <td class="num">${entero(p.cantidad)}</td>
                    <td class="num">${dinero(p.monto)}</td>
                </tr>`).join('') : '<tr><td colspan="4" class="rep-vacio">Sin ventas en este rango</td></tr>');

            // restaurantes
            const rs = datos.restaurantes;
            $('#tabla-restaurantes').html(rs.map((r) => `
                <tr>
                    <td><b>${esc(r.nombre)}</b></td>
                    <td class="num">${entero(r.ordenes)}</td>
                    <td class="num">${dinero(r.ventas)}</td>
                    <td class="num">${r.ordenes ? dinero(r.ticket) : '—'}</td>
                    <td class="num">${entero(r.pendientes)}</td>
                    <td class="num">${entero(r.canceladas)} <small class="text-muted">(${r.pct_canceladas}%)</small></td>
                    <td class="num">${entero(r.clientes)}</td>
                </tr>`).join(''));
            if (rs.length > 1) {
                const t = rs.reduce((a, r) => ({ ordenes: a.ordenes + r.ordenes, ventas: a.ventas + r.ventas, pendientes: a.pendientes + r.pendientes, canceladas: a.canceladas + r.canceladas, clientes: a.clientes + r.clientes }), { ordenes: 0, ventas: 0, pendientes: 0, canceladas: 0, clientes: 0 });
                const recibidas = t.ordenes + t.canceladas + t.pendientes;
                const pct = recibidas ? (t.canceladas * 100 / recibidas).toFixed(1) : 0;
                $('#pie-restaurantes').html(`<tr><td>Total</td><td class="num">${entero(t.ordenes)}</td><td class="num">${dinero(t.ventas)}</td>
                    <td class="num">${t.ordenes ? dinero(t.ventas / t.ordenes) : '—'}</td><td class="num">${entero(t.pendientes)}</td>
                    <td class="num">${entero(t.canceladas)} <small class="text-muted">(${pct}%)</small></td><td class="num">${entero(t.clientes)}</td></tr>`);
            } else {
                $('#pie-restaurantes').html('');
            }
        }

        function formatoHora(h) {
            const ampm = h < 12 ? 'AM' : 'PM';
            return (h % 12 === 0 ? 12 : h % 12) + ' ' + ampm;
        }

        function pintarSerie() {
            const porMes = datos.rango.agrupado === 'mes';
            $('#titulo-serie').text((medidaSerie === 'ventas' ? 'Ventas' : 'Órdenes') + (porMes ? ' por mes' : ' por día'));
            const fmt = medidaSerie === 'ventas' ? dinero : entero;
            barras('#grafico-serie', datos.serie.map((s) => ({
                etiqueta: s.etiqueta,
                valor: s[medidaSerie],
                tooltip: `${esc(s.etiqueta)}<br><b>${dinero(s.ventas)}</b> · ${ordenesTxt(s.ordenes)}`,
            })), { formato: fmt, alto: 260, cadaEtiqueta: Math.max(1, Math.ceil(datos.serie.length / 12)) });
        }

        // ---------- grafico de barras (una serie, svg) ----------
        function barras(selector, puntos, op) {
            const cont = document.querySelector(selector);
            const ancho = Math.max(280, cont.clientWidth);
            const alto = op.alto || 240;
            const m = { arriba: 10, abajo: 26, izq: 56, der: 6 };
            const w = ancho - m.izq - m.der, h = alto - m.arriba - m.abajo;

            const max = Math.max(...puntos.map((p) => p.valor), 0);
            if (!puntos.length || max === 0) {
                cont.innerHTML = '<div class="rep-vacio">Sin datos en este rango</div>';
                return;
            }

            // escala "bonita" de 4 lineas
            const paso = escalaPaso(max / 4);
            const tope = paso * Math.ceil(max / paso);
            const y = (v) => m.arriba + h - (v / tope) * h;

            const n = puntos.length;
            const banda = w / n;
            const gap = Math.min(8, Math.max(2, banda * 0.25));
            const anchoBarra = Math.max(1, banda - gap);

            let svg = `<svg viewBox="0 0 ${ancho} ${alto}" height="${alto}" role="img" aria-label="Gráfico de barras">`;
            for (let v = 0; v <= tope + 1e-9; v += paso) {
                svg += `<line class="grid" x1="${m.izq}" x2="${ancho - m.der}" y1="${y(v)}" y2="${y(v)}"></line>`;
                svg += `<text class="eje" x="${m.izq - 8}" y="${y(v) + 4}" text-anchor="end">${op.formato(v).replace('.00', '')}</text>`;
            }
            puntos.forEach((p, i) => {
                const x = m.izq + i * banda + gap / 2;
                const top = y(p.valor);
                const alt = m.arriba + h - top;
                // barra con esquinas superiores redondeadas (4px) y anclada a la base
                const r = Math.min(4, anchoBarra / 2, alt);
                const d = alt <= 0 ? '' : `M${x},${m.arriba + h} V${top + r} Q${x},${top} ${x + r},${top} H${x + anchoBarra - r} Q${x + anchoBarra},${top} ${x + anchoBarra},${top + r} V${m.arriba + h} Z`;
                svg += `<rect class="hit" data-i="${i}" x="${m.izq + i * banda}" y="${m.arriba}" width="${banda}" height="${h}"></rect>`;
                if (d) svg += `<path class="barra" data-i="${i}" d="${d}"></path>`;
                if (i % op.cadaEtiqueta === 0) {
                    svg += `<text class="eje" x="${x + anchoBarra / 2}" y="${alto - 8}" text-anchor="middle">${esc(p.etiqueta)}</text>`;
                }
            });
            svg += '</svg><div class="rep-tooltip"></div>';
            cont.innerHTML = svg;

            // tooltip por barra (el area de toque es toda la columna)
            const tip = cont.querySelector('.rep-tooltip');
            cont.querySelectorAll('.hit').forEach((el) => {
                el.addEventListener('mouseenter', () => {
                    const i = +el.dataset.i, p = puntos[i];
                    cont.querySelectorAll('.barra').forEach((b) => b.classList.toggle('hover', +b.dataset.i === i));
                    tip.innerHTML = p.tooltip;
                    tip.style.display = 'block';
                    const escala = cont.clientWidth / ancho;
                    tip.style.left = ((m.izq + i * banda + banda / 2) * escala) + 'px';
                    tip.style.top = (Math.max(y(p.valor) - 6, 18) * escala) + 'px';
                });
                el.addEventListener('mouseleave', () => {
                    tip.style.display = 'none';
                    cont.querySelectorAll('.barra').forEach((b) => b.classList.remove('hover'));
                });
            });
        }

        function escalaPaso(bruto) {
            const exp = Math.pow(10, Math.floor(Math.log10(bruto || 1)));
            const f = bruto / exp;
            return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * exp;
        }

        // redibujar al cambiar el ancho
        let espera;
        window.addEventListener('resize', () => { clearTimeout(espera); espera = setTimeout(() => datos && pintar(), 150); });

        // ---------- exportar ----------
        function exportarCsv() {
            if (!datos) return;
            const filas = [];
            const q = (v) => '"' + String(v).replace(/"/g, '""') + '"';
            filas.push(['Reporte', `${datos.rango.desde} a ${datos.rango.hasta}`, $('#servicio option:selected').text()].map(q).join(','));
            filas.push('');
            filas.push(['Restaurante', 'Ordenes iniciadas', 'Ventas', 'Ticket promedio', 'Pendientes', 'Canceladas', '% canceladas', 'Clientes'].map(q).join(','));
            datos.restaurantes.forEach((r) => filas.push([r.nombre, r.ordenes, r.ventas.toFixed(2), r.ticket.toFixed(2), r.pendientes, r.canceladas, r.pct_canceladas, r.clientes].map(q).join(',')));
            filas.push('');
            filas.push(['Producto', 'Unidades', 'Monto'].map(q).join(','));
            datos.productos.forEach((p) => filas.push([p.nombre, p.cantidad, p.monto.toFixed(2)].map(q).join(',')));
            filas.push('');
            filas.push([datos.rango.agrupado === 'mes' ? 'Mes' : 'Dia', 'Ordenes', 'Ventas'].map(q).join(','));
            datos.serie.forEach((s) => filas.push([s.periodo, s.ordenes, s.ventas.toFixed(2)].map(q).join(',')));

            const blob = new Blob(['﻿' + filas.join('\r\n')], { type: 'text/csv;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = `reporte_${datos.rango.desde}_${datos.rango.hasta}.csv`;
            a.click();
            URL.revokeObjectURL(a.href);
        }

        $(document).ready(() => aplicarPreset('30'));
    </script>
@stop
