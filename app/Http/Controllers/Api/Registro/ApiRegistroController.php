<?php

namespace App\Http\Controllers\Api\Registro;

use App\Http\Controllers\Controller;
use App\Models\Clientes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ApiRegistroController extends Controller
{
    public function registroCliente(Request $request){

        $rules = array(
            'usuario' => 'required',
            'password' => 'required',
            'version' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        // verificar si existe el usuario
        if(Clientes::where('usuario', $request->usuario)->first()){

            $titulo = "Nota";
            $mensaje = "El usuario ya se encuentra Registrado";

            return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
        }

        if($request->correo != null) {
            // verificar si existe el correo
            if (Clientes::where('correo', $request->correo)->first()) {
                $titulo = "Nota";
                $mensaje = "El correo ya se encuentra Registrado";

                return ['success' => 2, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }
        }

        $fecha = Carbon::now('America/El_Salvador');

        $usuario = new Clientes();
        $usuario->usuario = $request->usuario;
        $usuario->correo = $request->correo;
        $usuario->codigo_correo = null;
        $usuario->password = Hash::make($request->password);
        $usuario->fecha = $fecha;
        $usuario->activo = 1;
        // app nueva (externo = 1) usa external_id de OneSignal: no guarda token
        $usuario->token_fcm = $request->externo == 1 ? null : $request->token_fcm;
        $usuario->appregistro = self::versionConSistema($request->version, $request->userAgent());

        if($usuario->save()){

            // queda con la sesion iniciada: token de Sanctum sin vencimiento
            $token = $usuario->createToken('app-cliente', ['cliente'])->plainTextToken;
            return ['success'=> 3, 'id'=> strval($usuario->id), 'token' => $token];

        }else{
            return ['success' => 99];
        }
    }

    // las apps nuevas solo mandan "3.0.0": el sistema se saca del User-Agent
    // (Android usa OkHttp; iOS usa NSURLSession, que manda CFNetwork/Darwin)
    public static function versionConSistema(?string $version, ?string $agente): string
    {
        $version = trim((string) $version);
        if (preg_match('/^(android|ios)\s/i', $version)) { return $version; }

        $agente = (string) $agente;
        if (preg_match('/CFNetwork|Darwin|iPhone|iPad|iOS/i', $agente)) { return 'ios ' . $version; }
        if (preg_match('/okhttp|Android|Dalvik/i', $agente)) { return 'android ' . $version; }

        return $version;
    }
}
