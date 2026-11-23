<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Mail\CorreoPasswordMail;
use App\Models\CarritoExtra;
use App\Models\CarritoTemporal;
use App\Models\Clientes;
use App\Models\DireccionCliente;
use App\Models\HorarioServicio;
use App\Models\Ordenes;
use App\Models\Servicios;
use App\Models\UsuariosServicios;
use App\Models\ZonasServicio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiClienteController extends Controller
{

    // inicio de sesion para clientes
    public function loginCliente(Request $request){

        $rules = array(
            'usuario' => 'required',
            'password' => 'required',
        );

        $validator = Validator::make($request->all(), $rules );

        if ( $validator->fails()){
            return ['success' => 0];
        }

        if($info = Clientes::where('usuario', $request->usuario)->first()){

            if($info->activo == 0){

                $titulo = 'Nota';
                $mensaje = "Su usuario ha sido Eliminado";

                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }

            if (Hash::check($request->password, $info->password)) {

                if($request->externo == 1){
                    // app nueva: el telefono se vincula con OneSignal.login (external_id)
                    Clientes::where('id', $info->id)->update(['token_fcm' => null]);
                }
                else if($request->idfirebase != null){
                    Clientes::where('id', $info->id)->update(['token_fcm' => $request->idfirebase]);
                }

                // inicio sesion: token de Sanctum sin vencimiento para la app cliente
                $token = $info->createToken('app-cliente', ['cliente'])->plainTextToken;
                return ['success' => 2, 'id' => strval($info->id), 'token' => $token, 'mensaje' => "Inicio de sesion correctamente"];

            }else{
                // contraseña incorrecta

                $titulo = 'Nota';
                $mensaje = "Su Contraseña es incorrecta";

                return ['success' => 3, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }

        } else {

            $titulo = 'Nota';
            $mensaje = "Usuario no encontrado";

            // usuario no encontrado
            return ['success' => 4, 'titulo' => $titulo, 'mensaje' => $mensaje];
        }
    }

    // ===================== RECUPERACION DE CONTRASENA POR CORREO =====================
    // Flujo: 1) enviar codigo  2) verificar codigo -> devuelve token  3) actualizar con token
    //
    // Respuestas de success:
    //   0 = datos incompletos
    //   1 = ok
    //   2 = correo no encontrado (enviar) / codigo incorrecto (verificar) / token invalido (actualizar)
    //   3 = codigo o token expirado, debe solicitar uno nuevo
    //   4 = demasiados intentos o solicitudes, debe esperar

    const CODIGO_DIGITOS = 6;
    const CODIGO_VIGENCIA_MINUTOS = 10;
    const CODIGO_MAX_INTENTOS = 5;
    const ENVIOS_MAX_POR_HORA = 3;

    private function codigoExpirado($cliente): bool
    {
        return $cliente->codigo_fecha === null
            || Carbon::parse($cliente->codigo_fecha)->addMinutes(self::CODIGO_VIGENCIA_MINUTOS)->isPast();
    }

    private function limpiarRecuperacion($idCliente): void
    {
        Clientes::where('id', $idCliente)->update([
            'codigo_correo' => null,
            'codigo_fecha' => null,
            'codigo_intentos' => 0,
            'token_recuperacion' => null,
        ]);
    }

    public function enviarCodigoCorreo(Request $request){

        $rules = array(
            'correo' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails()){return ['success' => 0]; }

        if($info = Clientes::where('correo', $request->correo)->first()){

            // limitar reenvios para que no se puedan pedir codigos sin fin
            $claveLimite = 'recuperar-codigo:' . $info->id;
            if (RateLimiter::tooManyAttempts($claveLimite, self::ENVIOS_MAX_POR_HORA)) {
                return ['success' => 4, 'minutos' => (int) ceil(RateLimiter::availableIn($claveLimite) / 60)];
            }
            RateLimiter::hit($claveLimite, 3600);

            // codigo aleatorio criptograficamente seguro
            $codigo = str_pad((string) random_int(0, (10 ** self::CODIGO_DIGITOS) - 1), self::CODIGO_DIGITOS, '0', STR_PAD_LEFT);

            Clientes::where('id', $info->id)->update([
                'codigo_correo' => $codigo,
                'codigo_fecha' => Carbon::now(),
                'codigo_intentos' => 0,
                'token_recuperacion' => null,
            ]);

            $data = ["codigo" => $codigo,
                "usuario" => $info->usuario,
                "minutos" => self::CODIGO_VIGENCIA_MINUTOS];

            Mail::to($request->correo)
                ->send(new CorreoPasswordMail($data));


            return ['success' => 1];
        }else{
            // CORREO NO ENCONTRADO
            return ['success' => 2];
        }
    }

    public function verificarCodigoCorreoPassword(Request $request)
    {
        $rules = array(
            'codigo' => 'required',
            'correo' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ['success' => 0];
        }

        $info = Clientes::where('correo', $request->correo)->first();

        // sin cliente o sin codigo pendiente: se responde igual que codigo incorrecto
        if ($info === null || $info->codigo_correo === null) {
            return ['success' => 2];
        }

        if ($this->codigoExpirado($info)) {
            $this->limpiarRecuperacion($info->id);
            return ['success' => 3];
        }

        if ($info->codigo_intentos >= self::CODIGO_MAX_INTENTOS) {
            // el codigo queda inutilizado, debe pedir otro
            $this->limpiarRecuperacion($info->id);
            return ['success' => 4];
        }

        if (!hash_equals((string) $info->codigo_correo, (string) $request->codigo)) {
            Clientes::where('id', $info->id)->increment('codigo_intentos');
            return ['success' => 2];
        }

        // codigo correcto: se consume y se entrega un token de un solo uso
        // que vence en el mismo plazo, contado desde ahora
        $token = Str::random(64);

        Clientes::where('id', $info->id)->update([
            'codigo_correo' => null,
            'codigo_fecha' => Carbon::now(),
            'codigo_intentos' => 0,
            'token_recuperacion' => hash('sha256', $token),
        ]);

        return ['success' => 1, 'id' => $info->id, 'token' => $token];
    }

    // ACTUALIZAR CONTRASENA RECUPERACION POR CORREO
    // exige el token entregado al verificar el codigo
    public function actualizarPasswordClienteCorreo(Request $request){

        $rules = array(
            'id' => 'required',
            'token' => 'required|string',
            'password' => 'required|min:4'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        $info = Clientes::where('id', $request->id)->first();

        if ($info === null || $info->token_recuperacion === null
            || !hash_equals($info->token_recuperacion, hash('sha256', (string) $request->token))) {
            return ['success' => 2];
        }

        if ($this->codigoExpirado($info)) {
            $this->limpiarRecuperacion($info->id);
            return ['success' => 3];
        }

        Clientes::where('id', $info->id)->update(['password' => Hash::make($request->password)]);
        // contrasena nueva: se cierran todas las sesiones abiertas con la anterior
        $info->tokens()->delete();
        $this->limpiarRecuperacion($info->id);
        RateLimiter::clear('recuperar-codigo:' . $info->id);

        return ['success' => 1];
    }


    public function informacionCliente(Request $request){

        $rules = array(
            'id' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if($info = Clientes::where('id', $request->id)->first()){

            $correo = $info->correo;

            return ['success' => 1, 'usuario' => $info->usuario, 'correo' => $correo];
        }else{
            return ['success' => 2];
        }
    }


    public function actualizarCorreoCliente(Request $request){


        $rules = array(
            'id' => 'required',
            'correo' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if($info = Clientes::where('id', $request->id)->first()){


            Clientes::where('id', $info->id)->update(['correo' => $request->correo]);

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }


    public function actualizarCorreoClienteVersion2(Request $request){
        $rules = array(
            'id' => 'required',
            'correo' => 'required',
            'usuario' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if(Clientes::where('id', $request->id)->first()){

            // NO USUARIO REPETIDO
            if(Clientes::where('correo', $request->correo)->where('id', '!=', $request->id)->first()){
                // USUARIO REPETIDO
                return ['success' => 1];
            }

            if(Clientes::where('usuario', $request->usuario)->where('id', '!=', $request->id)->first()){
                // USUARIO REPETIDO
                return ['success' => 2];
            }

            // ACTUALIZAR
            Clientes::where('id', $request->id)->update(['correo' => $request->correo, 'usuario' => $request->usuario]);

            return ['success' => 3];
        }else{
            return ['success' => 2];
        }
    }


    public function informacionHorarioRestaurante(Request $request){

        $rules = array(
            'id' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if(Clientes::where('id', $request->id)->first()){

            // REGLA: SIEMPRE DEBERA HABER UNA ZONA SERVICIO, SINO ERROR

            // obtener direccion actual del cliente
            $infoDireccion = DireccionCliente::where('id_cliente', $request->id)
                ->where('seleccionado', 1)->first();

            $infoZonaServicio = ZonasServicio::where('id_zonas', $infoDireccion->id_zonas)->first();

            $infoServicio = Servicios::where('id', $infoZonaServicio->id_servicios)->first();

            $infoHorario = HorarioServicio::where('id_servicios', $infoZonaServicio->id_servicios)->get();


            foreach ($infoHorario as $info){
                $hora1 = date("h:i A", strtotime($info->hora1));
                $hora2 = date("h:i A", strtotime($info->hora2));

                if($info->cerrado == 1){
                    $info->horario = "Cerrado";
                    // para ios
                    $info->fechaformat = "Cerrado";

                }else{
                    $info->horario = $hora1 . " / " . $hora2;
                    // para ios
                    $info->fechaformat = $hora1 . " / " . $hora2;
                }

            }

            return ['success' => 1,
                'restaurante' => $infoServicio->nombre,
                'horario' => $infoHorario,];
        }else{
            return ['success' => 2];
        }

    }



    // ACTUALIZAR CONTRASENA DENTRO DEL PERFIL
    public function actualizarPasswordClientePerfil(Request $request){
        $rules = array(
            'id' => 'required',
            'password' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if(Clientes::where('id', $request->id)->first()){

            Clientes::where('id', $request->id)->update(['password' => Hash::make($request->password)]);

            // se cierran las sesiones de otros dispositivos; la actual sigue activa
            $actual = $request->user()?->currentAccessToken();
            Clientes::find($request->id)?->tokens()->when($actual, fn ($q) => $q->where('id', '!=', $actual->id))->delete();

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }



    public function infoBorrarCarritoComprasCliente(Request $request){


        $rules = array(
            'id' => 'required', // id cliente
        );

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails()){return ['success' => 0]; }

        if($info = Clientes::where('id', $request->id)->first()){

            $mensaje = "Al realizar una Orden, puedes elegir si borrar el carrito de compras o mantener los productos para realizar una orden nuevamente";

            return ['success' => 1, 'opcion' => $info->borrar_carrito, 'mensaje' => $mensaje];
        }else{
            return ['success' => 2];
        }
    }


    public function actualizarOpcionCarritoCliente(Request $request){


        $rules = array(
            'id' => 'required', // id cliente
            'disponible' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails()){return ['success' => 0]; }

        if(Clientes::where('id', $request->id)->first()){

            Clientes::where('id', $request->id)->update([
                'borrar_carrito' => $request->disponible]);

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }


    public function seleccionarDireccionParaOrdenes(Request $request){

        $reglaDatos = array(
            'dirid' => 'required',
            'id' => 'required', // id cliente
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if ( $validarDatos->fails()){return ['success' => 0]; }

        if(Clientes::where('id', $request->id)->first()){

            if($infoDireCliente = DireccionCliente::where('id_cliente', $request->id)
                ->where('id', $request->dirid)->first()){

                DB::beginTransaction();

                try {

                    // setear a 0 todas las direcciones del cliente
                    DireccionCliente::where('id_cliente', $request->id)->update(['seleccionado' => 0]);

                    // setear a 1 el id de la direccion que envia el usuario
                    DireccionCliente::where('id', $request->dirid)->update(['seleccionado' => 1]);


                    $infoZonaServicio = ZonasServicio::where('id_zonas', $infoDireCliente->id_zonas)->first();


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

                    // direccion seleccionda
                    return ['success' => 1];

                }catch(\Throwable $e){
                    Log::info('ee' .$e);
                    DB::rollback();
                    // error
                    return ['success' => 99];
                }

            }else{
                // cliente no encontrado
                return ['success' => 99];
            }
        }else{
            return ['success' => 99];
        }
    }



    public function eliminarDireccionSeleccionadaCliente(Request $request){

        $reglaDatos = array(
            'id' => 'required',
            'dirid' => 'required',
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if ( $validarDatos->fails()){return ['success' => 0]; }

        if($infoDire = DireccionCliente::where('id', $request->dirid)
            ->where('id_cliente', $request->id)->first()){

            DB::beginTransaction();

            try {

                $countDireccionCliente = DireccionCliente::where('id_cliente', $request->id)->count();

                if($countDireccionCliente > 1){

                    // verificar si esta direccion era la que estaba seleccionada, para poner una aleatoria
                    $infoDireccionCliente = DireccionCliente::where('id', $infoDire->id)->first();

                    // borrar direccion
                    DireccionCliente::where('id', $infoDire->id)->delete();

                    // si era la seleccionada poner aleatoria, sino no hacer nada
                    if($infoDireccionCliente->seleccionado == 1){

                        // volver a buscar la primera linea y poner seleccionado
                        $infoDireccionNueva = DireccionCliente::where('id_cliente', $request->id)->first();
                        DireccionCliente::where('id', $infoDireccionNueva->id)->update(['seleccionado' => 1]);
                    }

                     DB::commit();

                    // BORRADA CORRECTAMENTE
                    return ['success' => 1];
                }else{

                    // NO SE PUEDE ELIMINAR LA DIRECCION
                    return ['success' => 2];
                }
            }catch(\Throwable $e){
                DB::rollback();
                return ['success' => 3];
            }
        }else{
            return ['success' => 3];
        }
    }


    // CERRAR SESION (app cliente o restaurante): revoca el token de este dispositivo
    public function cerrarSesion(Request $request){

        $request->user()?->currentAccessToken()?->delete();

        return ['success' => 1];
    }


    //***********************************************************************************


    public function loginRestaurante(Request $request){


        $rules = array(
            'usuario' => 'required',
            'password' => 'required',
        );

        // idfirebase

        $validator = Validator::make($request->all(), $rules );

        if ( $validator->fails()){
            return ['success' => 0];
        }

        if($info = UsuariosServicios::where('usuario', $request->usuario)->first()){


            // EL USUARIO PUEDE ESTAR BLOQUEADO

            if($info->bloqueado == 1){

                $titulo = 'Nota';
                $mensaje = "Su usuario ha sido bloqueado";

                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }

            if (Hash::check($request->password, $info->password)) {

                if($request->externo == 1){
                    // app nueva: el telefono se vincula con OneSignal.login (external_id);
                    // se borra el token viejo para que no le lleguen avisos repetidos
                    UsuariosServicios::where('id', $info->id)->update(['token_fcm' => null]);
                }
                else if($request->idfirebase != null){
                    UsuariosServicios::where('id', $info->id)->update(['token_fcm' => $request->idfirebase]);
                }

                // inicio sesion: token de Sanctum sin vencimiento para la app del restaurante
                $token = $info->createToken('app-restaurante', ['restaurante'])->plainTextToken;
                return ['success' => 2, 'id' => strval($info->id), 'token' => $token, 'mensaje' => "Inicio de sesion correctamente"];

            }else{
                // contraseña incorrecta

                $titulo = 'Nota';
                $mensaje = "Su Contraseña es incorrecta";

                return ['success' => 3, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }

        } else {

            $titulo = 'Nota';
            $mensaje = "Usuario no encontrado";

            // usuario no encontrado
            return ['success' => 4, 'titulo' => $titulo, 'mensaje' => $mensaje];
        }
    }


}
