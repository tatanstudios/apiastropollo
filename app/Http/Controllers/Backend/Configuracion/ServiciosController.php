<?php

namespace App\Http\Controllers\Backend\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\CierresProgramados;
use App\Models\HorarioServicio;
use App\Models\Ordenes;
use App\Models\Servicios;
use App\Models\UsuariosServicios;
use App\Models\Zonas;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ServiciosController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }


    // dia en la tabla horario_servicio: 1 = domingo ... 7 = sabado (Carbon dayOfWeek + 1)
    const DIAS = [2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles', 5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado', 1 => 'Domingo'];

    public function index(){
        $dias = self::DIAS;
        $servicios = Servicios::orderBy('nombre')->get(['id', 'nombre']); // para los cierres programados
        $zonasSinRestaurante = Zonas::whereNotIn('id', DB::table('zonas_servicio')->pluck('id_zonas'))->count();
        return view('backend.admin.configuracion.servicios.vistaservicios', compact('dias', 'servicios', 'zonasSinRestaurante'));
    }

    // tabla
    public function serviciosTabla(){

        $servicios = Servicios::orderBy('nombre')->get();

        $ahora = Carbon::now('America/El_Salvador');
        $hora = $ahora->format('H:i:s');
        $diaHoy = $ahora->dayOfWeek + 1;

        $horarios = HorarioServicio::where('dia', $diaHoy)->get()->keyBy('id_servicios');
        $cierresHoy = CierresProgramados::orderBy('id')->get()->filter(fn($c) => $c->aplicaEn($ahora));

        $zonas = DB::table('zonas_servicio AS zs')
            ->join('zonas AS z', 'z.id', '=', 'zs.id_zonas')
            ->orderBy('z.nombre')
            ->get(['zs.id_servicios', 'z.nombre', 'z.activo', 'z.saturacion'])
            ->groupBy('id_servicios');

        $usuarios = UsuariosServicios::where('bloqueado', 0)->orderBy('nombre')->get()->groupBy('id_servicios');

        // cupones activos asignados a cada restaurante
        $cupones = DB::table('c_producto_gratis AS p')
            ->join('cupones AS c', 'c.id', '=', 'p.id_cupones')
            ->where('c.activo', 1)
            ->groupBy('p.id_servicios')
            ->pluck(DB::raw('COUNT(*)'), 'p.id_servicios');

        foreach ($servicios as $info){
            $h = $horarios[$info->id] ?? null;

            // misma regla que la app al procesar una orden
            $info->cerrado_hoy = !$h || $h->cerrado == 1;
            $info->horario_hoy = $h && $h->cerrado != 1
                ? date('h:i A', strtotime($h->hora1)) . ' – ' . date('h:i A', strtotime($h->hora2))
                : null;
            $info->abierto_ahora = $h && $h->cerrado != 1 && $h->hora1 <= $hora && $hora <= $h->hora2;

            $info->cierre_hoy = $cierresHoy->first(fn($c) => $c->id_servicios === null || (int) $c->id_servicios === (int) $info->id);

            $susZonas = $zonas[$info->id] ?? collect();
            $info->zonas = $susZonas->pluck('nombre')->all();
            $info->zonas_visibles = $susZonas->where('activo', 1)->count();
            $info->zonas_cerradas = $susZonas->where('saturacion', 1)->count();

            $info->usuarios = $usuarios[$info->id] ?? collect();
            $info->cupones = (int) ($cupones[$info->id] ?? 0);
        }

        return view('backend.admin.configuracion.servicios.tablaservicios', compact('servicios'));
    }

    // valida nombre, cupon y los 7 dias del horario; devuelve el mensaje de error o null
    private function validarRestaurante(Request $request): ?string
    {
        $validar = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'cupon' => 'required|in:0,1',
            'dias' => 'required|array|size:7',
            'dias.*.dia' => 'required|integer|between:1,7|distinct',
            'dias.*.cerrado' => 'required|in:0,1',
            'dias.*.hora1' => 'required|date_format:H:i',
            'dias.*.hora2' => 'required|date_format:H:i',
        ]);

        if($validar->fails()){ return 'Revisa el nombre y el horario de los 7 días'; }

        // WhatsApp opcional: si lo escriben debe ser un numero valido
        if(filled($request->whatsapp) && WhatsAppService::normalizarNumero($request->whatsapp) === null){
            return 'El número de WhatsApp no es válido (ej. 7777 8888 o +503 7777 8888)';
        }

        // el restaurante solo maneja horarios dentro del mismo dia
        foreach ($request->dias as $d){
            if(!$d['cerrado'] && $d['hora1'] >= $d['hora2']){
                return 'El ' . self::DIAS[$d['dia']] . ' la hora de cierre debe ser después de la de apertura';
            }
        }

        return null;
    }

    private function guardarHorario(int $idServicio, array $dias): void
    {
        foreach ($dias as $d){
            HorarioServicio::updateOrInsert(
                ['id_servicios' => $idServicio, 'dia' => (int) $d['dia']],
                ['hora1' => $d['hora1'], 'hora2' => $d['hora2'], 'cerrado' => (int) $d['cerrado']]
            );
        }
    }

    public function registrarServicio(Request $request)
    {
        if($error = $this->validarRestaurante($request)){ return ['success' => 0, 'mensaje' => $error]; }

        DB::beginTransaction();

        try {
            $servicio = new Servicios();
            $servicio->nombre = $request->nombre;
            $servicio->utiliza_cupon = (int) $request->cupon;
            $servicio->whatsapp = WhatsAppService::normalizarNumero($request->whatsapp);
            $servicio->save();

            $this->guardarHorario($servicio->id, $request->dias);

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::info('error ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }

    public function informacionServicio(Request $request){

        if($servicio = Servicios::where('id', $request->id)->first()){
            $horario = HorarioServicio::where('id_servicios', $servicio->id)->get(['dia', 'hora1', 'hora2', 'cerrado']);
            return ['success' => 1, 'servicio' => $servicio, 'horario' => $horario];
        }

        return ['success' => 2];
    }

    public function editarServicios(Request $request){

        if(!Servicios::where('id', $request->id)->exists()){ return ['success' => 99]; }
        if($error = $this->validarRestaurante($request)){ return ['success' => 0, 'mensaje' => $error]; }

        DB::beginTransaction();

        try {
            Servicios::where('id', $request->id)->update([
                'nombre' => $request->nombre,
                'utiliza_cupon' => (int) $request->cupon,
                'whatsapp' => WhatsAppService::normalizarNumero($request->whatsapp),
            ]);

            $this->guardarHorario((int) $request->id, $request->dias);

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::info('error ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }


    // ---- usuarios de la app del restaurante (puede haber varios por restaurante) ----

    public function indexUsuariosRestaurantes(){

        $servicios = Servicios::orderBy('nombre')->get(['id', 'nombre']);
        $conUsuario = UsuariosServicios::where('bloqueado', 0)->pluck('id_servicios')->all();
        $sinUsuario = $servicios->whereNotIn('id', $conUsuario)->values();

        return view('backend.admin.usuarios.vistausuarios', compact('servicios', 'sinUsuario'));
    }

    public function tablaUsuariosRestaurantes(){

        $nombres = Servicios::pluck('nombre', 'id');
        $lista = UsuariosServicios::orderBy('bloqueado')->orderBy('nombre')->get();
        $ahora = Carbon::now('America/El_Salvador');

        foreach ($lista as $info){
            $info->restaurante = $nombres[$info->id_servicios] ?? 'Restaurante borrado';

            if($info->fecha_entroapp){
                $fecha = Carbon::parse($info->fecha_entroapp, 'America/El_Salvador');
                $info->ingreso_texto = $fecha->format('d/m/Y h:i A');
                $info->ingreso_hace = $fecha->locale('es')->diffForHumans();
                $info->ingreso_dias = (int) $fecha->diffInDays($ahora);
            }
        }

        return view('backend.admin.usuarios.tablausuarios', compact('lista'));
    }

    // reglas comunes; la contrasena es obligatoria solo al crear
    private function validarUsuarioRestaurante(Request $request, bool $nuevo): ?string
    {
        $validar = Validator::make($request->all(), [
            'idservicio' => ($nuevo ? 'required' : 'nullable') . '|integer|exists:servicios,id',
            'nombre' => 'required|string|max:100',
            'usuario' => 'required|string|max:20',
            'password' => ($nuevo ? 'required' : 'nullable') . '|string|min:4|max:50',
        ], [
            'password.min' => 'La contraseña debe tener al menos 4 caracteres',
            'usuario.max' => 'El usuario puede tener máximo 20 caracteres',
        ]);

        if($validar->fails()){
            return $validar->errors()->first() ?: 'Revisa los datos';
        }
        return null;
    }

    public function registrarUsuarioRestaurante(Request $request){

        if($error = $this->validarUsuarioRestaurante($request, true)){ return ['success' => 0, 'mensaje' => $error]; }

        if(UsuariosServicios::where('usuario', $request->usuario)->exists()){
            return ['success' => 0, 'mensaje' => 'Ya existe otro usuario con ese nombre de usuario'];
        }

        $usuario = new UsuariosServicios();
        $usuario->id_servicios = $request->idservicio;
        $usuario->usuario = $request->usuario;
        $usuario->password = Hash::make($request->password);
        $usuario->token_fcm = null;
        $usuario->bloqueado = 0;
        $usuario->nombre = trim($request->nombre);

        return $usuario->save() ? ['success' => 1] : ['success' => 99];
    }

    public function informacionUsuarioRestaurante(Request $request){

        if($info = UsuariosServicios::where('id', $request->id)->first()){
            return ['success' => 1, 'info' => $info->only(['id', 'id_servicios', 'usuario', 'nombre', 'bloqueado'])];
        }
        return ['success' => 2];
    }

    public function actualizarUsuarioRestaurante(Request $request){

        $info = UsuariosServicios::where('id', $request->id)->first();
        if(!$info){ return ['success' => 99]; }

        if($error = $this->validarUsuarioRestaurante($request, false)){ return ['success' => 0, 'mensaje' => $error]; }

        if(UsuariosServicios::where('usuario', $request->usuario)->where('id', '!=', $info->id)->exists()){
            return ['success' => 0, 'mensaje' => 'Ya existe otro usuario con ese nombre de usuario'];
        }

        $info->nombre = trim($request->nombre);
        $info->usuario = $request->usuario;

        $cambioPassword = filled($request->password);
        if($cambioPassword){ $info->password = Hash::make($request->password); }
        $info->save();

        // contrasena nueva: se cierran las sesiones abiertas con la anterior
        if($cambioPassword){ $info->tokens()->delete(); }

        return ['success' => 1, 'cerroSesion' => $cambioPassword];
    }

    // bloquear (1) o desbloquear (0)
    public function bloquearUsuarioRestaurante(Request $request){

        $validar = Validator::make($request->all(), ['id' => 'required|integer', 'bloqueado' => 'nullable|in:0,1']);
        if($validar->fails()){ return ['success' => 0]; }

        $info = UsuariosServicios::where('id', $request->id)->first();
        if(!$info){ return ['success' => 99]; }

        $bloquear = $request->input('bloqueado', 1) == 1;

        $info->bloqueado = $bloquear ? 1 : 0;
        $info->save();

        // usuario bloqueado: se cierran sus sesiones en la app del restaurante
        if($bloquear){ $info->tokens()->delete(); }

        return ['success' => 1];
    }
}
