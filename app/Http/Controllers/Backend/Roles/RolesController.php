<?php

namespace App\Http\Controllers\Backend\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
        $this->middleware('can:' . PermisoController::PERMISO_ROLES);
    }

    // el formulario envia ids como texto; se buscan siempre por id numerico
    private function buscarRol($id): ?Role
    {
        if(!is_numeric($id)) return null;
        return Role::where('id', (int) $id)->first();
    }

    private function buscarPermiso($id): ?Permission
    {
        if(!is_numeric($id)) return null;
        return Permission::where('id', (int) $id)->first();
    }

    // retorna vista roles
    public function index(){
        return view('backend.admin.rolesypermisos.roles');
    }

    // retorna datos de tabla para roles
    public function tablaRoles(){
        $roles = Role::withCount(['permissions', 'users'])->orderBy('id')->get();
        return view('backend.admin.rolesypermisos.tabla.tablaroles', compact('roles'));
    }

    // vista de permisos de un rol
    public function vistaPermisos($id){
        $rol = $this->buscarRol($id);
        if($rol == null){
            return redirect()->route('admin.roles.index');
        }

        // solo los permisos que el rol aun no tiene
        $asignados = $rol->permissions()->pluck('id');
        $permisos = Permission::whereNotIn('id', $asignados)->orderBy('name')->get(['id', 'name', 'description']);

        return view('backend.admin.rolesypermisos.rolespermisos', compact('id', 'rol', 'permisos'));
    }

    public function tablaRolesPermisos($id){
        // lista de permisos asignados al Rol
        $rol = $this->buscarRol($id);
        $permisos = $rol ? $rol->permissions()->orderBy('name')->get(['id', 'name', 'description']) : collect();

        return view('backend.admin.rolesypermisos.tabla.tablarolespermisos', compact('permisos'));
    }

    // permisos que aun no tiene el rol (para recargar el select despues de agregar/quitar)
    public function permisosDisponibles($id){
        $rol = $this->buscarRol($id);
        if($rol == null){ return ['success' => 0]; }

        $asignados = $rol->permissions()->pluck('id');
        $permisos = Permission::whereNotIn('id', $asignados)->orderBy('name')->get(['id', 'name', 'description']);

        return ['success' => 1, 'permisos' => $permisos];
    }

    // editar nombre del rol
    public function editarRol(Request $request){
        $validar = Validator::make($request->all(), [
            'idrol' => 'required',
            'nombre' => 'required|max:30',
        ]);
        if ($validar->fails()){ return ['success' => 0]; }

        $rol = $this->buscarRol($request->idrol);
        if($rol == null){ return ['success' => 0]; }

        if(Role::where('name', $request->nombre)->where('id', '!=', $rol->id)->first()){
            return ['success' => 1];
        }

        $rol->name = $request->nombre;
        $rol->save();

        return ['success' => 2];
    }

    public function borrarPermiso(Request $request){

        $permission = $this->buscarPermiso($request->idpermiso);
        $role = $this->buscarRol($request->idrol);

        if($permission == null || $role == null){
            return ['success' => 0];
        }

        // no quitar el permiso si el usuario logueado se queda sin acceso a esta seccion
        // (se permite si otro de sus roles tambien tiene el permiso)
        $user = Auth::user();
        if($permission->name === PermisoController::PERMISO_ROLES && $user->hasRole($role)){
            $otrosRoles = $user->roles()->with('permissions')->where('id', '!=', $role->id)->get();
            if(!PermisoController::conservaAcceso($user, $otrosRoles)){
                return ['success' => 2];
            }
        }

        // quitamos el permiso al Rol
        $role->revokePermissionTo($permission);

        return ['success' => 1];
    }

    public function agregarPermiso(Request $request){

        $role = $this->buscarRol($request->idrol);
        $permission = $this->buscarPermiso($request->idpermiso);

        if($permission == null || $role == null){
            return ['success' => 0];
        }

        // asignamos el permiso al Rol
        $role->givePermissionTo($permission);

        return ['success' => 1];
    }

    public function listaTodosPermisos(){
        return view('backend.admin.rolesypermisos.listapermisos');
    }

    public function tablaTodosPermisos(){

        $permisos = Permission::withCount('roles')->orderBy('name')->get();
        return view('backend.admin.rolesypermisos.tabla.tablalistapermisos', compact('permisos'));
    }

    public function borrarRolGlobal(Request $request){

        $role = $this->buscarRol($request->idrol);
        if($role == null){
            return ['success' => 0];
        }

        // no borrar un rol del usuario logueado si con eso pierde el acceso a esta seccion
        $user = Auth::user();
        if($user->hasRole($role)){
            $otrosRoles = $user->roles()->with('permissions')->where('id', '!=', $role->id)->get();
            if(!PermisoController::conservaAcceso($user, $otrosRoles)){
                return ['success' => 2];
            }
        }

        // elimina el rol y todos los permisos asociados; los usuarios con este rol quedan sin rol
        $role->delete();

        return ['success' => 1];
    }
}
