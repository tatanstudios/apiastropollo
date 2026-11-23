<!-- explicacion de los cupones (se abre desde Cupones y desde Editar Restaurante) -->
<div class="modal fade" id="modalAyudaCupones" style="z-index: 1060">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="far fa-question-circle mr-1"></i> ¿Cómo funcionan los cupones?</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>El cliente escribe un código al pagar y recibe un producto gratis. Hay <b>dos interruptores</b> y cada uno controla una cosa distinta:</p>

                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                    <tr>
                        <th style="width: 28%">Interruptor</th>
                        <th>Qué hace</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><b>Acepta cupones</b><div class="small text-muted">Restaurantes → Editar</div></td>
                        <td>Muestra u oculta el campo para escribir un cupón en la app cuando el cliente compra en <b>ese restaurante</b>.</td>
                    </tr>
                    <tr>
                        <td><b>Activo</b><div class="small text-muted">Cupones</div></td>
                        <td>Enciende o apaga <b>ese código</b> en todos los restaurantes donde vale.</td>
                    </tr>
                    </tbody>
                </table>

                <p class="mb-1"><b>Para que un cliente pueda usar un cupón en un restaurante se necesita todo esto:</b></p>
                <ol>
                    <li>El restaurante tiene encendido <b>Acepta cupones</b>.</li>
                    <li>El cupón está <b>Activo</b>.</li>
                    <li>El restaurante está marcado en <b>Dónde vale y qué regala</b>, con su producto gratis.</li>
                    <li>Al cupón le quedan usos (el <b>límite de usos</b> es uno solo para todos los restaurantes).</li>
                </ol>

                <div class="alert alert-light border mb-0">
                    <b>Ejemplo:</b> un cupón activo que vale en Acajutla y Metapán, pero Acajutla tiene apagado "Acepta cupones":
                    en Acajutla el cliente no ve dónde escribirlo, así que solo se puede usar en Metapán.
                    Por eso en la tabla sale <span class="badge badge-light border text-danger">no acepta cupones</span> junto a ese restaurante.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>
