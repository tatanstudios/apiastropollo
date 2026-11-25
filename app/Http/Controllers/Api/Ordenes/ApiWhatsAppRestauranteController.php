<?php

namespace App\Http\Controllers\Api\Ordenes;

use App\Http\Controllers\Controller;
use App\Models\Servicios;
use App\Models\UsuariosServicios;
use App\Services\WhatsAppBolson;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Seccion WhatsApp de la app restaurante: ver/cambiar el numero de su sucursal
 * que recibe el aviso de cada orden nueva, y cuantos mensajes quedan en el bolson.
 * "id" es el usuario del restaurante (lo fija el middleware propio:restaurante,id).
 */
class ApiWhatsAppRestauranteController extends Controller
{
    public function informacion(Request $request){
        $validar = Validator::make($request->all(), ['id' => 'required']);
        if($validar->fails()){ return ['success' => 0]; }

        $servicio = $this->servicioDe($request->id);
        if(!$servicio){ return ['success' => 0]; }

        return [
            'success' => 1,
            'whatsapp' => $servicio->whatsapp ?? '',
            'disponibles' => WhatsAppBolson::disponibles(),
        ];
    }

    // whatsapp vacio = ya no recibir avisos por WhatsApp
    public function actualizar(Request $request){
        $validar = Validator::make($request->all(), [
            'id' => 'required',
            'whatsapp' => 'nullable|string|max:30',
        ]);
        if($validar->fails()){ return ['success' => 0]; }

        $servicio = $this->servicioDe($request->id);
        if(!$servicio){ return ['success' => 0]; }

        $numero = null;
        if(filled($request->whatsapp)){
            $numero = WhatsAppService::normalizarNumero($request->whatsapp);
            if($numero === null){
                return ['success' => 2, 'mensaje' => 'El número no es válido. Escribe los 8 dígitos o el número con código de país.'];
            }
        }

        Servicios::where('id', $servicio->id)->update(['whatsapp' => $numero]);

        return [
            'success' => 1,
            'whatsapp' => $numero ?? '',
            'disponibles' => WhatsAppBolson::disponibles(),
        ];
    }

    private function servicioDe($idUsuario): ?Servicios
    {
        $idServicio = UsuariosServicios::where('id', $idUsuario)->value('id_servicios');
        return $idServicio ? Servicios::find($idServicio) : null;
    }
}
