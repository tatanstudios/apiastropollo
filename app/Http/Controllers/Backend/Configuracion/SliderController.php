<?php

namespace App\Http\Controllers\Backend\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Categorias;
use App\Models\Productos;
use App\Models\Slider;
use App\Models\SubCategorias;
use App\Services\Imagenes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SliderController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }


    public function index($id){
        // id servicios

        // productos del restaurante agrupados por "Categoría › Sub categoría" para el selector
        $grupoProductos = DB::table('productos AS p')
            ->join('sub_categorias AS s', 's.id', '=', 'p.id_subcategorias')
            ->join('categorias AS c', 'c.id', '=', 's.id_categorias')
            ->where('c.id_servicios', $id)
            ->orderBy('c.posicion')->orderBy('s.posicion')->orderBy('p.posicion')
            ->select('p.id', 'p.nombre', 'p.activo', 'c.nombre AS categoria', 's.nombre AS subcategoria')
            ->get()
            ->groupBy(fn ($p) => $p->categoria . ' › ' . $p->subcategoria);

        $restaurante = \App\Models\Servicios::where('id', $id)->value('nombre');

        return view('backend.admin.configuracion.slider.vistaslider', compact('grupoProductos', 'id', 'restaurante'));
    }

    public function sliderTabla($id){
        // id servicios

        $slider = Slider::where('id_servicios', $id)->orderBy('posicion')->get();

        $productos = Productos::whereIn('id', $slider->pluck('id_producto')->filter())->get()->keyBy('id');

        foreach ($slider as $info){

            $infoProducto = $productos->get($info->id_producto);
            $info->producto = $infoProducto ? $infoProducto->nombre : null;
            $info->producto_activo = $infoProducto ? (int) $infoProducto->activo : null;

            $info->hora_abre = date("h:i A", strtotime($info->hora_abre));
            $info->hora_cierra = date("h:i A", strtotime($info->hora_cierra));
        }

        return view('backend.admin.configuracion.slider.tablaslider', compact('slider'));
    }

    public function nuevaSlider(Request $request){

        // idservicio

        // imagen: JPG/PNG real, peso maximo, y se guarda reducida
        if($error = Imagenes::validar($request, true)){
            return ['success' => 3, 'mensaje' => $error];
        }

        if($request->hasFile('imagen')){

            $nombreFoto = Imagenes::guardar($request->file('imagen'), Imagenes::LADO_BANNER);
            if(!$nombreFoto){
                return ['success' => 3, 'mensaje' => Imagenes::mensajeError()];
            }
            $upload = true;

            if($upload){

                if($info = Slider::where('id_servicios', $request->idservicio)
                    ->orderBy('posicion', 'DESC')
                    ->first()){
                    $suma = $info->posicion + 1;
                }else{
                    $suma = 1;
                }

                $ca = new Slider();
                $ca->id_producto = $request->producto;
                $ca->id_servicios = $request->idservicio;
                $ca->imagen = $nombreFoto;
                $ca->posicion = $suma;
                $ca->nombre = $request->nombre;
                $ca->redireccionamiento = $request->toggledireccion;
                $ca->usa_horario = $request->togglehorario;
                $ca->hora_abre = $request->horaabre;
                $ca->hora_cierra = $request->horacierra;
                $ca->activo = 1;

                if($ca->save()){
                    return ['success' => 1];
                }else{
                    return ['success' => 99];
                }
            }else{
                return ['success' => 99];
            }

        }else {
            return ['success' => 99];
        }
    }

    function editarSlider(Request $request){

        $rules = array(
            'idslider' => 'required',
            'toggleactivo' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ( $validator->fails()){return ['success' => 0];}

        if($info = Slider::where('id', $request->idslider)->first()){

            // version anterior del formulario: solo cambia el estado
            if(!$request->has('nombre')){
                Slider::where('id', $request->idslider)->update([
                    'activo' => $request->toggleactivo,
                ]);
                return ['success' => 1];
            }

            if($request->toggledireccion == 1 && empty($request->producto)){
                return ['success' => 3, 'mensaje' => 'Selecciona el producto al que redirecciona'];
            }

            $datos = [
                'activo' => $request->toggleactivo,
                'nombre' => $request->nombre,
                'redireccionamiento' => $request->toggledireccion,
                'id_producto' => $request->producto ?: null,
                'usa_horario' => $request->togglehorario,
                'hora_abre' => $request->horaabre,
                'hora_cierra' => $request->horacierra,
            ];

            // imagen nueva (opcional): se valida, se guarda reducida y se borra la anterior
            if($error = Imagenes::validar($request)){
                return ['success' => 3, 'mensaje' => $error];
            }

            $imagenOld = null;
            if($request->hasFile('imagen')){
                $nombreFoto = Imagenes::guardar($request->file('imagen'), Imagenes::LADO_BANNER);
                if(!$nombreFoto){
                    return ['success' => 3, 'mensaje' => Imagenes::mensajeError()];
                }
                $datos['imagen'] = $nombreFoto;
                $imagenOld = $info->imagen;
            }

            Slider::where('id', $request->idslider)->update($datos);

            if($imagenOld && Storage::disk('imagenes')->exists($imagenOld)){
                Storage::disk('imagenes')->delete($imagenOld);
            }

            return ['success' => 1];
        }else{
            return ['success' => 2];
        }
    }
    public function ordenarSliders(Request $request){
        $tasks = Slider::all();

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

    public function borrarSliders(Request $request){
        $rules = array(
            'id' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()){return ['success' => 0]; }

        if($info = Slider::where('id', $request->id)->first()){

            // se puede borrar aunque sea el ultimo: la app no muestra el bloque si no hay banners

            if(Storage::disk('imagenes')->exists($info->imagen)){
                Storage::disk('imagenes')->delete($info->imagen);
            }

            Slider::where('id', $request->id)->delete();

            return ['success' => 2];
        }else{
            return ['success' => 99];
        }
    }

    public function informacionSlider(Request $request){

        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){return ['success' => 0]; }

        if($bloque = Slider::where('id', $request->id)->first()){

            return ['success' => 1, 'slider' => $bloque];
        }else{
            return ['success' => 2];
        }
    }

}
