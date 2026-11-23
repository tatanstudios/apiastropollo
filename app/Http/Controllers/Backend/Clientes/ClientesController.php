<?php

namespace App\Http\Controllers\Backend\Clientes;

use App\Http\Controllers\Controller;
use App\Models\Clientes;
use App\Models\DireccionCliente;
use App\Models\Servicios;
use App\Models\ZonasServicio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }


    // clientes reservados que no se muestran (antiguo call center y acceso de app)
    const RESERVADOS = [1, 2, 3];

    public function index(){

        $hace30 = self::hace30();
        $base = fn () => self::consultaClientes();

        $resumen = [
            'todos' => $base()->count(),
            'activo' => $base()->where('c.activo', 1)->count(),
            'desactivado' => $base()->where('c.activo', '!=', 1)->count(),
            'nuevo' => $base()->where('c.fecha', '>=', $hace30)->count(),
            'sinordenes' => $base()->whereNull('o.total')->count(),
        ];

        return view('backend.admin.clientes.lista.vistaclientes', compact('resumen'));
    }

    private static function hace30(): string
    {
        return Carbon::now('America/El_Salvador')->subDays(30)->format('Y-m-d H:i:s');
    }

    // clientes con su conteo de ordenes (para la tarjeta "Nunca han pedido")
    private static function consultaClientes()
    {
        $ordenes = DB::table('ordenes')->select('id_cliente', DB::raw('COUNT(*) AS total'), DB::raw('MAX(fecha_orden) AS ultima'))->groupBy('id_cliente');

        return DB::table('clientes AS c')
            ->leftJoinSub($ordenes, 'o', 'o.id_cliente', '=', 'c.id')
            ->whereNotIn('c.id', self::RESERVADOS);
    }

    // datos para DataTables en modo servidor: solo se trae la pagina que se ve
    public function tablaClientes(Request $request){

        $hace30 = self::hace30();
        $consulta = self::consultaClientes();

        // filtro de las tarjetas de resumen
        switch ($request->get('filtro')) {
            case 'activo': $consulta->where('c.activo', 1); break;
            case 'desactivado': $consulta->where('c.activo', '!=', 1); break;
            case 'nuevo': $consulta->where('c.fecha', '>=', $hace30); break;
            case 'sinordenes': $consulta->whereNull('o.total'); break;
        }

        $total = (clone $consulta)->count();

        $buscar = trim((string) data_get($request->get('search'), 'value', ''));
        if ($buscar !== '') {
            $consulta->where(function ($q) use ($buscar) {
                $q->where('c.usuario', 'like', "%{$buscar}%")
                    ->orWhere('c.correo', 'like', "%{$buscar}%")
                    ->orWhere('c.appregistro', 'like', "%{$buscar}%");
            });
        }

        $filtrados = $buscar !== '' ? (clone $consulta)->count() : $total;

        // columnas en el orden de la tabla
        $columnas = ['c.usuario', 'c.fecha', 'c.appregistro', 'c.activo'];
        $orden = $request->input('order.0', []);
        $columna = $columnas[(int) ($orden['column'] ?? 1)] ?? 'c.fecha';
        $direccion = ($orden['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $inicio = max(0, (int) $request->get('start', 0));
        $largo = (int) $request->get('length', 25);
        if ($largo < 1 || $largo > 500) { $largo = 500; }

        $filas = $consulta
            ->select('c.id', 'c.usuario', 'c.correo', 'c.fecha', 'c.activo', 'c.appregistro')
            ->orderBy($columna, $direccion)
            ->orderBy('c.id', 'desc')
            ->skip($inicio)->take($largo)
            ->get()
            ->map(function ($c) use ($hace30) {
                [$sistema, $version] = self::app($c->appregistro);
                return [
                    'id' => $c->id,
                    'usuario' => $c->usuario,
                    'correo' => $c->correo,
                    'fecha' => $c->fecha ? date('d/m/Y h:i A', strtotime($c->fecha)) : '',
                    'nuevo' => $c->fecha && $c->fecha >= $hace30,
                    'sistema' => $sistema,
                    'version' => $version,
                    'activo' => (int) $c->activo === 1,
                ];
            });

        return response()->json([
            'draw' => (int) $request->get('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtrados,
            'data' => $filas,
        ]);
    }

    // "android 1.0.6" -> ['android', '1.0.6']; las versiones 2.x en adelante no guardan el sistema
    public static function app(?string $texto): array
    {
        $texto = trim((string) $texto);
        if(preg_match('/^(android|ios)\s+(.+)$/i', $texto, $m)){ return [strtolower($m[1]), $m[2]]; }
        return [null, $texto];
    }

    // activar / desactivar
    public function editarCliente(Request $request){

        $validator = Validator::make($request->all(), ['id' => 'required|integer', 'activo' => 'required|in:0,1']);
        if ($validator->fails()){ return ['success' => 0]; }

        $cliente = Clientes::where('id', $request->id)->whereNotIn('id', self::RESERVADOS)->first();
        if(!$cliente){ return ['success' => 99]; }

        $cliente->activo = (int) $request->activo;
        $cliente->save();

        // cliente desactivado: se cierran sus sesiones en la app
        if ($cliente->activo === 0) { $cliente->tokens()->delete(); }

        return ['success' => 1];
    }

    public function indexClientePrimeraUbicacion(){


        // obtener lista de restaurantes
        $listaRestaurantes = Servicios::orderBy('nombre')->get();


        foreach ($listaRestaurantes as $info){

            $pilaId = array();

            $infoZonaServicios = ZonasServicio::where('id_servicios', $info->id)->get();

            foreach ($infoZonaServicios as $datoZona){
                array_push($pilaId, $datoZona->id_zonas);
            }

            // listado de cliente direccion que pertenecen a estas zonas
            $conteo = DireccionCliente::whereIn('id_zonas', $pilaId)
                ->where('seleccionado', 1)
                ->count();

            $info->conteo = $conteo;
        }



        return view('backend.admin.reportes.clientes.vistaclientesrestaurantes', compact('listaRestaurantes'));
    }



}
