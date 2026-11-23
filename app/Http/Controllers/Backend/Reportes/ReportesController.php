<?php

namespace App\Http\Controllers\Backend\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Servicios;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Reportes del panel: ventas, órdenes, cancelaciones, restaurantes, productos y clientes.
 *
 * Venta = orden INICIADA por el restaurante y no cancelada. Las pendientes (el restaurante aún no
 * la acepta) se cuentan aparte y no suman a ventas. Las fechas se toman tal cual se guardan
 * (hora de El Salvador). Un mismo filtro (rango + restaurante) alimenta todos los bloques.
 */
class ReportesController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function index(){
        $restaurantes = Servicios::orderBy('nombre')->get(['id', 'nombre']);
        return view('backend.admin.reportes.vistareportes', compact('restaurantes'));
    }

    public function datos(Request $request){

        $zona = 'America/El_Salvador';
        try {
            $desde = Carbon::parse($request->input('desde', Carbon::now($zona)->subDays(29)->toDateString()), $zona)->startOfDay();
            $hasta = Carbon::parse($request->input('hasta', Carbon::now($zona)->toDateString()), $zona)->endOfDay();
        } catch (\Throwable $e) {
            return ['success' => 0, 'mensaje' => 'Fechas no válidas'];
        }
        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }
        if ($desde->diffInDays($hasta) > 1100) {
            return ['success' => 0, 'mensaje' => 'El rango máximo es de 3 años'];
        }

        $idServicio = $request->filled('servicio') ? (int) $request->servicio : null;

        // órdenes del rango (y del restaurante si se filtra)
        $ordenes = fn (Carbon $d, Carbon $h) => DB::table('ordenes AS o')
            ->whereBetween('o.fecha_orden', [$d->format('Y-m-d H:i:s'), $h->format('Y-m-d H:i:s')])
            ->when($idServicio, fn ($q) => $q->where('o.id_servicio', $idServicio));

        // ---------- tarjetas ----------
        $kpi = function (Carbon $d, Carbon $h) use ($ordenes) {
            $r = $ordenes($d, $h)->selectRaw('
                    SUM(CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 1 THEN 1 ELSE 0 END) AS ordenes,
                    SUM(CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 1 THEN o.total_orden ELSE 0 END) AS ventas,
                    SUM(CASE WHEN o.estado_cancelada = 1 THEN 1 ELSE 0 END) AS canceladas,
                    SUM(CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 0 THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN o.estado_cancelada = 1 AND o.cancelado_por = 1 THEN 1 ELSE 0 END) AS cancel_cliente,
                    SUM(CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 1 AND o.id_cupones_copia IS NOT NULL THEN 1 ELSE 0 END) AS con_cupon,
                    COUNT(DISTINCT CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 1 THEN o.id_cliente END) AS clientes')
                ->first();
            $ordenesN = (int) $r->ordenes;
            // % de cancelacion sobre todas las ordenes recibidas (iniciadas + pendientes + canceladas)
            $total = $ordenesN + (int) $r->canceladas + (int) $r->pendientes;
            return [
                'ventas' => round((float) $r->ventas, 2),
                'ordenes' => $ordenesN,
                'ticket' => $ordenesN ? round((float) $r->ventas / $ordenesN, 2) : 0,
                'pendientes' => (int) $r->pendientes,
                'canceladas' => (int) $r->canceladas,
                'cancel_cliente' => (int) $r->cancel_cliente,
                'cancel_restaurante' => (int) $r->canceladas - (int) $r->cancel_cliente,
                'pct_canceladas' => $total ? round($r->canceladas * 100 / $total, 1) : 0,
                'con_cupon' => (int) $r->con_cupon,
                'clientes' => (int) $r->clientes,
            ];
        };

        $actual = $kpi($desde, $hasta);

        // mismo largo de periodo, justo antes, para comparar
        $dias = $desde->copy()->startOfDay()->diffInDays($hasta->copy()->startOfDay()) + 1;
        $antDesde = $desde->copy()->subDays($dias);
        $antHasta = $desde->copy()->subSecond();
        $anterior = $kpi($antDesde, $antHasta);

        $variacion = fn ($a, $b) => $b > 0 ? round(($a - $b) * 100 / $b, 1) : null;

        // clientes nuevos (registro de la cuenta; no depende del restaurante)
        $clientesNuevos = DB::table('clientes')->whereBetween('fecha', [$desde->format('Y-m-d H:i:s'), $hasta->format('Y-m-d H:i:s')])->count();

        // ---------- serie en el tiempo (por día, o por mes si el rango es largo) ----------
        $porMes = $dias > 92;
        $formatoSql = $porMes ? '%Y-%m' : '%Y-%m-%d';
        $filas = $ordenes($desde, $hasta)
            ->where('o.estado_cancelada', 0)->where('o.estado_iniciada', 1)
            ->selectRaw("DATE_FORMAT(o.fecha_orden, '{$formatoSql}') AS periodo, COUNT(*) AS ordenes, SUM(o.total_orden) AS ventas")
            ->groupBy('periodo')->get()->keyBy('periodo');

        $serie = [];
        $cursor = $porMes ? $desde->copy()->startOfMonth() : $desde->copy()->startOfDay();
        while ($cursor->lte($hasta)) {
            $clave = $cursor->format($porMes ? 'Y-m' : 'Y-m-d');
            $f = $filas->get($clave);
            $serie[] = [
                'periodo' => $clave,
                'etiqueta' => $porMes ? $cursor->locale('es')->translatedFormat('M Y') : $cursor->locale('es')->translatedFormat('D d M'),
                'ventas' => $f ? round((float) $f->ventas, 2) : 0,
                'ordenes' => $f ? (int) $f->ordenes : 0,
            ];
            $porMes ? $cursor->addMonth() : $cursor->addDay();
        }

        // ---------- órdenes por hora del día ----------
        $horas = $ordenes($desde, $hasta)->where('o.estado_cancelada', 0)->where('o.estado_iniciada', 1)
            ->selectRaw('HOUR(o.fecha_orden) AS hora, COUNT(*) AS ordenes')
            ->groupBy('hora')->pluck('ordenes', 'hora');
        $porHora = [];
        for ($h = 0; $h < 24; $h++) {
            $porHora[] = ['hora' => $h, 'ordenes' => (int) ($horas[$h] ?? 0)];
        }

        // ---------- por restaurante ----------
        $porServicio = $ordenes($desde, $hasta)
            ->selectRaw('o.id_servicio,
                SUM(CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 1 THEN 1 ELSE 0 END) AS ordenes,
                SUM(CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 1 THEN o.total_orden ELSE 0 END) AS ventas,
                SUM(CASE WHEN o.estado_cancelada = 1 THEN 1 ELSE 0 END) AS canceladas,
                SUM(CASE WHEN o.estado_cancelada = 0 AND o.estado_iniciada = 0 THEN 1 ELSE 0 END) AS pendientes')
            ->groupBy('o.id_servicio')->get()->keyBy('id_servicio');

        // clientes cuya dirección seleccionada está en una zona del restaurante (el reporte anterior)
        $clientesAsociados = DB::table('direccion_cliente AS d')
            ->join('zonas_servicio AS zs', 'zs.id_zonas', '=', 'd.id_zonas')
            ->where('d.seleccionado', 1)
            ->selectRaw('zs.id_servicios, COUNT(DISTINCT d.id_cliente) AS total')
            ->groupBy('zs.id_servicios')->pluck('total', 'id_servicios');

        $restaurantes = Servicios::orderBy('nombre')
            ->when($idServicio, fn ($q) => $q->where('id', $idServicio))
            ->get(['id', 'nombre'])
            ->map(function ($s) use ($porServicio, $clientesAsociados) {
                $f = $porServicio->get($s->id);
                $ord = $f ? (int) $f->ordenes : 0;
                $can = $f ? (int) $f->canceladas : 0;
                $pen = $f ? (int) $f->pendientes : 0;
                $ven = $f ? round((float) $f->ventas, 2) : 0;
                return [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                    'ordenes' => $ord,
                    'ventas' => $ven,
                    'ticket' => $ord ? round($ven / $ord, 2) : 0,
                    'pendientes' => $pen,
                    'canceladas' => $can,
                    'pct_canceladas' => ($ord + $can + $pen) ? round($can * 100 / ($ord + $can + $pen), 1) : 0,
                    'clientes' => (int) ($clientesAsociados[$s->id] ?? 0),
                ];
            })
            ->sortByDesc('ventas')->values();

        // ---------- productos más vendidos ----------
        $productos = DB::table('ordenes_descripcion AS od')
            ->join('ordenes AS o', 'o.id', '=', 'od.id_ordenes')
            ->leftJoin('productos AS p', 'p.id', '=', 'od.id_producto')
            ->leftJoin('servicios AS sv', 'sv.id', '=', 'o.id_servicio')
            ->whereBetween('o.fecha_orden', [$desde->format('Y-m-d H:i:s'), $hasta->format('Y-m-d H:i:s')])
            ->where('o.estado_cancelada', 0)->where('o.estado_iniciada', 1)
            ->when($idServicio, fn ($q) => $q->where('o.id_servicio', $idServicio))
            ->selectRaw("od.id_producto, COALESCE(p.nombre, 'Producto borrado') AS nombre, p.imagen, sv.nombre AS restaurante,
                SUM(od.cantidad) AS cantidad, SUM(od.cantidad * od.precio) AS monto")
            ->groupBy('od.id_producto', 'p.nombre', 'p.imagen', 'sv.nombre')
            ->orderByDesc('cantidad')->limit(10)->get()
            ->map(fn ($p) => [
                'nombre' => $p->nombre,
                'restaurante' => $p->restaurante,
                'imagen' => $p->imagen ? url('storage/imagenes/' . $p->imagen) : null,
                'cantidad' => (int) $p->cantidad,
                'monto' => round((float) $p->monto, 2),
            ]);

        return [
            'success' => 1,
            'rango' => [
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
                'dias' => $dias,
                'anterior' => $antDesde->toDateString() . ' a ' . $antHasta->toDateString(),
                'agrupado' => $porMes ? 'mes' : 'dia',
            ],
            'kpi' => $actual + [
                'clientes_nuevos' => $clientesNuevos,
                'var_ventas' => $variacion($actual['ventas'], $anterior['ventas']),
                'var_ordenes' => $variacion($actual['ordenes'], $anterior['ordenes']),
                'var_ticket' => $variacion($actual['ticket'], $anterior['ticket']),
            ],
            'serie' => $serie,
            'horas' => $porHora,
            'restaurantes' => $restaurantes,
            'productos' => $productos,
        ];
    }
}
