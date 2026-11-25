<?php

namespace App\Http\Controllers\Backend\WhatsApp;

use App\Http\Controllers\Backend\Roles\PermisoController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TEMPORAL (solo mientras se arranca en produccion; despues se quita junto con su
 * ruta y su boton en la vista de WhatsApp).
 *
 * Borra TODAS las ordenes de prueba y lo que depende de ellas:
 *   ordenes_descripcion_opcion -> ordenes_descripcion -> ordenes_direcciones -> ordenes
 * Ademas reinicia la numeracion de ordenes (la siguiente sera #1), deja en 0 el uso de
 * los cupones y desliga del historial de WhatsApp los envios de esas ordenes
 * (el historial y el saldo del bolson se conservan).
 */
class BorrarVentasController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
        $this->middleware('can:' . PermisoController::PERMISO_ROLES);
    }

    public function borrar(Request $request){
        // confirmacion escrita en el panel
        if($request->confirmar !== 'BORRAR'){
            return ['success' => 0];
        }

        $total = DB::table('ordenes')->count();

        DB::transaction(function () {
            DB::table('ordenes_descripcion_opcion')->delete();
            DB::table('ordenes_descripcion')->delete();
            DB::table('ordenes_direcciones')->delete();
            DB::table('ordenes')->delete();

            DB::table('cupones')->update(['contador' => 0]);
            DB::table('whatsapp_movimientos')->whereNotNull('id_orden')->update(['id_orden' => null]);
        });

        // ALTER TABLE cierra la transaccion por su cuenta: va despues
        foreach (['ordenes_descripcion_opcion', 'ordenes_descripcion', 'ordenes_direcciones', 'ordenes'] as $tabla) {
            DB::statement("ALTER TABLE `$tabla` AUTO_INCREMENT = 1");
        }

        Log::warning("Borrar ventas: $total ordenes borradas por el administrador " . Auth::id());

        return ['success' => 1, 'borradas' => $total];
    }
}
