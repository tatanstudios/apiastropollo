<?php

namespace App\Http\Controllers\Backend\Configuracion;


use App\Services\OneSignalService;
use App\Http\Controllers\Controller;
use App\Models\Clientes;
use App\Models\DireccionCliente;
use App\Models\Servicios;
use App\Models\ZonasServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OneSignal;

class NotificacionesController extends Controller
{

    public function __construct(){
        $this->middleware('auth');
    }


    public function indexNotificacionPorRestaurante(){

        $restaurantes = Servicios::orderBy('nombre')->get();

        return view('backend.admin.notificaciones.restaurantes.vistanotiporrestaurante', compact('restaurantes'));
    }


    public function enviarNotificacionPorServicio(Request $request){


        $rules = array(
            'idservicio' => 'required',
            'titulo' => 'required',
            'mensaje' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }


        // OBTENER TODAS LAS ZONAS ASIGNADAS AL RESTAURANTE

        $arrayZonas = ZonasServicio::where('id_servicios', $request->idservicio)->get();

        $pilaIdZonas = array();

        foreach ($arrayZonas as $info){
            array_push($pilaIdZonas, $info->id_zonas);
        }

        $arrayDirecciones = DireccionCliente::whereIn('id_zonas', $pilaIdZonas)
            ->where('seleccionado', 1)
            ->get();

        // a todos los clientes de esas zonas: por external_id (app nueva) y token_fcm (apps viejas)
        $idsClientes = $arrayDirecciones->pluck('id_cliente')->unique()->values()->all();

        if(!empty($idsClientes)){
            OneSignalService::enviarClientes($idsClientes, $request->titulo, $request->mensaje);
        }


        return ['success' => 1];
    }


    public function indexListaDireccioneNotificacion(){
        return view('backend.admin.notificaciones.clientes.vistanotiporcliente');
    }


    public function tablaClientesDireccionesNotificacion(){

        $lista = DireccionCliente::orderBy('telefono')->get();

        return view('backend.admin.notificaciones.clientes.tablanotiporcliente', compact('lista'));
    }


    public function informacionCliente(Request $request){


        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        if($info = DireccionCliente::where('id', $request->id)->first()){

            // con la app nueva no hay token guardado (usa external_id): siempre se puede intentar
            return ['success' => 1, 'info' => $info];
        }else{
            return ['success' => 99];
        }
    }


    public function enviarNotiPorCliente(Request $request){


        $regla = array(
            'id' => 'required', // id de direccion
            'titulo' => 'required',
            'mensaje' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        if($info = DireccionCliente::where('id', $request->id)->first()){

            // por external_id (app nueva) y token_fcm (apps viejas)
            $resultado = OneSignalService::enviarClientes([$info->id_cliente], $request->titulo, $request->mensaje);

            if($resultado === 'enviado'){
                return ['success' => 1];
            }else{
                // el cliente no tiene telefono vinculado (o fallo OneSignal)
                return ['success' => 2];
            }
        }else{
            return ['success' => 99];
        }
    }





}
