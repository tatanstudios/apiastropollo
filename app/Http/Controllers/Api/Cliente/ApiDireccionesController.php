<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Models\CarritoExtra;
use App\Models\CarritoTemporal;
use App\Models\Clientes;
use App\Models\DireccionCliente;
use App\Models\Zonas;
use App\Models\ZonasServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiDireccionesController extends Controller
{

    public function listadoDeDirecciones(Request $request){
        $rules = array(
            'id' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails()){return ['success' => 0]; }

        if(Clientes::where('id', $request->id)->first()){

            $conteo = DireccionCliente::where('id_cliente', $request->id)->count();

            if($conteo == 0){
                // cliente no tiene direccion

                $titulo = "Nota";
                $mensaje = "Registrar una nueva Dirección";

                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];

            }else{
                $direccion = DireccionCliente::where('id_cliente', $request->id)->get();

                foreach ($direccion as $info){

                    $infoZona = Zonas::where('id', $info->id_zonas)->first();

                    $minimo = number_format((float)$infoZona->minimo, 2, '.', '');

                    $texto = "Mínimo de Compra: $" . $minimo;

                    $info->minimocompra = $texto;
                }


                return ['success' => 2, 'direcciones' => $direccion];
            }

        }else{
            return ['succcess'=> 99];
        }
    }


    // retorna listado de zonas
    public function puntosZonaPoligonos(){

        $zonas = Zonas::where('activo', 1)->get();

        // una zona puede tener varias areas: se manda una entrada por area, todas con el mismo id,
        // asi las apps (que buscan el primer poligono que contiene la ubicacion) no necesitan cambios
        $puntos = DB::table('zonas_poligono')
            ->whereIn('id_zonas', $zonas->pluck('id'))
            ->orderBy('parte')->orderBy('id')
            ->get(['id_zonas', 'parte', 'latitud AS latitudPoligono', 'longitud AS longitudPoligono'])
            ->groupBy('id_zonas');

        $resultado = [];

        foreach($zonas as $secciones){
            // las apps leen el nombre como "nombreZona"
            $secciones->nombreZona = $secciones->nombre;

            $areas = isset($puntos[$secciones->id]) ? $puntos[$secciones->id]->groupBy('parte') : collect([collect()]);

            foreach($areas as $area){
                $entrada = clone $secciones;
                $entrada->poligonos = $area->map(fn($p) => [
                    'latitudPoligono' => $p->latitudPoligono,
                    'longitudPoligono' => $p->longitudPoligono,
                ])->values();
                $resultado[] = $entrada;
            }
        }

        return [
            'success' => 1,
            'poligono' => $resultado
        ];
    }


    // registrar direccion de cliente
    public function nuevaDireccionCliente(Request $request){
        $reglaDatos = array(
            'id' => 'required',
            'nombre' => 'required',
            'direccion' => 'required',
            'id_zona' => 'required',
            // 8 digitos (El Salvador) o numero de otro pais con codigo: "+" opcional y hasta 15 digitos
            'telefono' => ['required', 'regex:/^\+?[0-9]{8,15}$/']
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if ( $validarDatos->fails()){return ['success' => 0]; }

        if(Clientes::where('id', $request->id)->first()){

            DB::beginTransaction();

            try {

                $di = new DireccionCliente();
                $di->id_zonas = $request->id_zona;
                $di->id_cliente = $request->id;
                $di->nombre = $request->nombre;
                // etiqueta opcional (Casa, Trabajo...); "nombre" es quien recibe
                $di->etiqueta = $request->filled('etiqueta') ? mb_substr(trim($request->etiqueta), 0, 50) : null;
                $di->telefono = $request->telefono;
                $di->direccion = $request->direccion;
                $di->punto_referencia = $request->punto_referencia;
                $di->seleccionado = 1;
                $di->latitud = $request->latitud;
                $di->longitud = $request->longitud;
                $di->latitudreal = $request->latitudreal;
                $di->longitudreal = $request->longitudreal;

                if($di->save()){

                    try {
                        DireccionCliente::where('id_cliente', $request->id)
                            ->where('id', '!=', $di->id)
                            ->update(['seleccionado' => 0]);


                        $infoZonaServicio = ZonasServicio::where('id_zonas', $request->id_zona)->first();


                        // COMO SELECCIONA EN EL MAPA, PREGUNTAR SI TIENE CARRITO EL CLIENTE
                        // SI EL SERVICIO ES EL MISMO, NO BORRAR CARRITO, PERO SI ES OTRA ZONA CON OTRO RESTAURANTE
                        // AHI SI BORRAR CARRITO DE COMPRAS

                        if($infoCarritoTempo = CarritoTemporal::where('id_clientes', $request->id)->first()){

                            if($infoZonaServicio->id_servicios == $infoCarritoTempo->id_servicios){
                                // NO BORRAR CARRITO YA QUE ES DEL MISMO SERVICIO
                            }else{

                                // SI BORRARLE CARRITO

                                CarritoExtra::where('id_carrito_temporal', $infoCarritoTempo->id)->delete();
                                CarritoTemporal::where('id_clientes', $request->id)->delete();
                            }
                        }






                        DB::commit();

                        return ['success' => 1];

                    }  catch (\Exception $ex) {
                        DB::rollback();

                        return ['success' => 99]; // error
                    }
                }else{
                    return ['success' => 99]; // error
                }

            } catch(\Throwable $e){
                DB::rollback();
                return ['success' => 99];
            }
        }else{
            return ['success' => 99];
        }
    }




}
