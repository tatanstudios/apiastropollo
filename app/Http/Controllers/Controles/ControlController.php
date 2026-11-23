<?php

namespace App\Http\Controllers\Controles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControlController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    // verifica que usuario inicio sesión y redirecciona a su vista según ROL
    public function indexRedireccionamiento(){

        $user = Auth::user();

        // pantalla inicial segun los permisos del usuario (no segun el nombre del rol),
        // en orden de prioridad: la primera seccion que pueda ver
        $inicio = [
            'sidebar.ordenes' => 'index.ordenes',
            'sidebar.zonas' => 'index.servicios.listado',
            'sidebar.usuarios' => 'index.usuarios.restaurantes',
            'sidebar.reportes' => 'index.reportes',
            'sidebar.roles.y.permisos' => 'admin.roles.index',
        ];

        // no tiene ningun permiso de vista, redirigir a pantalla sin permisos
        $ruta = 'no.permisos.index';

        foreach($inicio as $permiso => $rutaPermiso){
            if($user->can($permiso)){
                $ruta = $rutaPermiso;
                break;
            }
        }

        // el panel usa AdminLTE (config/adminlte.php): cada pantalla es una pagina completa
        return redirect()->route($ruta);
    }

    // redirecciona a vista sin permisos
    public function indexSinPermiso(){
        return view('errors.403');
    }
}
