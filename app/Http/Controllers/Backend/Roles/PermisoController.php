<?php

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Controller;
use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisoController extends Controller
{
    // permiso que da acceso a esta seccion; no se puede borrar ni quitar al propio usuario
    const PERMISO_ROLES = 'sidebar.roles.y.permisos';

    public function __construct(){
        $this->middleware('auth');
        $this->middleware('can:' . self::PERMISO_ROLES);
    }

    //retorna vista de "Permisos" en sisdebar
    public function index(){
        $roles = Role::orderBy('name')->pluck('name', 'id');

        return view('backend.admin.rolesypermisos.permisos', compact('roles'));
    }

    // muestra tabla de usuarios del sistema
    public function tablaUsuarios(){
        $usuarios = Administrador::with('roles')->orderBy('id', 'ASC')->get();

        return view('backend.admin.rolesypermisos.tabla.tablapermisos', compact('usuarios'));
    }

    // busca los roles por id (el formulario los envia como texto; Spatie trataria "4" como nombre).
    // retorna null si falta alguno
    private function buscarRoles($ids)
    {
        if(!is_array($ids) || count($ids) === 0) return null;

        $ids = array_unique(array_map('intval', array_filter($ids, 'is_numeric')));
        $roles = Role::whereIn('id', $ids)->get();

        return ($roles->count() === count($ids) && count($ids) > 0) ? $roles : null;
    }

    /**
     * Indica si el usuario conservaria el permiso de Roles y Permisos con estos roles
     * (o por un permiso directo). Se usa para que nadie se quite su propio acceso.
     */
    public static function conservaAcceso(Administrador $usuario, $rolesFinales): bool
    {
        if($usuario->hasDirectPermission(self::PERMISO_ROLES)){
            return true;
        }

        foreach($rolesFinales as $rol){
            if($rol->permissions->contains('name', self::PERMISO_ROLES)){
                return true;
            }
        }

        return false;
    }

    // crear nuevo usuario
    public function nuevoUsuario(Request $request){

        $validar = Validator::make($request->all(), [
            'nombre' => 'required|max:100',
            'usuario' => 'required|max:25',
            'password' => 'required|min:4|max:16',
            'roles' => 'required|array|min:1',
        ]);

        if ($validar->fails()){ return ['success' => 0]; }

        // verificar que usuario no este registrado
        if(Administrador::where('usuario', $request->usuario)->first()){
            return ['success' => 1];
        }

        $roles = $this->buscarRoles($request->roles);
        if($roles == null){ return ['success' => 0]; }

        $u = new Administrador();
        $u->nombre = $request->nombre;
        $u->usuario = $request->usuario;
        $u->password = bcrypt($request->password);
        $u->activo = 1;
        $u->token_correo = null;
        $u->token_fecha = null;

        if ($u->save()) {
            $u->syncRoles($roles);
            return ['success' => 2];
        } else {
            return ['success' => 3];
        }
    }

    // obtener información de un usuario
    public function infoUsuario(Request $request){
        if($info = Administrador::where('id', $request->id)->first()){

            $roles = Role::orderBy('name')->pluck('name', 'id');

            $idrol = $info->roles->pluck('id');

            return ['success' => 1,
                'info' => $info->only(['id', 'nombre', 'usuario', 'activo']),
                'roles' => $roles,
                'idrol' => $idrol];

        }else{
            return ['success' => 2];
        }
    }

    // editar un usuario
    public function editarUsuario(Request $request){

        $validar = Validator::make($request->all(), [
            'id' => 'required',
            'nombre' => 'required|max:100',
            'usuario' => 'required|max:25',
            'password' => 'nullable|min:4|max:16',
            'roles' => 'required|array|min:1',
            'toggle' => 'required|in:0,1',
        ]);

        if ($validar->fails()){ return ['success' => 0]; }

        $usuario = Administrador::find($request->id);
        if($usuario == null){ return ['success' => 3]; }

        // verificar que usuario no este repetido
        if(Administrador::where('usuario', $request->usuario)
            ->where('id', '!=', $request->id)->first()){
            return ['success' => 1];
        }

        $roles = $this->buscarRoles($request->roles);
        if($roles == null){ return ['success' => 0]; }

        // el usuario logueado no puede desactivarse ni quitarse el acceso a esta seccion
        if($usuario->id == Auth::id()){
            if((int) $request->toggle === 0){
                return ['success' => 4];
            }
            if(!self::conservaAcceso($usuario, $roles)){
                return ['success' => 5];
            }
        }

        $usuario->nombre = $request->nombre;
        $usuario->usuario = $request->usuario;
        $usuario->activo = (int) $request->toggle;

        if($request->password != null){
            $usuario->password = bcrypt($request->password);
        }

        $usuario->save();

        //elimina el rol existente y agrega el nuevo.
        $usuario->syncRoles($roles);

        return ['success' => 2];
    }

    // crear un nuevo Rol
    public function nuevoRol(Request $request){

        $validar = Validator::make($request->all(), ['nombre' => 'required|max:30']);

        if ($validar->fails()){return ['success' => 0];}

        // verificar si existe el rol
        if(Role::where('name', $request->nombre)->first()){
            return ['success' => 1];
        }

        Role::create(['name' => $request->nombre]);

        return ['success' => 2];
    }

    // crear nuevos permisos
    public function nuevoPermisoExtra(Request $request){

        $validar = Validator::make($request->all(), [
            'nombre' => 'required|max:200',
            'descripcion' => 'required|max:200',
        ]);

        if ($validar->fails()){return ['success' => 0];}

        // verificar si existe el permiso
        if(Permission::where('name', $request->nombre)->first()){
            return ['success' => 1];
        }

        Permission::create(['name' => $request->nombre, 'description' => $request->descripcion]);

        return ['success' => 2];
    }

    // borrar permiso global, a todos los roles que lo contenga
    public function borrarPermisoGlobal(Request $request){

        $permiso = is_numeric($request->idpermiso)
            ? Permission::where('id', (int) $request->idpermiso)->first()
            : null;

        if($permiso == null){
            return ['success' => 0];
        }

        // el permiso de esta seccion no se puede borrar, se perderia el acceso
        if($permiso->name === self::PERMISO_ROLES){
            return ['success' => 2];
        }

        $permiso->delete();

        return ['success' => 1];
    }

}
