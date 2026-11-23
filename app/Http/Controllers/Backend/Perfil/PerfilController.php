<?php

namespace App\Http\Controllers\Backend\Perfil;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Perfil del usuario del panel: cambiar el correo de recuperacion y la contrasena.
 * Ambas acciones piden la contrasena actual (si alguien encuentra la sesion abierta no puede quedarse con la cuenta).
 *
 * Respuestas: success 0 = datos invalidos, 1 = guardado, 2 = contrasena actual incorrecta,
 *             3 = el correo ya lo usa otro usuario del panel
 */
class PerfilController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    // vista del perfil
    public function indexEditarPerfil(){
        $usuario = auth()->user();
        $rol = $usuario->getRoleNames()->implode(', ');

        return view('backend.admin.perfil.vistaperfil', compact('usuario', 'rol'));
    }

    // cambiar contrasena
    public function editarUsuario(Request $request){

        $validar = Validator::make($request->all(), [
            'password_actual' => 'required',
            'password' => 'required|min:4|max:16',
        ]);

        if ($validar->fails()){return ['success' => 0];}

        $usuario = auth()->user();

        if (!Hash::check($request->password_actual, $usuario->password)) {
            return ['success' => 2];
        }

        Administrador::where('id', $usuario->id)
            ->update(['password' => Hash::make($request->password)]);

        return ['success' => 1];
    }

    // cambiar el correo de recuperacion de contrasena
    public function editarCorreo(Request $request){

        $validar = Validator::make($request->all(), [
            'password_actual' => 'required',
            'correo' => 'required|email|max:100',
        ]);

        if ($validar->fails()){return ['success' => 0];}

        $usuario = auth()->user();

        if (!Hash::check($request->password_actual, $usuario->password)) {
            return ['success' => 2];
        }

        $correo = mb_strtolower(trim($request->correo));

        // es el mismo correo que ya tiene: no hay nada que cambiar
        if ($correo === mb_strtolower(trim((string) $usuario->correo))) {
            return ['success' => 1, 'correo' => $correo];
        }

        // la recuperacion busca al usuario por correo: un correo nuevo no puede repetirse
        if (Administrador::where('correo', $correo)->where('id', '!=', $usuario->id)->exists()) {
            return ['success' => 3];
        }

        Administrador::where('id', $usuario->id)->update(['correo' => $correo]);

        return ['success' => 1, 'correo' => $correo];
    }
}
