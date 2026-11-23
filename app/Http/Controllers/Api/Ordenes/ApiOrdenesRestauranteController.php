<?php

namespace App\Http\Controllers\Api\Ordenes;


use App\Services\OneSignalService;
use App\Http\Controllers\Controller;
use App\Jobs\EnviarNotificacion;
use App\Jobs\EnviarNotificacionRestaurante;
use App\Jobs\EnviarNotificacionUsuario;
use App\Models\Categorias;
use App\Models\Clientes;
use App\Models\Cupones;
use App\Models\Ordenes;
use App\Models\OrdenesDescripcion;
use App\Models\OrdenesDirecciones;
use App\Models\Productos;
use App\Models\Servicios;
use App\Models\SubCategorias;
use App\Models\UsuariosServicios;
use App\Models\Zonas;
use App\Models\ZonasServicio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use OneSignal;

class ApiOrdenesRestauranteController extends Controller
{
    public function nuevasOrdenes(Request $request){

        $rules = array(
            'id' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }

        if($infoUsuario = UsuariosServicios::where('id', $request->id)->first()){

            // USUARIO BLOQUEADO
            if($infoUsuario->bloqueado == 1){
                return ['success'=> 1];
            }


            if($request->externo == 1){
                // app nueva: usa external_id de OneSignal, el token viejo ya no hace falta
                if($infoUsuario->token_fcm != null){
                    UsuariosServicios::where('id', $infoUsuario->id)->update(['token_fcm' => null]);
                }
            }
            else if($request->idfirebase != null){
                UsuariosServicios::where('id', $infoUsuario->id)->update(['token_fcm' => $request->idfirebase]);
            }


            // actualizar ccada vez que entran
            $fecha = Carbon::now('America/El_Salvador');

            UsuariosServicios::where('id', $infoUsuario->id)->update([
                'fecha_entroapp' => $fecha]);


            $arrayOrdenes = Ordenes::where('estado_iniciada', 0)
                ->where('estado_cancelada', 0)
                ->where('id_servicio', $infoUsuario->id_servicios)
                ->orderBy('id', 'ASC')
                ->get();

            $conteo = 0;

            foreach($arrayOrdenes as $info){
                $conteo++;

                $infoOrdenesDireccion = OrdenesDirecciones::where('id_ordenes', $info->id)->first();

                $info->fecha_orden = date("h:i A d-m-Y", strtotime($info->fecha_orden));

                // CUPONES
                $haycupon = 0;
                $totalAPagar = $info->total_orden;

                if ($info->id_cupones != null) {
                    $haycupon = 1;
                }

                $info->totalformat = '$' . number_format((float)$totalAPagar, 2, '.', ',');


                $info->haycupon = $haycupon;
                $info->cliente = $infoOrdenesDireccion->nombre;
                $info->direccion = $infoOrdenesDireccion->direccion;
                $info->telefono = $infoOrdenesDireccion->telefono;
                $info->referencia = $infoOrdenesDireccion->referencia;


            }

            // para el menu lateral de la app: restaurante y usuario con sesion
            $nombreRestaurante = Servicios::where('id', $infoUsuario->id_servicios)->value('nombre');
            $nombreUsuario = $infoUsuario->nombre ?: $infoUsuario->usuario;

            return ['success' => 2, 'hayordenes' => $conteo, 'ordenes' => $arrayOrdenes,
                'restaurante' => $nombreRestaurante, 'usuario' => $nombreUsuario, 'login' => $infoUsuario->usuario];
        }else{
            return ['success' => 3];
        }
    }



    public function listadoProductosOrden(Request $request){


        $reglaDatos = array(
            'idorden' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        // buscar la orden
        if($infoOrden = Ordenes::where('id', $request->idorden)->first()){

            $lista = OrdenesDescripcion::where('id_ordenes', $infoOrden->id)->get();

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
        }
        else{

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


    public function iniciarOrdenPorRestaurante(Request $request){

        $reglaDatos = array(
            'idorden' => 'required',
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        if($infoOrden = Ordenes::where('id', $request->idorden)->first()){

            // ORDEN ESTA CANCELADA
            if($infoOrden->estado_cancelada == 1){
                $titulo = "Orden Cancelada";
                $mensaje = "Fue cancelada por el Cliente";
                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }

            // INICIO DE ORDEN
            if($infoOrden->estado_iniciada == 0){

                $fecha = Carbon::now('America/El_Salvador');

                // TIEMPO DE ZONA + DEL RESTAURANTE
                if($infoZona = Zonas::where('id', $infoOrden->id_zona)->first()){

                    $fechaInicioPreparar = Carbon::parse($fecha);
                    $horaEstimada = $fechaInicioPreparar->addMinute($infoZona->tiempo_extra);
                }else{

                    $horaEstimada = Carbon::now('America/El_Salvador');
                }

                //****************
                $fechaHoy = Carbon::now('America/El_Salvador');

                Ordenes::where('id', $infoOrden->id)
                    ->update(['estado_iniciada' => 1,
                              'fecha_iniciada' => $fechaHoy,
                              'fecha_estimada' => $horaEstimada,
                              // iniciar es el ultimo paso: la orden pasa al historial del cliente
                              'visible' => 0]);



                // NOTIFICACION ONE SIGNAL A CLIENTE

                // por external_id (app nueva) y token_fcm (apps viejas)
                $tituloNoti = "Orden #" . $infoOrden->id . " Aceptada";
                $mensajeNoti = "Su orden inicia su Preparación";
                dispatch(new EnviarNotificacionUsuario([$infoOrden->id_cliente], $tituloNoti, $mensajeNoti));

                $titulo = "Nota";
                $mensaje = "Orden Iniciada";

                // orden iniciada
                return ['success' => 2, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }

            $titulo = "Nota";
            $mensaje = "Orden Iniciada";

            // orden iniciada por defecto
            return ['success'=> 2, 'titulo' => $titulo, 'mensaje' => $mensaje];
        }else{
            return ['success'=> 3];
        }

    }



    public function cancelarOrden(Request $request){

        $reglaDatos = array(
            'idorden' => 'required',
            'mensaje' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        if($infoOrden = Ordenes::where('id', $request->idorden)->first()){

            DB::beginTransaction();

            try {

                // VERIFICAR QUE ORDEN NO ESTE CANCELADA
                if($infoOrden->estado_cancelada == 1){
                    $titulo = "Nota";
                    if($infoOrden->cancelado_por == 1){
                        $mensaje = "La orden ya habia sido cancelada por el Cliente";
                    }
                    else{
                        $mensaje = "Orden fue cancelada";
                    }


                    return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                }

                // LA ORDEN PUEDE CANCELARSE

                $fecha = Carbon::now('America/El_Salvador');

                Ordenes::where('id', $infoOrden->id)->update(['estado_cancelada' => 1,
                    'fecha_cancelada' => $fecha,
                    'cancelado_por' => 2,
                    'nota_cancelada' => $request->mensaje]);








                // SI SE UTILIZO CUPON SE DEBE DE VOLVER A SUMAR SU CONTADOR EN - 1

                if($infoOrden->id_cupones_copia != null){
                    // si hay 1 cupon

                    $infoCupones = Cupones::where('id', $infoOrden->id_cupones_copia)->first();

                    $contador = $infoCupones->contador - 1;

                    Cupones::where('id', $infoCupones->id)->update(['contador' => $contador]);
                }

                    // notificacion a cliente que su orden fue cancelada
                    // por external_id (app nueva) y token_fcm (apps viejas); despues de responder
                    $tituloNoti = "Orden #" . $infoOrden->id . " Cancelada";
                    $mensajeNoti = "Fue cancelada por Restaurante";
                    dispatch(new EnviarNotificacionUsuario([$infoOrden->id_cliente], $tituloNoti, $mensajeNoti));

                    DB::commit();

                    // Orden cancelada por restaurante

                    $tituloFinal = "Nota";
                    $mensajeFinal = "Orden cancelada correctamente";

                    return ['success' => 1, 'titulo' => $tituloFinal, 'mensaje' => $mensajeFinal];


            } catch(\Throwable $e){
                DB::rollback();
                return ['success' => 99];
            }
        }else{

            // orden no encontrada
            return ['success' => 99];
        }
    }





    public function completadasHoyOrdenes(Request $request){

        $rules = array(
            'id' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }

        if($infoUsuario = UsuariosServicios::where('id', $request->id)->first()){

            $fecha = Carbon::now('America/El_Salvador');

            // ordenes iniciadas hoy (iniciar es el ultimo paso del restaurante)
            $arrayOrdenes = Ordenes::where('estado_iniciada', 1)
                ->where('estado_cancelada', 0)
                ->whereDate('fecha_iniciada', $fecha)
                ->where('id_servicio', $infoUsuario->id_servicios)
                ->orderBy('id', 'ASC')
                ->get();

            $conteo = 0;

            foreach($arrayOrdenes as $info){
                $conteo++;

                $infoOrdenesDireccion = OrdenesDirecciones::where('id_ordenes', $info->id)->first();

                $info->fecha_orden = date("h:i A d-m-Y", strtotime($info->fecha_orden));

                // la app del restaurante muestra este campo como "Inicio"
                $info->fecha_preparada = date("h:i A d-m-Y", strtotime($info->fecha_iniciada));

                // CUPONES
                $haycupon = 0;
                $totalAPagar = $info->total_orden;

                if ($info->id_cupones != null) {
                    $haycupon = 1;
                }

                $info->totalformat = '$' . number_format((float)$totalAPagar, 2, '.', ',');


                $info->haycupon = $haycupon;
                $info->cliente = $infoOrdenesDireccion->nombre;
                $info->direccion = $infoOrdenesDireccion->direccion;
                $info->telefono = $infoOrdenesDireccion->telefono;
                $info->referencia = $infoOrdenesDireccion->referencia;


            }

            return ['success' => 1, 'hayordenes' => $conteo, 'ordenes' => $arrayOrdenes];
        }else{
            return ['success' => 99];
        }
    }


    public function canceladasHoyOrdenes(Request $request){

        $rules = array(
            'id' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }

        if($infoUsuario = UsuariosServicios::where('id', $request->id)->first()){

            $fecha = Carbon::now('America/El_Salvador');


            $arrayOrdenes = Ordenes::where('estado_cancelada', 1)
                ->whereDate('fecha_cancelada', $fecha)
                ->where('id_servicio', $infoUsuario->id_servicios)
                ->orderBy('id', 'ASC')
                ->get();

           $conteo = 0;

            foreach($arrayOrdenes as $info){
                $conteo++;

                $infoOrdenesDireccion = OrdenesDirecciones::where('id_ordenes', $info->id)->first();

                $info->fecha_orden = date("h:i A d-m-Y", strtotime($info->fecha_orden));

                $info->fecha_cancelada = date("h:i A d-m-Y", strtotime($info->fecha_cancelada));

                // CUPONES
                $haycupon = 0;
                $totalAPagar = $info->total_orden;

                if ($info->id_cupones != null) {
                    $haycupon = 1;
                }

                $info->totalformat = '$' . number_format((float)$totalAPagar, 2, '.', ',');


                $info->haycupon = $haycupon;
                $info->cliente = $infoOrdenesDireccion->nombre;
                $info->direccion = $infoOrdenesDireccion->direccion;
                $info->telefono = $infoOrdenesDireccion->telefono;
                $info->referencia = $infoOrdenesDireccion->referencia;


            }

            return ['success' => 1, 'hayordenes' => $conteo, 'ordenes' => $arrayOrdenes];
        }else{
            return ['success' => 99];
        }
    }

    public function listadoDeCategorias(Request $request){

        $rules = array(
            'id' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }

        if($infoUsuario = UsuariosServicios::where('id', $request->id)->first()){

           $arrayCategorias = Categorias::where('id_servicios', $infoUsuario->id_servicios)
               ->orderBy('posicion', 'ASC')
               ->get();

            foreach($arrayCategorias as $info){

                $horaAbre = date("h:i A", strtotime($info->hora_abre));
                $horaCierra = date("h:i A", strtotime($info->hora_cierra));

                $info->horario = "Horario: " . $horaAbre . " / " . $horaCierra;

                if($info->activo == 1){
                    $info->estado = "Activo";
                }else{
                    $info->estado = "Desactivado";
                }


            }

            return ['success' => 1, 'categorias' => $arrayCategorias];
        }else{
            return ['success' => 99];
        }
    }


    public function actualizarEstadoCategoria(Request $request){

        $rules = array(
            'idcategoria' => 'required',
            'checkvalor' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }

        if($infoCategoria = Categorias::where('id', $request->idcategoria)->first()){

            Categorias::where('id', $infoCategoria->id)
                ->update(['activo' => $request->checkvalor]);

            return ['success' => 1];
        }else{
            return ['success' => 99];
        }
    }



    public function listadoDeProductosPorCategoria(Request $request){


        $reglaDatos = array(
            'idcategoria' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }


        if($infoCategoria = Categorias::where('id', $request->idcategoria)->first()){

            // listado de sub categorias

            $arraySubCategorias = SubCategorias::where('id_categorias', $infoCategoria->id)->get();

            $pilaIdSubCategorias = array();

            foreach ($arraySubCategorias as $info){
                array_push($pilaIdSubCategorias, $info->id);
            }

            $arrayProductos = Productos::whereIn('id_subcategorias', $pilaIdSubCategorias)
                ->orderBy('posicion', 'ASC')
                ->get();


            foreach ($arrayProductos as $info){

                if($info->activo == 1){
                    $info->estado = "Activado";
                }else{
                    $info->estado = "Desactivado";
                }
            }

            return ['success' => 1, 'productos' => $arrayProductos,];
        }
        else{

            return ['success' => 2];
        }
    }


    public function actualizarEstadoProducto(Request $request){


        $rules = array(
            'idproducto' => 'required',
            'checkvalor' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }

        if($infoProducto = Productos::where('id', $request->idproducto)->first()){

            Productos::where('id', $infoProducto->id)
                ->update(['activo' => $request->checkvalor]);

            return ['success' => 1];
        }else{
            return ['success' => 99];
        }

    }



    public function historialOrdenesRestaurantes(Request $request)
    {

        $reglaDatos = array(
            'id' => 'required',
            'fecha1' => 'required',
            'fecha2' => 'required'
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if ($validarDatos->fails()) {
            return ['success' => 0];
        }

        if ($infoUsuario = UsuariosServicios::where('id', $request->id)->first()) {

            $date1 = Carbon::parse($request->fecha1)->startOfDay();
            $date2 = Carbon::parse($request->fecha2)->endOfDay();

            // todas las ordenes por fecha
            $arrayOrdenes = Ordenes::whereBetween('fecha_orden', array($date1, $date2))
                ->where('id_servicio', $infoUsuario->id_servicios)
                ->orderBy('id', 'DESC')
                ->get();

            foreach ($arrayOrdenes as $info) {

                $info->fecha_orden = date("d-m-Y h:i A", strtotime($info->fecha_orden));

                $estado = "Orden Pendiente";

                // POR PRIORIDAD

                if ($info->estado_iniciada == 1) {
                    $estado = "Orden Iniciada";
                }

                if ($info->estado_cancelada == 1) {
                    $estado = "Orden Cancelada";
                }

                $info->estado = $estado;


                $infoOrdenesDireccion = OrdenesDirecciones::where('id_ordenes', $info->id)->first();


                // CUPONES
                $haycupon = 0;
                $totalAPagar = $info->total_orden;

                if ($info->id_cupones != null) {
                    $haycupon = 1;
                }

                $info->totalformat = '$' . number_format((float)$totalAPagar, 2, '.', ',');


                $info->haycupon = $haycupon;
                $info->cliente = $infoOrdenesDireccion->nombre;
                $info->direccion = $infoOrdenesDireccion->direccion;
                $info->telefono = $infoOrdenesDireccion->telefono;




            }


            return ['success' => 1, 'ordenes' => $arrayOrdenes];
        } else {
            return ['success' => 2];
        }
    }


    public function enviarNotificacionPropia(Request $request){

        $rules = array(
            'id' => 'required'
        );

        // tokenid (apps viejas) | externo = 1 (app nueva, usa external_id de OneSignal)

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails()){return ['success' => 0];}

        if($infoAfiliado = UsuariosServicios::where('id', $request->id)->first()){

            if($request->externo == 1){
                UsuariosServicios::where('id', $infoAfiliado->id)->update(['token_fcm' => null]);
            }
            else if(!empty($request->tokenid)){
                // antes se enviaba al token anterior (se leia antes de actualizarlo)
                UsuariosServicios::where('id', $infoAfiliado->id)->update(['token_fcm' => $request->tokenid]);
            }

            // se envia en el momento para decirle a la app si llego a algun telefono
            $resultado = OneSignalService::enviarUsuariosRestaurante(
                [$infoAfiliado->id],
                "Notificación de prueba",
                "Este teléfono recibe los avisos de órdenes nuevas",
                config('googleapi.CanalRestaurante')
            );

            if($resultado === 'enviado'){
                return ['success' => 1];
            }
            if($resultado === 'sin_dispositivo'){
                // OneSignal no tiene ningun telefono vinculado a este usuario
                return ['success' => 2, 'mensaje' => 'Este teléfono todavía no está registrado para recibir avisos.'];
            }
            return ['success' => 3, 'mensaje' => 'El servidor no pudo enviar la notificación. Revisa la configuración de OneSignal.'];
        }

        return ['success' => 99];
    }




}
