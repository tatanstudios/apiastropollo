<!-- modal cierres programados por fecha -->
<div class="modal fade" id="modalCierres">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Cierres programados</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    En estas fechas el restaurante no recibe órdenes y el cliente ve el mensaje. No hace falta abrir nada después: al pasar la fecha vuelve a recibir órdenes solo.
                </p>

                <form id="formulario-cierre" onsubmit="return false;" autocomplete="off" class="border rounded p-3 mb-3 bg-light">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Restaurante</label>
                            <select class="form-control" id="cierre-servicio">
                                <option value="">Todos los restaurantes</option>
                                @foreach($servicios as $s)
                                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Desde</label>
                            <input type="date" class="form-control" id="cierre-inicio">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Hasta</label>
                            <input type="date" class="form-control" id="cierre-fin">
                        </div>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-md-6 form-group">
                            <label>Mensaje para el cliente</label>
                            <input type="text" maxlength="100" class="form-control" id="cierre-mensaje" placeholder="Ej.: Cerrado por Navidad, volvemos el 26">
                        </div>
                        <div class="col-md-4 form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="cierre-anual">
                                <label class="custom-control-label" for="cierre-anual">Repetir cada año</label>
                            </div>
                            <small class="text-muted">Solo cuenta el día y el mes.</small>
                        </div>
                        <div class="col-md-2 form-group text-right">
                            <button type="button" class="btn btn-primary btn-block" onclick="guardarCierre()">Agregar</button>
                        </div>
                    </div>
                </form>

                <table class="table table-sm table-bordered mb-0">
                    <thead>
                    <tr>
                        <th>Restaurante</th>
                        <th style="width: 170px">Fechas</th>
                        <th>Mensaje</th>
                        <th style="width: 100px">Estado</th>
                        <th style="width: 50px"></th>
                    </tr>
                    </thead>
                    <tbody id="lista-cierres"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
