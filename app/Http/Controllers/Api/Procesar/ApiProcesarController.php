<?php

namespace App\Http\Controllers\Api\Procesar;

use App\Http\Controllers\Controller;
use App\Jobs\NotificarNuevaOrden;
use App\Models\CarritoExtra;
use App\Models\CarritoTemporal;
use App\Models\Categorias;
use App\Models\CierresProgramados;
use App\Models\Clientes;
use App\Models\Cupones;
use App\Models\CuponProductoGratis;
use App\Models\DireccionCliente;
use App\Models\HorarioServicio;
use App\Models\Ordenes;
use App\Models\OrdenesDescripcion;
use App\Models\OrdenesDirecciones;
use App\Models\SubCategorias;
use App\Models\UsuariosServicios;
use App\Models\Zonas;
use App\Models\ZonasServicio;
use App\Services\Modificadores;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OneSignal;

class ApiProcesarController extends Controller
{
    public function enviarOrdenRestaurante(Request $request){


        $reglaDatos = array(
            'clienteid' => 'required',
            'aplicacupon' => 'required',
           // 'version' => 'required'
        );

        // nota
        // cupon
        // idfirebase

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }


        if($infoCarritoTempo = CarritoTemporal::where('id_clientes', $request->clienteid)->first()){


            // VERIFICAR QUE ZONA ESTE DISPONIBLE

            // ** BUSCAR ESTE SERVICIO DONDE ESTA ASIGNADO, SI ES BORRADO MOSTRAR SERVICIO CERRADO

            // la zona es la de la direccion seleccionada del cliente (de ahi salen cierre, horario,
            // minimo y tiempo extra); antes se tomaba la primera zona del restaurante.
            // Si la direccion no es de este restaurante se mantiene el comportamiento anterior.
            $infoZonaServicio = null;
            if($infoDireccionZona = DireccionCliente::where('id_cliente', $request->clienteid)
                ->where('seleccionado', 1)->first()){
                $infoZonaServicio = ZonasServicio::where('id_zonas', $infoDireccionZona->id_zonas)
                    ->where('id_servicios', $infoCarritoTempo->id_servicios)
                    ->first();
            }
            if($infoZonaServicio == null){
                $infoZonaServicio = ZonasServicio::where('id_servicios', $infoCarritoTempo->id_servicios)->first();
            }

            if($infoZonaServicio){

                $infoZona = Zonas::where('id', $infoZonaServicio->id_zonas)->first();



                // REGLA: EL RESTAURANTE TIENE CERRADO ESTA ZONA
                if($infoZona->saturacion == 1){
                    $titulo = "Nota";
                    $mensaje = $infoZona->mensaje_bloqueo;
                    return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                }

                // REGLA: CIERRE PROGRAMADO POR FECHA (DEL RESTAURANTE O DE TODOS)
                if($cierreProgramado = CierresProgramados::vigenteHoy((int) $infoCarritoTempo->id_servicios)){
                    return ['success' => 1, 'titulo' => 'Nota', 'mensaje' => $cierreProgramado->mensaje];
                }


                // REGLA: VERIFICAR HORARIO DE LA ZONA


                $numSemana = [
                    0 => 1, // domingo
                    1 => 2, // lunes
                    2 => 3, // martes
                    3 => 4, // miercoles
                    4 => 5, // jueves
                    5 => 6, // viernes
                    6 => 7, // sabado
                ];

                $getValores = Carbon::now('America/El_Salvador');
                $getDiaHora = $getValores->dayOfWeek;
                $diaSemana = $numSemana[$getDiaHora];
                $hora = $getValores->format('H:i:s');

                $horaZona = Zonas::where('id', $infoZonaServicio->id_zonas)
                    ->where('hora_abierto_delivery', '<=', $hora)
                    ->where('hora_cerrado_delivery', '>=', $hora)
                    ->get();

                if(count($horaZona) >= 1){
                    // ABIERTO
                }else{
                    $titulo = "Nota";
                    $mensaje = "El Horario a domicilio para su Dirección esta cerrado";
                    return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                }


                // REGLA DE PRODUCTOS DISPONIBLE
                // CATEGORIAS ACTIVAS (CON HORARIO Y SIN HORARAIO)
                // SUB CATEGORIAS ACTIVAS
                // PRODUCTO ACTIVO

                $estadoProductoActivo = false;


                $producto = DB::table('productos AS p')
                    ->join('carrito_extra AS c', 'c.id_producto', '=', 'p.id')
                    ->select('p.id AS productoID', 'p.nombre', 'c.cantidad',
                        'p.imagen', 'p.precio', 'p.activo', 'c.nota_producto', 'c.id AS carritoid', 'p.utiliza_imagen', 'p.id_subcategorias')
                    ->where('c.id_carrito_temporal', $infoCarritoTempo->id)
                    ->get();


                $totalCarrito = 0;

                // verificar cada producto
                foreach ($producto as $pro) {

                    // PRODUCTO NO ACTIVO
                    if($pro->activo == 0){


                        $estadoProductoActivo = true;
                    }

                    // CONOCER SI LA SUB CATEGORIA ESTA ACTIVA
                    $infoSubCate = SubCategorias::where('id', $pro->id_subcategorias)->first();

                    if($infoSubCate->activo == 0){

                        $estadoProductoActivo = true;
                    }

                    // CONOCER SI LA CATEGORIA DEL PRODUCTO ESTA ACTIVA
                    $infoCategoria = Categorias::where('id', $infoSubCate->id_categorias)->first();

                    if($infoCategoria->activo == 0){

                        $estadoProductoActivo = true;
                    }


                    if($infoCategoria->usa_horario == 1){
                        // CONOCER SI LA CATEGORIA TIENE HORARIO Y VER SI ESTA DISPONIBLE
                        $horaCategoria = Categorias::where('id', $infoSubCate->id_categorias)
                            ->where('activo', 1)
                            ->where('usa_horario', 1)
                            ->where('hora_abre', '<=', $hora)
                            ->where('hora_cierra', '>=', $hora)
                            ->get();

                        if(count($horaCategoria) >= 1){
                            // ABIERTO
                        }else{

                            $estadoProductoActivo = true;
                        }
                    }


                    // OPCIONES ELEGIDAS: se validan con las reglas actuales del producto
                    $validacion = Modificadores::validarFila((int) $pro->productoID, (int) $pro->carritoid);
                    $pro->modificadoresOk = $validacion['ok'];
                    $pro->modificadoresMensaje = $validacion['mensaje'];
                    $pro->lineas = $validacion['lineas'];

                    // precio por unidad = producto + opciones (es el que se guarda en la orden)
                    $pro->precioUnidad = round((float) $pro->precio + $validacion['extra'], 2);

                    // MULTIPLICAR PARA SACAR TOTAL
                    $multi = $pro->precioUnidad * $pro->cantidad;
                    $totalCarrito = $totalCarrito + $multi;
                }


                // REGLA: UNO O VARIOS PRODUCTOS NO ESTAN DISPONIBLES

                if($estadoProductoActivo){
                    $titulo = "Revisión";
                    $mensaje = "Uno o más Productos no estan disponibles, revisar el Carrito de Compras. Gracias";
                    return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                }

                // REGLA: LAS OPCIONES DE UN PRODUCTO CAMBIARON Y YA NO SON VALIDAS
                foreach ($producto as $pro) {
                    if(!$pro->modificadoresOk){
                        $titulo = "Revisa las opciones";
                        $mensaje = "«" . $pro->nombre . "»: " . $pro->modificadoresMensaje;
                        return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                    }
                }


                // REGLA: VALIDAR HORARIO DEL RESTAURANTE


                $horario = DB::table('horario_servicio AS h')
                    ->join('servicios AS s', 's.id', '=', 'h.id_servicios')
                    ->where('h.id_servicios', $infoCarritoTempo->id_servicios)
                    ->where('h.dia', $diaSemana)
                    ->where('h.hora1', '<=', $hora)
                    ->where('h.hora2', '>=', $hora)
                    ->get();

                if(count($horario) >= 1){

                }else{
                    // cerrado

                    $titulo = "Nota";
                    $mensaje = "El Restaurante esta cerrado";
                    return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                }


                // REGLA: VALIDAD DIA CERRADO DEL RESTAURANTE

                $cerradoHoy = HorarioServicio::where('id_servicios', $infoCarritoTempo->id_servicios)
                    ->where('dia', $diaSemana)
                    ->first();


                if($cerradoHoy->cerrado == 1){

                    $titulo = "Nota";
                    $mensaje = "El Restaurante esta cerrado este Día";
                    return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                }


                /// ************** VALIDAR CUPONES PARA DESPUES VALIDAR MINIMO DE COMPRA POR ZONA **********

                // una descripcion si se aplico el cupon para guardar historial
                $mensajeCupon = null;

                // id del cupon donde se suma el contador
                $idCupones = null;


                if($request->aplicacupon == 1){


                    // verificar que tipo de cupon es y si aun es valido
                    if($infoCupon = Cupones::where('texto_cupon', $request->cupon)->first()){

                        // CUPON ACTIVO
                        if($infoCupon->activo == 0){
                            $titulo = "Nota";
                            $mensaje = "Cupón no válido";
                            return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                        }


                        // CUPON LIMITE ALCANZADO
                        if($infoCupon->contador >= $infoCupon->uso_limite){
                            $titulo = "Nota";
                            $mensaje = "Cupón no válido";
                            return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                        }


                        // SOLO EXISTE EL CUPON DE PRODUCTO GRATIS

                        if($infoCupon->id_tipo_cupon == 1){

                            // VERIFICAR QUE EL SERVICIO DONDE ESTOY COMPRANDO ACEPTA ESTE CUPON
                            if($infoCuponProGratis = CuponProductoGratis::where('id_cupones', $infoCupon->id)
                                ->where('id_servicios', $infoCarritoTempo->id_servicios)
                                ->first()){


                                $idCupones = $infoCupon->id;

                                $mensajeCupon = "Aplico para producto Gratis: " . $infoCuponProGratis->nombre;

                            }else{
                                $titulo = "Nota";
                                $mensaje = "Cupón no válido";
                                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                            }
                        }

                        // Cupon no encontrado
                        else{

                            $titulo = "Nota";
                            $mensaje = "Cupón no válido";
                            return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];

                        }

                    }else{

                        $titulo = "Nota";
                        $mensaje = "Cupón no válido";
                        return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                    }

                }



                // MINIMO DE COMPRA POR ZONA

                if($totalCarrito < $infoZona->minimo){

                    $minimo = '$' . number_format((float)$infoZona->minimo, 2, '.', ',');

                    $titulo = "Nota";
                    $mensaje = "El mínimo de compra para su dirección actual es . " . $minimo;
                    return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
                }


                $infoCliente = Clientes::where('id', $request->clienteid)->first();
                $fecha = Carbon::now('America/El_Salvador');



                //*********** VALIDACIONES COMPLETADAS  **************

                // todo o nada: si algo falla, no queda la orden sin direccion o sin productos
                DB::beginTransaction();

                try {

                    // SUMAR CONTADOR DE USO DEL CUPON SI APLICA

                    if($request->aplicacupon == 1){

                        $contador = $infoCupon->contador + 1;

                        Cupones::where('id', $infoCupon->id)->update(['contador' => $contador]);
                    }



                    // GUARDAR LA ORDEN

                    $orden = new Ordenes();
                    $orden->id_cliente = $request->clienteid;
                    $orden->id_servicio = $infoCarritoTempo->id_servicios;
                    $orden->id_zona = $infoZona->id;
                    $orden->nota_orden = $request->nota;
                    $orden->total_orden = $totalCarrito;
                    $orden->fecha_orden = $fecha;
                    $orden->fecha_estimada = null;
                    $orden->estado_iniciada = 0;
                    $orden->fecha_iniciada = null;
                    $orden->nota_cancelada = null;
                    $orden->id_cupones = $idCupones;
                    $orden->id_cupones_copia = $idCupones;
                    $orden->mensaje_cupon = $mensajeCupon;
                    $orden->visible = 1;
                    $orden->cancelado_por = 0;
                    $orden->conteo_notificacion = 0;

                    $orden->save();


                    $infoDireccion = DireccionCliente::where('id_cliente', $request->clienteid)
                        ->where('seleccionado', 1)
                        ->first();

                    // GUARDAR LA DIRECCION DEL CLIENTE

                    $dirCliente = new OrdenesDirecciones();
                    $dirCliente->id_ordenes = $orden->id;

                    $dirCliente->nombre = $infoDireccion->nombre;
                    $dirCliente->direccion = $infoDireccion->direccion;
                    $dirCliente->telefono = $infoDireccion->telefono;
                    $dirCliente->referencia = $infoDireccion->punto_referencia;
                    $dirCliente->latitud = $infoDireccion->latitud;
                    $dirCliente->longitud = $infoDireccion->longitud;
                    $dirCliente->latitudreal = $infoDireccion->latitudreal;
                    $dirCliente->longitudreal = $infoDireccion->longitudreal;
                    // "android 4.0.0" / "ios 4.0.0": el sistema se detecta del telefono que envia la orden
                    $dirCliente->appversion = \App\Http\Controllers\Api\Registro\ApiRegistroController::versionConSistema($request->version, $request->userAgent());

                    $dirCliente->save();


                    // guadar todos los productos de esa orden
                    // el precio guardado es por unidad e incluye las opciones; las opciones se copian
                    // para que la orden no cambie si luego se editan los modificadores del producto
                    foreach($producto as $p){

                        $data = array('id_ordenes' => $orden->id,
                            'id_producto' => $p->productoID,
                            'cantidad' => $p->cantidad,
                            'nota' => $p->nota_producto,
                            'precio' => $p->precioUnidad);
                        $idDescripcion = OrdenesDescripcion::insertGetId($data);

                        Modificadores::copiarAOrden($idDescripcion, $p->lineas);
                    }



                    // BORRAR CARRITO DE COMPRAS SIEMPRE

                    CarritoExtra::where('id_carrito_temporal', $infoCarritoTempo->id)->delete();
                    CarritoTemporal::where('id_clientes', $request->clienteid)->delete();



                    if($request->externo == 1){
                        if($infoCliente->token_fcm != null){
                            Clientes::where('id', $infoCliente->id)->update(['token_fcm' => null]);
                        }
                    }
                    else if($request->idfirebase != null){
                        Clientes::where('id', $infoCliente->id)->update(['token_fcm' => $request->idfirebase]);
                    }



                    DB::commit();

                    // aviso al restaurante (push; luego WhatsApp). Corre despues de responder al
                    // cliente y si falla no afecta la orden, que ya quedo guardada.
                    NotificarNuevaOrden::despachar($orden->id);

                    $titulo = "Orden #" . $orden->id;
                    $mensaje = "Espere la notificación del Restaurante para verificar su orden";
                    return ['success' => 10, 'titulo' => $titulo, 'mensaje' => $mensaje, 'idorden' => $orden->id];

                } catch (\Throwable $e) {
                    Log::info('error ' . $e);
                    DB::rollback();
                    return ['success' => 99];
                }

            }else{

                // no encontrado en zona servicio
                $titulo = "Nota";
                $mensaje = "El Restaurante esta cerrado";
                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }


        }else{
            // este seria raro que pasara
            $titulo = "Nota";
            $mensaje = "Carrito de compras no encontrado";
            return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
        }
    }


    // OBSOLETO: el aviso al restaurante ahora lo envia el servidor al guardar la orden
    // (Job NotificarNuevaOrden). Se deja para las apps cliente ya instaladas que todavia
    // llaman este endpoint: responde igual que antes pero NO envia nada, para no duplicar el aviso.

    public function notificacionOrdenParaRestaurante(Request $request){

        $validator = Validator::make($request->all(), ['id' => 'required']);
        if ($validator->fails()){ return ['success' => 0]; }

        return Ordenes::where('id', $request->id)->exists()
            ? ['success' => 1]
            : ['success' => 2];
    }
}
