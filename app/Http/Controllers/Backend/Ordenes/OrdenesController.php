<?php

namespace App\Http\Controllers\Backend\Ordenes;

use App\Http\Controllers\Controller;
use App\Models\Cupones;
use App\Models\Ordenes;
use App\Models\OrdenesDescripcion;
use App\Models\OrdenesDirecciones;
use App\Models\Servicios;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// Una sola pagina de ordenes con pestañas: pendientes, iniciadas, canceladas y todas
class OrdenesController extends Controller
{
    const ESTADOS = ['pendientes', 'iniciadas', 'canceladas', 'todas'];

    public function __construct(){
        $this->middleware('auth');
    }

    private static function hoy(): string
    {
        return Carbon::now('America/El_Salvador')->toDateString();
    }

    public function index(Request $request){

        $hoy = self::hoy();
        $resumen = [
            'pendientes' => Ordenes::where('estado_iniciada', 0)->where('estado_cancelada', 0)->count(),
            'iniciadas' => Ordenes::whereDate('fecha_orden', $hoy)->where('estado_iniciada', 1)->where('estado_cancelada', 0)->count(),
            'canceladas' => Ordenes::whereDate('fecha_orden', $hoy)->where('estado_cancelada', 1)->count(),
            'todas' => Ordenes::count(),
        ];

        $pestana = in_array($request->get('ver'), self::ESTADOS) ? $request->get('ver') : 'pendientes';
        $servicios = Servicios::orderBy('nombre')->get(['id', 'nombre']);

        return view('backend.admin.ordenes.vistaordenes', compact('resumen', 'pestana', 'servicios', 'hoy'));
    }

    // datos para DataTables en modo servidor
    public function tabla(Request $request){

        $estado = in_array($request->get('estado'), self::ESTADOS) ? $request->get('estado') : 'pendientes';

        $consulta = DB::table('ordenes AS o')
            ->leftJoin('servicios AS s', 's.id', '=', 'o.id_servicio')
            ->leftJoin('ordenes_direcciones AS d', 'd.id_ordenes', '=', 'o.id');

        switch ($estado) {
            case 'pendientes':
                $consulta->where('o.estado_iniciada', 0)->where('o.estado_cancelada', 0);
                break;
            case 'iniciadas':
                $consulta->where('o.estado_iniciada', 1)->where('o.estado_cancelada', 0);
                break;
            case 'canceladas':
                $consulta->where('o.estado_cancelada', 1);
                break;
        }

        // iniciadas y canceladas se ven por dia (por defecto hoy)
        if (in_array($estado, ['iniciadas', 'canceladas'])) {
            $fecha = $request->get('fecha');
            $fecha = $fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : self::hoy();
            $consulta->whereDate('o.fecha_orden', $fecha);
        }

        if ($request->filled('servicio')) {
            $consulta->where('o.id_servicio', (int) $request->get('servicio'));
        }

        $total = (clone $consulta)->count();

        $buscar = trim((string) data_get($request->get('search'), 'value', ''));
        if ($buscar !== '') {
            $consulta->where(function ($q) use ($buscar) {
                $q->where('d.nombre', 'like', "%{$buscar}%")
                    ->orWhere('d.direccion', 'like', "%{$buscar}%")
                    ->orWhere('d.telefono', 'like', "%{$buscar}%")
                    ->orWhere('s.nombre', 'like', "%{$buscar}%");
                if (ctype_digit($buscar)) { $q->orWhere('o.id', (int) $buscar); }
            });
        }
        $filtrados = $buscar !== '' ? (clone $consulta)->count() : $total;

        $columnas = ['o.id', 'o.fecha_orden', 's.nombre', 'd.nombre', 'd.direccion', 'o.total_orden', 'o.estado_cancelada'];
        $orden = $request->input('order.0', []);
        $columna = $columnas[(int) ($orden['column'] ?? 0)] ?? 'o.id';
        $direccion = ($orden['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $inicio = max(0, (int) $request->get('start', 0));
        $largo = (int) $request->get('length', 25);
        if ($largo < 1 || $largo > 500) { $largo = 500; }

        $ahora = Carbon::now('America/El_Salvador');

        $filas = $consulta
            ->select('o.id', 'o.fecha_orden', 'o.total_orden', 'o.estado_iniciada', 'o.estado_cancelada', 'o.cancelado_por',
                'o.fecha_iniciada', 'o.fecha_cancelada', 'o.fecha_estimada', 'o.id_cupones',
                's.nombre AS restaurante', 'd.nombre AS cliente', 'd.telefono', 'd.direccion')
            ->orderBy($columna, $direccion)
            ->orderBy('o.id', 'desc')
            ->skip($inicio)->take($largo)
            ->get()
            ->map(function ($o) use ($ahora) {
                $fecha = Carbon::parse($o->fecha_orden, 'America/El_Salvador');
                return [
                    'id' => $o->id,
                    'fecha' => $fecha->format('d/m/Y h:i A'),
                    'hace' => $fecha->locale('es')->diffForHumans($ahora, \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW),
                    'restaurante' => $o->restaurante ?? 'Restaurante borrado',
                    'cliente' => $o->cliente,
                    'telefono' => $o->telefono,
                    'direccion' => $o->direccion,
                    'total' => '$' . number_format((float) $o->total_orden, 2),
                    'cupon' => $o->id_cupones !== null,
                    'estado' => $o->estado_cancelada ? 'cancelada' : ($o->estado_iniciada ? 'iniciada' : 'pendiente'),
                    'cancelado_por' => $o->estado_cancelada ? ((int) $o->cancelado_por === 1 ? 'Cliente' : 'Restaurante') : null,
                ];
            });

        return response()->json([
            'draw' => (int) $request->get('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtrados,
            'data' => $filas,
        ]);
    }

    // todo lo de una orden: cliente, productos y proceso
    public function detalle(Request $request){

        $orden = Ordenes::where('id', (int) $request->id)->first();
        if (!$orden) { return ['success' => 99]; }

        $direccion = OrdenesDirecciones::where('id_ordenes', $orden->id)->first();

        $productos = DB::table('ordenes_descripcion AS od')
            ->leftJoin('productos AS p', 'p.id', '=', 'od.id_producto')
            ->where('od.id_ordenes', $orden->id)
            ->orderBy('od.id')
            ->get(['od.id', 'p.nombre', 'p.imagen', 'p.utiliza_imagen', 'od.cantidad', 'od.nota', 'od.precio']);

        // opciones elegidas (copiadas al ordenar); el precio guardado ya las incluye
        $opcionesOrden = \App\Services\Modificadores::deOrden($productos->pluck('id')->all());

        $productos = $productos->map(fn ($p) => [
                'nombre' => $p->nombre ?? 'Producto borrado',
                'imagen' => $p->utiliza_imagen == 1 && $p->imagen ? url('storage/imagenes/' . $p->imagen) : null,
                'cantidad' => (int) $p->cantidad,
                'nota' => $p->nota,
                'opciones' => $opcionesOrden[$p->id]['texto'] ?? [],
                'precio' => '$' . number_format((float) $p->precio, 2),
                'subtotal' => '$' . number_format((float) $p->precio * $p->cantidad, 2),
            ]);

        $formato = fn ($f) => $f ? date('d/m/Y h:i A', strtotime($f)) : null;

        $zona = DB::table('zonas')->where('id', $orden->id_zona)->first(['nombre', 'descripcion', 'tiempo_extra']);

        // minutos que realmente se sumaron al iniciar (la zona pudo cambiar despues)
        $minutosUsados = $orden->estado_iniciada && $orden->fecha_iniciada && $orden->fecha_estimada
            ? (int) round((strtotime($orden->fecha_estimada) - strtotime($orden->fecha_iniciada)) / 60)
            : null;

        return [
            'success' => 1,
            'orden' => [
                'id' => $orden->id,
                'restaurante' => Servicios::where('id', $orden->id_servicio)->value('nombre') ?? 'Restaurante borrado',
                'fecha' => $formato($orden->fecha_orden),
                'zona' => $zona ? trim($zona->nombre . ($zona->descripcion ? ' · ' . $zona->descripcion : '')) : 'Zona borrada',
                'zona_minutos' => $zona ? (int) $zona->tiempo_extra : null,
                'minutos_usados' => $minutosUsados,
                'nota' => $orden->nota_orden,
                'total' => '$' . number_format((float) $orden->total_orden, 2),
                'cupon' => $orden->id_cupones ? (Cupones::where('id', $orden->id_cupones)->value('texto_cupon') ?? 'Sí') : null,
                'mensaje_cupon' => $orden->mensaje_cupon,
                'estado' => $orden->estado_cancelada ? 'cancelada' : ($orden->estado_iniciada ? 'iniciada' : 'pendiente'),
                'fecha_iniciada' => $formato($orden->estado_iniciada ? $orden->fecha_iniciada : null),
                'fecha_estimada' => $formato($orden->estado_iniciada ? $orden->fecha_estimada : null),
                'fecha_cancelada' => $formato($orden->estado_cancelada ? $orden->fecha_cancelada : null),
                'cancelado_por' => $orden->estado_cancelada ? ((int) $orden->cancelado_por === 1 ? 'Cliente' : 'Restaurante') : null,
                'nota_cancelada' => $orden->nota_cancelada,
            ],
            'cliente' => $direccion ? [
                'nombre' => $direccion->nombre,
                'telefono' => $direccion->telefono,
                'direccion' => $direccion->direccion,
                'referencia' => $direccion->referencia,
                'app' => \App\Http\Controllers\Backend\Clientes\ClientesController::app($direccion->appversion)[1],
                'sistema' => \App\Http\Controllers\Backend\Clientes\ClientesController::app($direccion->appversion)[0],
                'lat' => $direccion->latitud,
                'lng' => $direccion->longitud,
            ] : null,
            'productos' => $productos,
        ];
    }

    // borra por completo una orden que el restaurante aun no ha iniciado
    public function borrar(Request $request){

        $validar = Validator::make($request->all(), ['id' => 'required|integer']);
        if ($validar->fails()) { return ['success' => 0]; }

        $orden = Ordenes::where('id', $request->id)->first();
        if (!$orden) { return ['success' => 99]; }

        if ($orden->estado_iniciada == 1 || $orden->estado_cancelada == 1) {
            return ['success' => 2, 'mensaje' => 'Esta orden ya no está pendiente: el restaurante la inició o fue cancelada.'];
        }

        DB::transaction(function () use ($orden) {
            // devolver el uso del cupon, igual que cuando se cancela
            if ($orden->id_cupones_copia) {
                Cupones::where('id', $orden->id_cupones_copia)->where('contador', '>', 0)->decrement('contador');
            }
            OrdenesDescripcion::where('id_ordenes', $orden->id)->delete();
            OrdenesDirecciones::where('id_ordenes', $orden->id)->delete();
            $orden->delete();
        });

        return ['success' => 1];
    }
}
