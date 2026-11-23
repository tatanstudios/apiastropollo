<?php

namespace App\Http\Controllers\Api\Ordenes;

use App\Http\Controllers\Controller;
use App\Jobs\EnviarNotificacionRestaurante;
use App\Models\Clientes;
use App\Models\Cupones;
use App\Models\Ordenes;
use App\Models\OrdenesDescripcion;
use App\Models\OrdenesDirecciones;
use App\Models\Productos;
use App\Models\UsuariosServicios;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OneSignal;

class ApiOrdenesController extends Controller
{

    public function verListadoOrdenesActivasCliente(Request $request)
    {


        // validaciones para los datos
        $reglaDatos = array(
            'clienteid' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if ($validarDatos->fails()) {
            return ['success' => 0];
        }

        if (Clientes::where('id', $request->clienteid)->first()) {

            // solo ordenes no canceladas, ni completadas
            $orden = Ordenes::where('id_cliente', $request->clienteid)
                ->where('visible', 1)
                ->orderBy('id', 'DESC')
                ->get();

            foreach ($orden as $info) {
                $info->fecha_orden = date("h:i A d-m-Y", strtotime($info->fecha_orden));

                $infoDireccion = OrdenesDirecciones::where('id_ordenes', $info->id)->first();

                $info->direccion = $infoDireccion->direccion;

                // CUPONES
                $haycupon = 0;
                $totalAPagar = $info->total_orden;

                if ($info->id_cupones != null) {
                    $haycupon = 1;
                }

                $info->totalformat = '$' . number_format((float)$totalAPagar, 2, '.', ',');

                $estado = "Orden Pendiente";

                // LOS ESTADOS VAN POR PRIORIDAD

                if ($info->estado_iniciada == 1) {
                    $estado = "Orden Iniciada";
                }

                // Si fue cancelada

                if ($info->estado_cancelada == 1) {
                    $estado = "Orden Cancelada";
                }


                $info->haycupon = $haycupon;
                $info->estado = $estado;
            }

            return ['success' => 1, 'ordenes' => $orden];
        } else {
            return ['success' => 2];
        }
    }


    public function informacionOrdenIndividual(Request $request)
    {


        $reglaDatos = array(
            'ordenid' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if ($validarDatos->fails()) {
            return ['success' => 0];
        }

        if (Ordenes::where('id', $request->ordenid)->first()) {

            $orden = Ordenes::where('id', $request->ordenid)->get();

            foreach ($orden as $info) {

                if ($info->estado_iniciada == 1) { // propietario inicia la orden

                    // cuando el restaurante manda a iniciar la orden se toma su tiempo y el de la zona
                    // esto dara el campo fecha ya formateado

                    $fechaEstimada = "Hora Estimada de Entrega \n" . date("h:i A d-m-Y", strtotime($info->fecha_estimada));
                    $textoIniciada = "Orden Iniciada";
                } else {
                    $textoIniciada = "Orden Pendiente";
                    $fechaEstimada = "";
                }

                $info->textoiniciada = $textoIniciada;
                $info->fechaestimada = $fechaEstimada;


                //****************************************************


                if ($info->estado_cancelada == 1) {
                    $info->fecha_cancelada = date("h:i A d-m-Y", strtotime($info->fecha_cancelada));
                }


                $info->nota_cancelada = "Nota: " . $info->nota_cancelada;

            }

            return ['success' => 1, 'ordenes' => $orden];
        } else {
            return ['success' => 2];
        }

    }


    public function ocultameOrdenCliente(Request $request){

        $reglaDatos = array(
            'ordenid' => 'required',
        );

        /// mensaje

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        if($infoOrden = Ordenes::where('id', $request->ordenid)->first()){


            // OCULTA ORDEN A CLIENTE
            Ordenes::where('id', $infoOrden->id)
                ->update(['visible' => 0]);

            return ['success' => 1];
        }else{
            return ['success' => 99];
        }


    }


    public function listadoProductosOrdenes(Request $request){

        $reglaDatos = array(
            'ordenid' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        if($infoOrden = Ordenes::where('id', $request->ordenid)->first()){


            $lista = OrdenesDescripcion::where('id_ordenes', $infoOrden->id)->get();

            // opciones elegidas (copiadas al ordenar): [idOrdenDescripcion => ['texto' => [...], 'extra' => x]]
            $opcionesOrden = \App\Services\Modificadores::deOrden($lista->pluck('id')->all());

            foreach ($lista as $info){

                // ej. ["Elegir sabor: Naranja"]; el precio guardado ya incluye las opciones
                $info->opciones = $opcionesOrden[$info->id]['texto'] ?? [];

                $infoProducto = Productos::where('id', $info->id_producto)->first();
                $info->nombreproducto = $infoProducto->nombre;

                $info->idordendescrip = $info->id;

                $info->utiliza_imagen = $infoProducto->utiliza_imagen;
                $info->imagen = $infoProducto->imagen;

                $multi = $info->cantidad * $info->precio;
                $info->multiplicado = '$' . number_format((float)$multi, 2, '.', ',');
            }

            return ['success' => 1, 'productos' => $lista];
        }else{
            return ['success' => 2];
        }
    }



    public function infoProductoOrdenadoIndividual(Request $request){


        $reglaDatos = array(
            'idordendescrip' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        if($infoOrdenDescr = OrdenesDescripcion::where('id', $request->idordendescrip)->first()){

            $lista = OrdenesDescripcion::where('id', $infoOrdenDescr->id)->get();

            // opciones elegidas (copiadas al ordenar): [idOrdenDescripcion => ['texto' => [...], 'extra' => x]]
            $opcionesOrden = \App\Services\Modificadores::deOrden($lista->pluck('id')->all());

            foreach ($lista as $info){

                // ej. ["Elegir sabor: Naranja"]; el precio guardado ya incluye las opciones
                $info->opciones = $opcionesOrden[$info->id]['texto'] ?? [];

                $infoProducto = Productos::where('id', $info->id_producto)->first();
                $info->nombreproducto = $infoProducto->nombre;

                $info->utiliza_imagen = $infoProducto->utiliza_imagen;
                $info->imagen = $infoProducto->imagen;

                $info->descripcion = $infoProducto->descripcion;

                $multi = $info->cantidad * $info->precio;
                $info->multiplicado = '$' . number_format((float)$multi, 2, '.', ',');

                $info->precio = '$' . number_format((float)$info->precio, 2, '.', ',');
            }

            return ['success' => 1, 'productos' => $lista];
        }else{
            return ['success' => 2];
        }
    }

    public function cancelarOrdenPorCliente(Request $request){

        $reglaDatos = array(
            'idorden' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        if($infoOrden = Ordenes::where('id', $request->idorden)->first()){

            if($infoOrden->estado_iniciada == 1){
                $titulo = "Orden Fue Iniciada";
                $mensaje = "La orden ya fue iniciada por el Restaurante";
                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }

            if($infoOrden->estado_cancelada == 0){

                DB::beginTransaction();

                try {

                    $fecha = Carbon::now('America/El_Salvador');

                    Ordenes::where('id', $infoOrden->id)->update(['estado_cancelada' => 1,
                        'cancelado_por' => 1,
                        'visible' => 0,
                        'fecha_cancelada' => $fecha]);

                    // SUBIR CONTADOR DE CUPON SI FUE UITILIZADO

                    // UTILIZO ESTE COPIA PORQUE SIEMPRE SE REGISTRA CUALQUIER CUPON INGRESADO
                    if($infoOrden->id_cupones_copia != null){

                        $infoCupon = Cupones::where('id', $infoOrden->id_cupones_copia)->first();
                        $contador = $infoCupon->contador - 1;


                        Cupones::where('id', $infoCupon->id)
                            ->update(['contador' => $contador]);

                    }

                    // NOTIFICACION A RESTAURANTE DE ORDEN CANCELADA


                    // a todos los usuarios activos del restaurante
                    $tokensRestaurante = UsuariosServicios::idsActivos($infoOrden->id_servicio);
                    if(!empty($tokensRestaurante)){
                        $titulo = "Orden #" . $infoOrden->id . " Cancelada";
                        $mensaje = "La orden fue cancelada por Cliente";

                        dispatch(new EnviarNotificacionRestaurante($tokensRestaurante, $titulo, $mensaje));
                    }


                    DB::commit();

                    $titulo = "Orden Fue Cancelada";
                    $mensaje = "";
                    return ['success' => 2, 'titulo' => $titulo, 'mensaje' => $mensaje];

                } catch(\Throwable $e){
                    Log::info('error ' . $e);
                    DB::rollback();
                    return ['success' => 99];
                }

            }else{

                // DIGAMOS QUE CLIENTE CANCELO Y ANTES EL RESTAURANTE CANCELO,
                // ASI QUE MEJOR OCULTAR YA QUE QUIERE CANCELAR

                Ordenes::where('id', $infoOrden->id)->update(['visible' => 0]);

                $titulo = "Orden Fue Cancelada";
                $mensaje = "";
                return ['success' => 2, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }
        }else{
            return ['success' => 99]; // no encontrada
        }
    }



    public function historialOrdenesCliente(Request $request){

        // validaciones para los datos
        $reglaDatos = array(
            'id' => 'required',
            'fecha1' => 'required',
            'fecha2' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if ($validarDatos->fails()) {
            return ['success' => 0];
        }

        $date1 = Carbon::parse($request->fecha1)->startOfDay();
        $date2 = Carbon::parse($request->fecha2)->endOfDay();

        if ($infoCliente = Clientes::where('id', $request->id)->first()) {

            // todas las ordenes por fecha
            $arrayOrdenes = Ordenes::whereBetween('fecha_orden', array($date1, $date2))
                ->where('id_cliente', $infoCliente->id)
                ->orderBy('id', 'DESC')
                ->get();

            $conteo = 0;

            foreach ($arrayOrdenes as $info) {

                $conteo++;

                $info->fecha_orden = date("h:i A d-m-Y", strtotime($info->fecha_orden));

                $infoDireccion = OrdenesDirecciones::where('id_ordenes', $info->id)->first();

                $info->direccion = $infoDireccion->direccion;

                // CUPONES
                $haycupon = 0;
                $totalAPagar = $info->total_orden;

                if ($info->id_cupones != null) {
                    $haycupon = 1;
                }

                $info->totalformat = '$' . number_format((float)$totalAPagar, 2, '.', ',');

                $estado = "Orden Pendiente";

                // LOS ESTADOS VAN POR PRIORIDAD

                if ($info->estado_iniciada == 1) {
                    $estado = "Orden Iniciada";
                }

                // Si fue cancelada

                if ($info->estado_cancelada == 1) {
                    $estado = "Orden Cancelada";
                }


                $info->haycupon = $haycupon;
                $info->estado = $estado;
            }

            return ['success' => 1, 'hayordenes' => $conteo, 'ordenes' => $arrayOrdenes];
        } else {
            return ['success' => 2];
        }


    }



}
