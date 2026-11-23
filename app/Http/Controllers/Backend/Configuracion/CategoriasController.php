<?php

namespace App\Http\Controllers\Backend\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Categorias;
use App\Models\Populares;
use App\Models\Productos;
use App\Models\SubCategorias;
use App\Services\Imagenes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoriasController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }


    public function index($id){
        $restaurante = \App\Models\Servicios::where('id', $id)->value('nombre');
        return view('backend.admin.configuracion.categorias.vistacategorias', compact('id', 'restaurante'));
    }

    // tabla lista categorias
    public function categoriasTabla($id){

        $categorias = Categorias::where('id_servicios', $id)->orderBy('posicion')->get();

        foreach ($categorias as $info){

            $info->hora_abre = date("h:i A", strtotime($info->hora_abre));
            $info->hora_cierra = date("h:i A", strtotime($info->hora_cierra));
        }

        return view('backend.admin.configuracion.categorias.tablacategorias', compact('categorias'));
    }

    public function nuevaCategorias(Request $request){

        $regla = array(
            'nombre' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        // imagen: JPG/PNG real, peso maximo, y se guarda reducida
        if($error = Imagenes::validar($request, true)){
            return ['success' => 3, 'mensaje' => $error];
        }

        if($info = Categorias::where('id_servicios', $request->id)->orderBy('posicion', 'DESC')->first()){
            $suma = $info->posicion + 1;
        }else{
            $suma = 1;
        }

        $nombreFoto = Imagenes::guardar($request->file('imagen'), Imagenes::LADO_CATEGORIA);
        if(!$nombreFoto){
            return ['success' => 3, 'mensaje' => Imagenes::mensajeError()];
        }
        $upload = true;

        if($upload){
            $ca = new Categorias();
            $ca->id_servicios = $request->id;
            $ca->nombre = $request->nombre;
            $ca->activo = 0;
            $ca->posicion = $suma;
            $ca->usa_horario = $request->toggle;
            $ca->hora_abre = $request->horaabre;
            $ca->hora_cierra = $request->horacierra;
            $ca->imagen = $nombreFoto;

            if($ca->save()){
                return ['success' => 1];
            }else{
                return ['success' => 2];
            }
        }else{
            return ['success' => 2];
        }

    }

    public function informacionCategorias(Request $request){

        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        if($categoria = Categorias::where('id', $request->id)->first()){

            return ['success' => 1, 'categoria' => $categoria];
        }else{
            return ['success' => 2];
        }
    }

    public function editarCategorias(Request $request){

        $rules = array(
            'id' => 'required',
            'nombre' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if($infoCategoria = Categorias::where('id', $request->id)->first()){

            if($error = Imagenes::validar($request)){
                return ['success' => 3, 'mensaje' => $error];
            }

            if($request->hasFile('imagen')){

                $nombreFoto = Imagenes::guardar($request->file('imagen'), Imagenes::LADO_CATEGORIA);
                if(!$nombreFoto){
                    return ['success' => 3, 'mensaje' => Imagenes::mensajeError()];
                }
                $upload = true;

                if($upload){
                    $imagenOld = $infoCategoria->imagen;

                    Categorias::where('id', $request->id)->update([
                        'nombre' => $request->nombre,
                        'activo' => $request->cbactivo,
                        'usa_horario' => $request->cbhorario,
                        'hora_abre' => $request->horaabre,
                        'hora_cierra' => $request->horacierra,
                        'imagen' => $nombreFoto
                    ]);

                    if(Storage::disk('imagenes')->exists($imagenOld)){
                        Storage::disk('imagenes')->delete($imagenOld);
                    }

                    return ['success' => 1];

                }else{
                    return ['success' => 2];
                }
            }else{


                Categorias::where('id', $request->id)->update([
                    'nombre' => $request->nombre,
                    'activo' => $request->cbactivo,
                    'usa_horario' => $request->cbhorario,
                    'hora_abre' => $request->horaabre,
                    'hora_cierra' => $request->horacierra
                ]);


                return ['success' => 1];
            }







        }else{
            return ['success' => 99];
        }
    }


    public function ordenarCategorias(Request $request){

        $tasks = Categorias::all();

        foreach ($tasks as $task) {
            $id = $task->id;

            foreach ($request->order as $order) {
                if ($order['id'] == $id) {
                    $task->update(['posicion' => $order['posicion']]);
                }
            }
        }
        return ['success' => 1];
    }









    //***************** SUB CATEGORIAS  ************************


    public function indexSubCategorias($id){
        // viene id categoria

        $infoCategoria = Categorias::where('id', $id)->first();
        $nomcategoria = $infoCategoria->nombre;
        $idservicio = $infoCategoria->id_servicios;
        $restaurante = \App\Models\Servicios::where('id', $idservicio)->value('nombre');

        return view('backend.admin.configuracion.categorias.subcategorias.vistasubcategorias', compact('id', 'nomcategoria', 'idservicio', 'restaurante'));
    }

    // tabla lista categorias
    public function subCategoriasTabla($id){

        $lista = SubCategorias::where('id_categorias', $id)->orderBy('posicion')->get();

        // conteo de productos por sub categoria (total y activos)
        $conteos = Productos::whereIn('id_subcategorias', $lista->pluck('id'))
            ->select('id_subcategorias',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos'))
            ->groupBy('id_subcategorias')
            ->get()
            ->keyBy('id_subcategorias');

        foreach ($lista as $info){
            $info->total_productos = (int) optional($conteos->get($info->id))->total;
            $info->productos_activos = (int) optional($conteos->get($info->id))->activos;
        }

        return view('backend.admin.configuracion.categorias.subcategorias.tablasubcategorias', compact('lista'));
    }

    public function nuevaSubCategorias(Request $request){

        $regla = array(
            'nombre' => 'required',
            'id' => 'required' // id de categoria
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        if($info = SubCategorias::where('id_categorias', $request->id)->orderBy('posicion', 'DESC')->first()){
            $suma = $info->posicion + 1;
        }else{
            $suma = 1;
        }

        $subcategoria = new SubCategorias();
        $subcategoria->id_categorias = $request->id;
        $subcategoria->nombre = $request->nombre;
        $subcategoria->activo = 0;
        $subcategoria->posicion = $suma;

        if($subcategoria->save()){
            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }

    public function informacionSubCategorias(Request $request){

        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        if($subCategoria = SubCategorias::where('id', $request->id)->first()){

            return ['success' => 1, 'subcategoria' => $subCategoria];
        }else{
            return ['success' => 2];
        }
    }

    public function editarSubCategorias(Request $request){

        $rules = array(
            'id' => 'required',
            'nombre' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if(SubCategorias::where('id', $request->id)->first()){

            $verificar = true;

            // verificar que haya productos activos para activar esta categoria
            if($request->cbactivo == 1){
                if(Productos::where('id_subcategorias', $request->id)
                    ->where('activo', 1)
                    ->first()){
                    // encontro
                    $verificar = false;
                }

                // NO HAY PRODUCTOS EN SUB CATEGORIAS, NO SE PUEDE ACTIVAR
                if($verificar){
                    return ['success' => 1];
                }
            }


            SubCategorias::where('id', $request->id)->update([
                'nombre' => $request->nombre,
                'activo' => $request->cbactivo
            ]);

            return ['success' => 2];
        }else{
            return ['success' => 99];
        }
    }


    public function ordenarSubCategorias(Request $request){

        $tasks = SubCategorias::all();

        foreach ($tasks as $task) {
            $id = $task->id;

            foreach ($request->order as $order) {
                if ($order['id'] == $id) {
                    $task->update(['posicion' => $order['posicion']]);
                }
            }
        }
        return ['success' => 1];
    }




    // ******************** PRODUCTOS PRINCIPALES DEL SERVICIO /////


    public function indexServiciosProductosPrincipales($id){

        // productos del restaurante agrupados por "Categoría › Sub categoría" para el selector
        $grupoProductos = DB::table('productos AS p')
            ->join('sub_categorias AS s', 's.id', '=', 'p.id_subcategorias')
            ->join('categorias AS c', 'c.id', '=', 's.id_categorias')
            ->where('c.id_servicios', $id)
            ->orderBy('c.posicion')->orderBy('s.posicion')->orderBy('p.posicion')
            ->select('p.id', 'p.nombre', 'p.precio', 'p.activo', 'c.nombre AS categoria', 's.nombre AS subcategoria')
            ->get()
            ->groupBy(fn ($p) => $p->categoria . ' › ' . $p->subcategoria);

        $restaurante = \App\Models\Servicios::where('id', $id)->value('nombre');

        return view('backend.admin.configuracion.servicios.productosprincipales.vistaproductosprincipales',
            compact('id', 'grupoProductos', 'restaurante'));
    }

    public function tablaServiciosProductosPrincipales($id){

        $hora = \Carbon\Carbon::now('America/El_Salvador')->format('H:i:s');

        // cada popular con su producto, sub categoria y categoria (leftJoin: el producto pudo borrarse)
        $listado = DB::table('populares AS pop')
            ->leftJoin('productos AS p', 'p.id', '=', 'pop.id_productos')
            ->leftJoin('sub_categorias AS s', 's.id', '=', 'p.id_subcategorias')
            ->leftJoin('categorias AS c', 'c.id', '=', 's.id_categorias')
            ->where('pop.id_servicios', $id)
            ->orderBy('pop.posicion')
            ->select('pop.id', 'pop.id_productos', 'p.nombre', 'p.precio', 'p.imagen', 'p.activo AS producto_activo',
                's.nombre AS subcategoria', 's.activo AS sub_activo',
                'c.nombre AS categoria', 'c.activo AS cat_activo', 'c.usa_horario', 'c.hora_abre', 'c.hora_cierra')
            ->get();

        foreach ($listado as $info){
            $info->precio = '$' . number_format((float) $info->precio, 2, '.', ',');

            // por que no se ve en la app (mismas reglas que la API del menu); null = se ve
            if ($info->nombre === null) {
                $info->motivo = 'El producto fue borrado';
            } elseif ($info->producto_activo == 0) {
                $info->motivo = 'El producto está oculto';
            } elseif ($info->sub_activo == 0) {
                $info->motivo = 'La sub categoría «' . $info->subcategoria . '» está oculta';
            } elseif ($info->cat_activo == 0) {
                $info->motivo = 'La categoría «' . $info->categoria . '» está oculta';
            } elseif ($info->usa_horario == 1 && ($hora < $info->hora_abre || $hora > $info->hora_cierra)) {
                $info->motivo = 'Fuera del horario de «' . $info->categoria . '» ('
                    . date('h:i A', strtotime($info->hora_abre)) . ' – ' . date('h:i A', strtotime($info->hora_cierra)) . ')';
            } else {
                $info->motivo = null;
            }
        }

        return view('backend.admin.configuracion.servicios.productosprincipales.tablaproductosprincipales', compact('listado'));
    }


    public function nuevoProductosPrincipales(Request $request){

        $regla = array(
            'idservicio' => 'required',
            'idproducto' => 'required'
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        // YA ESTA REGISTRADO EL PRODUCTO
        if(Populares::where('id_servicios', $request->idservicio)
            ->where('id_productos', $request->idproducto)
            ->first()){
            return ['success' => 1];
        }

        if($info = Populares::where('id_servicios', $request->idservicio)
            ->orderBy('posicion', 'DESC')
            ->first()){
            $suma = $info->posicion + 1;
        }else{
            $suma = 1;
        }

        $ca = new Populares();
        $ca->id_servicios = $request->idservicio;
        $ca->id_productos = $request->idproducto;
        $ca->posicion = $suma;

        if($ca->save()){
            return ['success' => 2];
        }else {
            return ['success' => 99];
        }
    }


    public function borrarProductosPrincipales(Request $request){

        $rules = array(
            'id' => 'required' // id (zonas)
        );
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){ return ['success' => 0]; }

        if(Populares::where('id', $request->id)->first()){
            Populares::where('id', $request->id)->delete();
        }

        return ['success'=> 1];

    }


    public function ordenarProductosPopulares(Request $request){

        $tasks = Populares::all();

        foreach ($tasks as $task) {
            $id = $task->id;

            foreach ($request->order as $order) {
                if ($order['id'] == $id) {
                    $task->update(['posicion' => $order['posicion']]);
                }
            }
        }
        return ['success' => 1];
    }



}
