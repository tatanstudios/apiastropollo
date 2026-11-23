<?php

namespace App\Http\Controllers\Api\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Categorias;
use App\Models\Clientes;
use App\Models\DireccionCliente;
use App\Models\Productos;
use App\Models\Servicios;
use App\Models\Slider;
use App\Models\SubCategorias;
use App\Models\Zonas;
use App\Models\ZonasServicio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiMenuController extends Controller
{


    public function listadoMenuPrincipal(Request $request){

        $rules = array(
            'id' => 'required'
        );

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){return ['success' => 0]; }

        if($data = Clientes::where('id', $request->id)->first()){
            if($data->activo == 0){
                $titulo = "Nota";
                $mensaje = "Usuario ha sido bloqueado.";

                // bloquear usuario
                return ['success' => 1, 'titulo' => $titulo, 'mensaje' => $mensaje];
            }
        }

        // conocer si tiene direccion el cliente
        $infoConteoDireccion = DireccionCliente::where('id_cliente', $request->id)->count();

        if($infoConteoDireccion == 0){
            $mensaje = "No hay direccion de entrega";
            return ['success' => 2, 'mensaje' => $mensaje];
        }

        // el cliente si tiene una direccion seleccionada
        if($infoDireccion = DireccionCliente::where('id_cliente', $request->id)
            ->where('seleccionado', 1)
            ->first()){

            // buscar si hay un servicio asignado a la zona
            if($infoZonaServicio = ZonasServicio::where('id_zonas', $infoDireccion->id_zonas)->first()){
                $menu = $this->armarMenuServicio($infoZonaServicio->id_servicios, $request->id);
                // encabezado "Entregar en" de la app
                $menu['entrega_nombre'] = $infoDireccion->etiqueta ?: $infoDireccion->nombre;
                $menu['entrega_direccion'] = $infoDireccion->direccion;
                return $menu;

            }
            else{
                $mensaje = "No hay un servicio asociado a la zona";
                return ['success' => 4, 'mensaje' => $mensaje];
            }
        }
        else{
            $mensaje = "No hay direccion de entrega seleccionado";
            return ['success' => 5, 'mensaje' => $mensaje];
        }
    }





    // SUCURSALES PARA INVITADO (sin iniciar sesion): restaurantes con al menos una zona activa
    public function listadoSucursalesInvitado(){

        $sucursales = DB::table('servicios AS s')
            ->join('zonas_servicio AS zs', 'zs.id_servicios', '=', 's.id')
            ->join('zonas AS z', 'zs.id_zonas', '=', 'z.id')
            ->where('z.activo', 1)
            ->select('s.id', 's.nombre', 'z.id AS id_zona', 'z.nombre AS zona')
            ->orderBy('s.id')
            ->get()
            ->groupBy('id')
            ->map(function ($filas) {
                return [
                    'id' => $filas->first()->id,
                    'nombre' => $filas->first()->nombre,
                    'zonas' => $filas->pluck('id_zona')->values(),
                    'descripcion' => $filas->pluck('zona')->unique()->implode(', '),
                ];
            })
            ->values();

        return ['success' => 1, 'sucursales' => $sucursales];
    }


    // MENU PARA INVITADO: por sucursal (id_servicio) o por zona (id_zona)
    public function listadoMenuInvitado(Request $request){

        $validator = Validator::make($request->all(), [
            'id_servicio' => 'required_without:id_zona',
            'id_zona' => 'required_without:id_servicio',
        ]);
        if($validator->fails()){return ['success' => 0]; }

        if($request->filled('id_servicio')){
            if(!Servicios::where('id', $request->id_servicio)->first()){
                return ['success' => 4, 'mensaje' => "Sucursal no disponible"];
            }
            return $this->armarMenuServicio($request->id_servicio, null);
        }

        if(!Zonas::where('id', $request->id_zona)->where('activo', 1)->first()){
            return ['success' => 4, 'mensaje' => "Zona no disponible"];
        }

        if($infoZonaServicio = ZonasServicio::where('id_zonas', $request->id_zona)->first()){
            return $this->armarMenuServicio($infoZonaServicio->id_servicios, null);
        }

        return ['success' => 4, 'mensaje' => "No hay un servicio asociado a la zona"];
    }


    // solo categorias con al menos un producto visible (sub categoria visible + producto visible);
    // asi el cliente no entra a una categoria vacia
    private static function conProductosVisibles($consulta)
    {
        return $consulta->whereExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('sub_categorias AS sc')
                ->join('productos AS p', 'p.id_subcategorias', '=', 'sc.id')
                ->whereColumn('sc.id_categorias', 'categorias.id')
                ->where('sc.activo', 1)
                ->where('p.activo', 1);
        });
    }


    // slider, categorias y populares del servicio (restaurante); $idCliente null = invitado
    private function armarMenuServicio($idServicio, $idCliente){

        $sliderActivos = Slider::where('id_servicios', $idServicio)
            ->where('activo', 1)
            ->orderBy('posicion')
            ->get();

        // horario propio del banner: solo se muestra dentro de su rango
        // (si cierra antes de abrir, cruza la medianoche, ej. 20:00 - 02:00)
        $horaBanner = Carbon::now('America/El_Salvador')->format('H:i:s');
        $slider = $sliderActivos->filter(function ($s) use ($horaBanner) {
            if ($s->usa_horario != 1) {
                return true;
            }
            if ($s->hora_abre <= $s->hora_cierra) {
                return $horaBanner >= $s->hora_abre && $horaBanner <= $s->hora_cierra;
            }
            return $horaBanner >= $s->hora_abre || $horaBanner <= $s->hora_cierra;
        })->values();

        // sin banners (ninguno, todos ocultos o fuera de horario) la lista va vacia y "hayslider" = 0
        // para que la app no muestre ese espacio

        // el 0 que se asigna, verifico que producto no lleva redireccionamiento en slider
        foreach ($slider as $info){

            if($info->redireccionamiento == 0){
                $info->id_producto = 0;
            }

            if($info->id_producto == null){
                $info->id_producto = 0;
            }
        }

        //**********************************

        $getValores = Carbon::now('America/El_Salvador');
        $hora = $getValores->format('H:i:s');

        // TODAS las categorias del restaurante que esten activas y, si usan horario, dentro de el.
        // (antes solo se enviaban las "categorias principales" y el resto quedaba en "ver mas")
        $arrayCategorias = Categorias::where('id_servicios', $idServicio)
            ->where('activo', 1)
            ->where(function ($q) use ($hora) {
                $q->where('usa_horario', 0)
                    ->orWhere(function ($q) use ($hora) {
                        $q->where('usa_horario', 1)
                            ->where('hora_abre', '<=', $hora)
                            ->where('hora_cierra', '>=', $hora);
                    });
            })
            ->tap(fn ($q) => self::conProductosVisibles($q))
            ->orderBy('posicion', 'ASC')
            ->get(['id', 'imagen', 'nombre', 'posicion']);

        // validar que haya categorias, sino ocultar
        $hayCategorias = 0;
        if ($arrayCategorias->count()){
            $hayCategorias = 1;
        }


        //**********************

        // productos populares que el cliente puede pedir: producto, sub categoria y categoria visibles,
        // y la categoria dentro de su horario si lo usa (antes no se revisaba el producto ni la sub
        // categoria, y una categoria con horario aparecia aunque estuviera fuera de el)
        $arrayProductos = DB::table('populares AS pop')
            ->join('productos AS pro', 'pop.id_productos', '=', 'pro.id')
            ->join('sub_categorias AS subca', 'pro.id_subcategorias', '=', 'subca.id')
            ->join('categorias AS ca', 'subca.id_categorias', '=', 'ca.id')
            ->select('pro.id', 'pro.nombre', 'pro.imagen', 'pro.utiliza_imagen', 'pro.precio', 'pop.posicion')
            ->where('pop.id_servicios', $idServicio)
            ->where('ca.id_servicios', $idServicio)
            ->where('pro.activo', 1)
            ->where('subca.activo', 1)
            ->where('ca.activo', 1)
            ->where(function ($q) use ($hora) {
                $q->where('ca.usa_horario', 0)
                    ->orWhere(function ($q) use ($hora) {
                        $q->where('ca.hora_abre', '<=', $hora)
                            ->where('ca.hora_cierra', '>=', $hora);
                    });
            })
            ->orderBy('pop.posicion')
            ->get();

        foreach ($arrayProductos as $info){
            $info->precio = '$' . number_format((float)$info->precio, 2, '.', ',');
        }

        $hayPopulares = 0;
        if ($arrayProductos->count()){
            $hayPopulares = 1;
        }

        // productos que el cliente puede pedir ahora (mismas reglas que las categorias visibles),
        // para que la app busque al instante sin otra peticion
        $buscables = DB::table('productos AS pro')
            ->join('sub_categorias AS subca', 'pro.id_subcategorias', '=', 'subca.id')
            ->join('categorias AS ca', 'subca.id_categorias', '=', 'ca.id')
            ->select('pro.id', 'pro.nombre', 'pro.descripcion', 'pro.imagen', 'pro.utiliza_imagen',
                'pro.precio', 'ca.nombre AS categoria')
            ->whereIn('ca.id', $arrayCategorias->pluck('id'))
            ->where('pro.activo', 1)
            ->where('subca.activo', 1)
            ->orderBy('ca.posicion')
            ->orderBy('subca.posicion')
            ->orderBy('pro.posicion')
            ->get();

        foreach ($buscables as $info){
            $info->precio = '$' . number_format((float)$info->precio, 2, '.', ',');
        }

        $servicio = Servicios::where('id', $idServicio)->first();


        // el slider puede venir vacio: la app debe ocultar el bloque cuando "hayslider" = 0
        return [
            'success' => 3,
            'slider' => $slider,
            'hayslider' => $slider->isEmpty() ? 0 : 1,
            'categorias' => $arrayCategorias,
            'populares' => $arrayProductos,
            'haycategorias' => $hayCategorias,
            // ya van todas las categorias: la app no necesita el boton "ver mas"
            'vermascategorias' => 0,
            'haypopulares' => $hayPopulares,
            'productos' => $buscables,
            'sucursal' => $servicio ? $servicio->nombre : null,
        ];
    }


    // categorias de una zona para invitado (misma logica que listaDeTodasLasCategorias)
    public function listaDeTodasLasCategorias(Request $request){

        // ESTA PETICION SIEMPRE EL CLIENTE DEBERA TENER UNA DIRECCION YA REGISTRADA

        $infoDireccion = DireccionCliente::where('id_cliente', $request->id)
            ->where('seleccionado', 1)
            ->first();

        if($infoZonaServicio = ZonasServicio::where('id_zonas', $infoDireccion->id_zonas)->first()){

            $getValores = Carbon::now('America/El_Salvador');
            $hora = $getValores->format('H:i:s');

            $pilaIdCategorias = array();

            // obtener las categorias del servicio (ACTIVAS Y UTILIZAN HORARIO)
            $categoriasHorario = Categorias::where('id_servicios', $infoZonaServicio->id_servicios)
                ->where('activo', 1)
                ->where('usa_horario', 1)
                ->where('hora_abre', '<=', $hora)
                ->where('hora_cierra', '>=', $hora)
                ->get();

            foreach ($categoriasHorario as $info){
                array_push($pilaIdCategorias, $info->id);
            }

            // obtener las categorias del servicio (ACTIVAS)

            $categoriasActivas = Categorias::where('id_servicios', $infoZonaServicio->id_servicios)
                ->where('activo', 1)
                ->where('usa_horario', 0)
                ->get();

            foreach ($categoriasActivas as $info){
                array_push($pilaIdCategorias, $info->id);
            }

            // listado de categorias ya filtradas (sin las que no tienen productos visibles)
            $arrayCategorias = Categorias::whereIn('id', $pilaIdCategorias)
                ->tap(fn ($q) => self::conProductosVisibles($q))
                ->orderBy('posicion', 'ASC')
                ->get();


            return ['success' => 1, 'categorias' => $arrayCategorias];

        }else{
            $mensaje = "No hay una servicio asociado a la zona";
            return ['success' => 2, 'mensaje' => $mensaje];
        }
    }



    // retorna listado de productos cuando es seleccionada una categoria
    public function listaDeTodosLosProductosServicio(Request $request){

        // viene id categoria

        $reglaDatos = array(
            'id' => 'required',
        );

        $validarDatos = Validator::make($request->all(), $reglaDatos);

        if($validarDatos->fails()){return ['success' => 0]; }

        $arraySubcategorias = SubCategorias::where('id_categorias', $request->id)
                ->where('activo', 1)
                ->orderBy('posicion', 'ASC')
                ->get();

            $resultsBloque = array();
            $index = 0;

            foreach($arraySubcategorias as $secciones){
                array_push($resultsBloque,$secciones);

                $subSecciones = Productos::where('id_subcategorias', $secciones->id)
                    ->where('activo', 1)
                    ->orderBy('posicion', 'ASC')
                    ->get();

                $resultsBloque[$index]->productos = $subSecciones; //agregar los productos en la sub seccion
                $index++;
            }

            return [
                'success' => 1,
                'productos' => $arraySubcategorias,
            ];
        }


        public function informacionProductoIndividual(Request $request){

            $reglaDatos = array(
                'id' => 'required',   // id producto
            );

            $validarDatos = Validator::make($request->all(), $reglaDatos);

            if($validarDatos->fails()){return ['success' => 0]; }

            if(Productos::where('id', $request->id)->first()){

                $producto = Productos::where('id', $request->id)->get();

                return [
                    'success' => 1,
                    'producto' => $producto,
                    // bloques de opciones que el cliente puede/debe elegir (vacío si no tiene)
                    'modificadores' => \App\Services\Modificadores::grupos((int) $request->id),
                ];

            }else{
                return ['success' => 2];
            }
        }

}
