<?php

namespace App\Http\Controllers\Backend\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Cupones;
use App\Models\CuponProductoGratis;
use App\Models\Servicios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Cupones de producto gratis (el unico tipo que existe, id_tipo_cupon = 1).
// Un cupon es un codigo con un limite de usos compartido; vale en los restaurantes
// donde esta asignado y cada restaurante define que regala (c_producto_gratis.nombre).
class CuponesController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }

    public function index(){
        $servicios = Servicios::orderBy('nombre')->get(['id', 'nombre', 'utiliza_cupon']);
        return view('backend.admin.configuracion.cupones.vistacupones', compact('servicios'));
    }

    public function cuponesTabla(){

        $cupones = Cupones::where('id_tipo_cupon', 1)->orderBy('texto_cupon')->get();
        $nombres = Servicios::pluck('nombre', 'id');
        $aceptan = Servicios::pluck('utiliza_cupon', 'id');
        $premios = CuponProductoGratis::orderBy('id')->get()->groupBy('id_cupones');

        foreach ($cupones as $info){
            $info->agotado = $info->contador >= $info->uso_limite;
            $info->porcentaje = $info->uso_limite > 0 ? min(100, round($info->contador * 100 / $info->uso_limite)) : 100;
            $info->premios = ($premios[$info->id] ?? collect())->map(fn($p) => (object) [
                'restaurante' => $nombres[$p->id_servicios] ?? '',
                'premio' => $p->nombre,
                'acepta' => (int) ($aceptan[$p->id_servicios] ?? 0) === 1,
            ])->sortBy('restaurante')->values();
        }

        return view('backend.admin.configuracion.cupones.tablacupones', compact('cupones'));
    }

    public function informacionCupon(Request $request){

        $cupon = Cupones::where('id', $request->id)->where('id_tipo_cupon', 1)->first();
        if(!$cupon){ return ['success' => 2]; }

        $premios = CuponProductoGratis::where('id_cupones', $cupon->id)->get(['id_servicios', 'nombre']);

        return ['success' => 1, 'cupon' => $cupon, 'premios' => $premios];
    }

    // crear o editar (con id) el cupon y en que restaurantes vale
    public function guardarCupon(Request $request){

        $validar = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'nombre' => 'required|string|max:50',
            'limite' => 'required|integer|min:1|max:1000000',
            'activo' => 'required|in:0,1',
            'premios' => 'present|array',
            'premios.*.servicio' => 'required|integer|distinct|exists:servicios,id',
            'premios.*.premio' => 'required|string|max:100',
        ]);
        if($validar->fails()){ return ['success' => 0, 'mensaje' => 'Revisa los datos del cupón']; }

        $nombre = trim($request->nombre);
        $id = $request->id ? (int) $request->id : null;

        if($id && !Cupones::where('id', $id)->where('id_tipo_cupon', 1)->exists()){ return ['success' => 99]; }

        // no cupones repetidos
        if(Cupones::where('texto_cupon', $nombre)->when($id, fn($q) => $q->where('id', '!=', $id))->exists()){
            return ['success' => 0, 'mensaje' => 'Ya existe un cupón con ese código'];
        }

        DB::beginTransaction();

        try {
            $cupon = $id ? Cupones::find($id) : new Cupones();
            if(!$id){
                $cupon->id_tipo_cupon = 1;
                $cupon->contador = 0;
            }
            $cupon->texto_cupon = $nombre;
            $cupon->uso_limite = (int) $request->limite;
            $cupon->activo = (int) $request->activo;
            $cupon->save();

            // premios por restaurante: se actualizan, se agregan y se quitan los que ya no estan
            $servicios = [];
            foreach ($request->premios as $p){
                $servicios[] = (int) $p['servicio'];
                CuponProductoGratis::updateOrInsert(
                    ['id_cupones' => $cupon->id, 'id_servicios' => (int) $p['servicio']],
                    ['nombre' => trim($p['premio'])]
                );
            }
            CuponProductoGratis::where('id_cupones', $cupon->id)->whereNotIn('id_servicios', $servicios)->delete();

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::info('error ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }

    // interruptor rapido desde la tabla
    public function cambiarActivo(Request $request){

        $validar = Validator::make($request->all(), ['id' => 'required|integer', 'activo' => 'required|in:0,1']);
        if($validar->fails()){ return ['success' => 0]; }

        $total = Cupones::where('id', $request->id)->where('id_tipo_cupon', 1)->update(['activo' => (int) $request->activo]);

        return ['success' => $total ? 1 : 99];
    }
}
