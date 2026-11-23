<?php

namespace App\Http\Controllers\Backend\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\CierresProgramados;
use App\Models\Servicios;
use App\Models\Zonas;
use App\Models\ZonasServicio;
use App\Models\ZonasPoligono;
use App\Support\Geometria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ZonasController extends Controller
{
    // un poligono necesita al menos 3 puntos para cerrar un area
    const MINIMO_PUNTOS = 3;
    const MAXIMO_AREAS = 20;

    public function __construct(){
        $this->middleware('auth');
        $this->middleware('can:sidebar.zonas');
    }

    // todas las zonas (mapa general); se entra desde Restaurantes
    public function index(){
        $servicios = Servicios::orderBy('nombre')->get(['id', 'nombre']);
        $restaurante = null;
        return view('backend.admin.configuracion.zonas.vistazonas', compact('servicios', 'restaurante'));
    }

    // zonas de un restaurante; $id = 'sin' para las zonas que aun no tienen restaurante
    public function indexRestaurante($id){
        $servicios = Servicios::orderBy('nombre')->get(['id', 'nombre']);

        if($id === 'sin'){
            $restaurante = (object) ['id' => 'sin', 'nombre' => 'Zonas sin restaurante'];
        }else{
            $restaurante = $servicios->firstWhere('id', (int) $id);
            if(!$restaurante){ return redirect()->route('index.servicios.listado'); }
        }

        return view('backend.admin.configuracion.zonas.vistazonas', compact('servicios', 'restaurante'));
    }

    // lista de zonas de un restaurante (o de las que no tienen)
    public static function rutaZonasDe($idServicio): string
    {
        return url('/admin/restaurantes/' . ($idServicio ?: 'sin') . '/zonas');
    }

    // restaurante (servicio) que atiende cada zona: [id_zona => ['id' => .., 'nombre' => ..]]
    private function restaurantesPorZona(): array
    {
        $lista = [];
        $filas = DB::table('zonas_servicio AS zs')
            ->join('servicios AS s', 's.id', '=', 'zs.id_servicios')
            ->orderBy('zs.id')
            ->get(['zs.id_zonas', 's.id', 's.nombre']);

        foreach ($filas as $f){
            $lista[$f->id_zonas] ??= ['id' => $f->id, 'nombre' => $f->nombre];
        }
        return $lista;
    }

    // areas (poligonos) de cada zona: [id_zona => [ [[lat, lng], ...], ... ]]; el orden de los puntos es el de los id
    private function areasPorZona($consulta = null): array
    {
        $filas = ($consulta ?? ZonasPoligono::query())
            ->orderBy('id_zonas')->orderBy('parte')->orderBy('id')
            ->get(['id_zonas', 'parte', 'latitud', 'longitud']);

        $areas = [];
        foreach ($filas as $p){
            $areas[$p->id_zonas][$p->parte][] = [(float) $p->latitud, (float) $p->longitud];
        }
        return array_map('array_values', $areas);
    }

    // la app falla si un cliente elige una zona sin restaurante, o sin poligono no la puede ubicar
    private function motivoNoVisible(int $idZona): int
    {
        if(ZonasPoligono::where('id_zonas', $idZona)->count() < self::MINIMO_PUNTOS){
            return 2;
        }
        if(!ZonasServicio::where('id_zonas', $idZona)->exists()){
            return 4;
        }
        return 0;
    }

    // asigna el restaurante solo si la zona aun no tiene (cambiarlo afecta carritos y direcciones)
    private function asignarRestaurante(int $idZona, $idServicio): void
    {
        if(empty($idServicio) || ZonasServicio::where('id_zonas', $idZona)->exists()){
            return;
        }
        $zs = new ZonasServicio();
        $zs->id_zonas = $idZona;
        $zs->id_servicios = (int) $idServicio;
        $zs->fecha = Carbon::now('America/El_Salvador');
        $zs->save();
    }

    // tabla para ver zonas
    public function tablaZonas(Request $request){
        $zonas = Zonas::orderBy('nombre', 'ASC')->orderBy('id', 'ASC');

        // ?servicio=ID solo las de ese restaurante; ?servicio=sin las que no tienen
        if($request->servicio === 'sin'){
            $zonas->whereNotIn('id', ZonasServicio::pluck('id_zonas'));
        }elseif($request->filled('servicio')){
            $zonas->whereIn('id', ZonasServicio::where('id_servicios', (int) $request->servicio)->pluck('id_zonas'));
        }
        $zonas = $zonas->get();
        $porRestaurante = $request->filled('servicio');

        $puntos = ZonasPoligono::select('id_zonas', DB::raw('COUNT(*) AS total'), DB::raw('COUNT(DISTINCT parte) AS areas'))
            ->groupBy('id_zonas')
            ->get()->keyBy('id_zonas');

        // misma regla que la app al procesar una orden: abre <= hora actual <= cierra
        $ahora = Carbon::now('America/El_Salvador')->format('H:i:s');
        $restaurantes = $this->restaurantesPorZona();
        $usos = $this->usosPorZona();

        // cierres programados que aplican hoy, por restaurante (null = todos)
        $hoy = Carbon::now('America/El_Salvador');
        $cierresHoy = CierresProgramados::orderBy('id')->get()->filter(fn($c) => $c->aplicaEn($hoy));

        foreach ($zonas as $info){
            $info->usos = (int) ($usos[$info->id] ?? 0);
            $info->id_servicio = $restaurantes[$info->id]['id'] ?? null;
            $info->cierre_hoy = $cierresHoy->first(fn($c) => $c->id_servicios === null
                || ($info->id_servicio && (int) $c->id_servicios === (int) $info->id_servicio));
            $info->restaurante = $restaurantes[$info->id]['nombre'] ?? null;
            $info->en_horario = $info->hora_abierto_delivery <= $ahora && $ahora <= $info->hora_cerrado_delivery;
            $info->hora_abierto_texto = date("h:i A", strtotime($info->hora_abierto_delivery));
            $info->hora_cerrado_texto = date("h:i A", strtotime($info->hora_cerrado_delivery));
            $info->minimo_texto = '$' . number_format((float)$info->minimo, 2, '.', ',');
            $info->puntos = (int) ($puntos[$info->id]->total ?? 0);
            $info->areas = (int) ($puntos[$info->id]->areas ?? 0);
        }

        return view('backend.admin.configuracion.zonas.tablazonas', compact('zonas', 'porRestaurante'));
    }

    // reglas comunes para crear y editar
    private function reglasZona(): array
    {
        return [
            'nombre' => 'required|string|max:50',
            'descripcion' => 'required|string|max:300',
            'horaabierto' => 'required|date_format:H:i',
            // la app no maneja horarios que pasan la medianoche
            'horacerrado' => 'required|date_format:H:i|after:horaabierto',
            'tiempoextra' => 'required|integer|min:0|max:500',
            'minimo' => 'required|numeric|min:0|max:1000000',
            'servicio' => 'nullable|integer|exists:servicios,id',
        ];
    }

    // mensaje en español para el primer campo que fallo la validacion
    private function mensajeError($validator): string
    {
        $nombres = [
            'nombre' => 'Nombre (máximo 50 caracteres)',
            'descripcion' => 'Descripción (máximo 300 caracteres)',
            'horaabierto' => 'Hora de apertura',
            'horacerrado' => 'Hora de cierre (debe ser después de la apertura)',
            'tiempoextra' => 'Tiempo extra (entero entre 0 y 500)',
            'minimo' => 'Mínimo de compra',
            'mensaje' => 'Mensaje de cierre (máximo 100 caracteres)',
            'servicio' => 'Restaurante',
        ];

        $campo = array_key_first($validator->failed());

        return 'Revisar: ' . ($nombres[$campo] ?? $campo);
    }

    // las horas llegan como "HH:MM" o "HH:MM:SS" segun el navegador
    private function normalizarHoras(Request $request): void
    {
        foreach (['horaabierto', 'horacerrado'] as $campo){
            if(is_string($request->$campo) && strlen($request->$campo) > 5){
                $request->merge([$campo => substr($request->$campo, 0, 5)]);
            }
        }
    }

    // crear zona (se crea oculta: primero hay que dibujar su poligono)
    public function nuevaZona(Request $request){

        $this->normalizarHoras($request);
        $validator = Validator::make($request->all(), $this->reglasZona());

        if ($validator->fails()){
            return ['success' => 0, 'mensaje' => $this->mensajeError($validator)];
        }

        $zona = new Zonas();
        $zona->nombre = $request->nombre;
        // el centro se calcula al guardar el poligono
        $zona->latitud = '';
        $zona->longitud = '';
        $zona->saturacion = 0;
        $zona->hora_abierto_delivery = $request->horaabierto;
        $zona->hora_cerrado_delivery = $request->horacerrado;
        $zona->activo = 0;
        $zona->tiempo_extra = (int) $request->tiempoextra;
        $zona->mensaje_bloqueo = null;
        $zona->minimo = $request->minimo;
        $zona->descripcion = $request->descripcion;

        if($zona->save()){
            $this->asignarRestaurante($zona->id, $request->servicio);
            return ['success' => 1, 'id' => $zona->id];
        }

        return ['success' => 2];
    }

    // informacion de la zona
    public function informacionZona(Request $request){
        $validator = Validator::make($request->all(), ['id' => 'required|integer']);
        if ($validator->fails()){return ['success' => 0];}

        if($zona = Zonas::where('id', $request->id)->first()){
            $zona->hora_abierto_delivery = substr($zona->hora_abierto_delivery, 0, 5);
            $zona->hora_cerrado_delivery = substr($zona->hora_cerrado_delivery, 0, 5);
            $zona->puntos = ZonasPoligono::where('id_zonas', $zona->id)->count();
            $zona->id_servicio = $this->restaurantesPorZona()[$zona->id]['id'] ?? null;

            return ['success' => 1, 'zona' => $zona];
        }

        return ['success' => 2];
    }

    // editar la zona
    public function editarZona(Request $request){

        $this->normalizarHoras($request);
        $reglas = $this->reglasZona() + [
            'id' => 'required|integer',
            'togglep' => 'required|in:0,1',
            'togglea' => 'required|in:0,1',
            'mensaje' => 'nullable|string|max:100|required_if:togglep,1',
        ];

        $validator = Validator::make($request->all(), $reglas);

        if ($validator->fails()){
            return ['success' => 0, 'mensaje' => $this->mensajeError($validator)];
        }

        $zona = Zonas::where('id', $request->id)->first();
        if($zona == null){
            return ['success' => 99];
        }

        $this->asignarRestaurante($zona->id, $request->servicio);

        // no se puede mostrar en la app una zona sin poligono o sin restaurante
        if((int) $request->togglea === 1 && ($motivo = $this->motivoNoVisible($zona->id))){
            return ['success' => $motivo];
        }

        $zona->nombre = $request->nombre;
        $zona->saturacion = (int) $request->togglep;
        $zona->hora_abierto_delivery = $request->horaabierto;
        $zona->hora_cerrado_delivery = $request->horacerrado;
        $zona->activo = (int) $request->togglea;
        $zona->tiempo_extra = (int) $request->tiempoextra;
        $zona->mensaje_bloqueo = $request->mensaje;
        $zona->minimo = $request->minimo;
        $zona->descripcion = $request->descripcion;
        $zona->save();

        return ['success' => 1];
    }

    // cambio rapido desde la tabla: visible en la app (activo) o cerrada temporalmente (saturacion)
    public function cambiarEstado(Request $request){

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'campo' => 'required|in:activo,saturacion',
            'valor' => 'required|in:0,1',
            'mensaje' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()){ return ['success' => 0]; }

        $zona = Zonas::where('id', $request->id)->first();
        if($zona == null){ return ['success' => 0]; }

        $valor = (int) $request->valor;

        if($request->campo === 'activo'){
            if($valor === 1 && ($motivo = $this->motivoNoVisible($zona->id))){
                return ['success' => $motivo];
            }
            $zona->activo = $valor;
        }else{
            $mensaje = trim((string) $request->mensaje);
            if($valor === 1 && $mensaje === ''){
                return ['success' => 3];
            }
            $zona->saturacion = $valor;
            if($valor === 1){
                $zona->mensaje_bloqueo = $mensaje;
            }
        }

        $zona->save();

        return ['success' => 1];
    }

    // cerrar o abrir varias zonas a la vez: todas, o las de un restaurante
    public function cerrarVarias(Request $request){

        $validator = Validator::make($request->all(), [
            'alcance' => 'required|in:todas,restaurante',
            'servicio' => 'required_if:alcance,restaurante|nullable|integer|exists:servicios,id',
            'valor' => 'required|in:0,1',
            'mensaje' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()){ return ['success' => 0]; }

        $valor = (int) $request->valor;
        $mensaje = trim((string) $request->mensaje);
        if($valor === 1 && $mensaje === ''){ return ['success' => 3]; }

        $consulta = Zonas::query();
        if($request->alcance === 'restaurante'){
            $consulta->whereIn('id', ZonasServicio::where('id_servicios', $request->servicio)->pluck('id_zonas'));
        }

        $datos = ['saturacion' => $valor];
        if($valor === 1){ $datos['mensaje_bloqueo'] = $mensaje; }

        $total = $consulta->update($datos);

        return ['success' => 1, 'total' => $total];
    }

    // ---- cierres programados por fecha ----

    public function listaCierres(){
        $hoy = Carbon::now('America/El_Salvador');
        $nombres = Servicios::pluck('nombre', 'id');

        $lista = CierresProgramados::orderBy('fecha_inicio')->get()->map(function ($c) use ($hoy, $nombres) {
            $vencido = !$c->anual && $c->fecha_fin < $hoy->toDateString();
            return [
                'id' => $c->id,
                'restaurante' => $c->id_servicios ? ($nombres[$c->id_servicios] ?? '') : 'Todos los restaurantes',
                'id_servicio' => $c->id_servicios ? (int) $c->id_servicios : null,
                'inicio' => $c->anual ? Carbon::parse($c->fecha_inicio)->format('d/m') : Carbon::parse($c->fecha_inicio)->format('d/m/Y'),
                'fin' => $c->anual ? Carbon::parse($c->fecha_fin)->format('d/m') : Carbon::parse($c->fecha_fin)->format('d/m/Y'),
                'anual' => (int) $c->anual,
                'mensaje' => $c->mensaje,
                'estado' => $c->aplicaEn($hoy) ? 'hoy' : ($vencido ? 'vencido' : 'pendiente'),
            ];
        })->sortBy(fn($c) => ['hoy' => 0, 'pendiente' => 1, 'vencido' => 2][$c['estado']])->values();

        return ['success' => 1, 'cierres' => $lista];
    }

    public function nuevoCierre(Request $request){

        $validator = Validator::make($request->all(), [
            'servicio' => 'nullable|integer|exists:servicios,id',
            'inicio' => 'required|date_format:Y-m-d',
            'fin' => 'required|date_format:Y-m-d',
            'anual' => 'required|in:0,1',
            'mensaje' => 'required|string|max:100',
        ]);
        if ($validator->fails()){ return ['success' => 0]; }

        // los anuales pueden cruzar fin de año (31 dic al 2 ene); los de una vez no
        if(!(int) $request->anual && $request->fin < $request->inicio){
            return ['success' => 2];
        }

        $c = new CierresProgramados();
        $c->id_servicios = $request->servicio ?: null;
        $c->fecha_inicio = $request->inicio;
        $c->fecha_fin = $request->fin;
        $c->anual = (int) $request->anual;
        $c->mensaje = trim($request->mensaje);

        // avisar si ya hay otro cierre esos dias para el mismo restaurante (o para todos);
        // el usuario puede guardarlo igual reenviando con confirmar=1
        if(!$request->confirmar){
            $choques = $this->cierresQueChocan($c);
            if($choques->isNotEmpty()){
                $nombres = Servicios::pluck('nombre', 'id');
                return ['success' => 3, 'choques' => $choques->map(fn($x) => [
                    'restaurante' => $x->id_servicios ? ($nombres[$x->id_servicios] ?? '') : 'Todos los restaurantes',
                    'fechas' => $x->anual
                        ? Carbon::parse($x->fecha_inicio)->format('d/m') . ' al ' . Carbon::parse($x->fecha_fin)->format('d/m') . ' (cada año)'
                        : Carbon::parse($x->fecha_inicio)->format('d/m/Y') . ' al ' . Carbon::parse($x->fecha_fin)->format('d/m/Y'),
                    'mensaje' => $x->mensaje,
                ])->values()];
            }
        }

        $c->fecha_registro = Carbon::now('America/El_Salvador');
        $c->save();

        return ['success' => 1];
    }

    // cierres ya guardados que caen algun mismo dia que $nuevo y afectan al mismo restaurante
    private function cierresQueChocan(CierresProgramados $nuevo)
    {
        return CierresProgramados::orderBy('id')->get()->filter(function ($otro) use ($nuevo) {

            $mismoRestaurante = $otro->id_servicios === null || $nuevo->id_servicios === null
                || (int) $otro->id_servicios === (int) $nuevo->id_servicios;
            if(!$mismoRestaurante) return false;

            // dias a revisar: los de un cierre de una sola vez; si ambos son anuales, un año completo (bisiesto)
            $base = !$nuevo->anual ? $nuevo : (!$otro->anual ? $otro : null);
            $dia = $base ? Carbon::parse($base->fecha_inicio) : Carbon::create(2024, 1, 1);
            $fin = $base ? Carbon::parse($base->fecha_fin) : Carbon::create(2024, 12, 31);

            for ($n = 0; $dia->lte($fin) && $n < 400; $dia->addDay(), $n++){
                if($nuevo->aplicaEn($dia) && $otro->aplicaEn($dia)) return true;
            }
            return false;
        });
    }

    public function borrarCierre(Request $request){
        $validator = Validator::make($request->all(), ['id' => 'required|integer']);
        if ($validator->fails()){ return ['success' => 0]; }

        CierresProgramados::where('id', $request->id)->delete();
        return ['success' => 1];
    }

    // ordenes y direcciones de clientes que usan cada zona: [id_zona => total]
    private function usosPorZona(): array
    {
        $usos = [];
        foreach ([['ordenes', 'id_zona'], ['direccion_cliente', 'id_zonas']] as [$tabla, $columna]){
            $conteo = DB::table($tabla)->select($columna, DB::raw('COUNT(*) AS total'))
                ->groupBy($columna)->pluck('total', $columna);
            foreach ($conteo as $idZona => $total){
                $usos[$idZona] = ($usos[$idZona] ?? 0) + $total;
            }
        }
        return $usos;
    }

    // borra la zona solo si nunca se uso (sin ordenes ni direcciones de clientes)
    public function borrarZona(Request $request){

        $validator = Validator::make($request->all(), ['id' => 'required|integer']);
        if($validator->fails()){ return ['success' => 0]; }

        $zona = Zonas::where('id', $request->id)->first();
        if($zona == null){ return ['success' => 0]; }

        $ordenes = DB::table('ordenes')->where('id_zona', $zona->id)->count();
        $direcciones = DB::table('direccion_cliente')->where('id_zonas', $zona->id)->count();

        if($ordenes > 0 || $direcciones > 0){
            return ['success' => 2, 'ordenes' => $ordenes, 'direcciones' => $direcciones];
        }

        DB::transaction(function () use ($zona) {
            ZonasPoligono::where('id_zonas', $zona->id)->delete();
            ZonasServicio::where('id_zonas', $zona->id)->delete();
            $zona->delete();
        });

        return ['success' => 1];
    }

    // todas las zonas con su poligono, para el mapa general
    public function mapaGeneral(){

        $areas = $this->areasPorZona();
        $restaurantes = $this->restaurantesPorZona();

        $zonas = Zonas::orderBy('nombre')->get(['id', 'nombre', 'activo', 'saturacion'])
            ->map(fn($z) => [
                'id' => $z->id,
                'nombre' => $z->nombre,
                'restaurante' => $restaurantes[$z->id]['nombre'] ?? 'Sin restaurante',
                'id_servicio' => $restaurantes[$z->id]['id'] ?? null,
                'activo' => (int) $z->activo,
                'saturacion' => (int) $z->saturacion,
                'areas' => $areas[$z->id] ?? [],
            ]);

        return ['success' => 1, 'zonas' => $zonas];
    }

    // editor del poligono sobre el mapa
    public function indexPoligono($id){

        $zona = Zonas::where('id', $id)->first();
        if($zona == null){
            return redirect()->route('index.servicios.listado');
        }

        $areas = $this->areasPorZona(ZonasPoligono::where('id_zonas', $zona->id))[$zona->id] ?? [];

        // areas de las demas zonas, en gris, para no dibujar encima de otra (una entrada por area)
        $otras = $this->areasPorZona(ZonasPoligono::where('id_zonas', '!=', $zona->id));
        $nombres = Zonas::whereIn('id', array_keys($otras))->pluck('nombre', 'id');

        $otrasZonas = [];
        foreach ($otras as $idZona => $lista){
            foreach ($lista as $puntosArea){
                $otrasZonas[] = ['nombre' => ($nombres[$idZona] ?? '') . ' #' . $idZona, 'puntos' => $puntosArea];
            }
        }

        $minimoPuntos = self::MINIMO_PUNTOS;
        $maximoAreas = self::MAXIMO_AREAS;
        $asignado = $this->restaurantesPorZona()[$zona->id] ?? null;
        $restaurante = $asignado['nombre'] ?? null;
        $rutaVolver = self::rutaZonasDe($asignado['id'] ?? null);

        return view('backend.admin.configuracion.zonas.poligonos.vistapoligonos',
            compact('zona', 'areas', 'otrasZonas', 'minimoPuntos', 'maximoAreas', 'restaurante', 'rutaVolver'));
    }

    // la vista de solo mapa se unifico con el editor del poligono
    public function verMapa($id){
        return redirect('/admin/zonas/poligono/' . (int) $id);
    }

    // guarda todas las areas de la zona (reemplaza los puntos anteriores)
    // areas = [ [[lat, lng], ...], ... ]
    public function nuevoPoligono(Request $request){

        $validar = Validator::make($request->all(), [
            'id' => 'required|integer',
            'areas' => 'required|array|min:1|max:' . self::MAXIMO_AREAS,
            'areas.*' => 'required|array|min:' . self::MINIMO_PUNTOS,
            'areas.*.*' => 'required|array|size:2',
            'areas.*.*.0' => 'required|numeric|between:-90,90',
            'areas.*.*.1' => 'required|numeric|between:-180,180',
        ]);

        if ($validar->fails()){
            return ['success' => 0];
        }

        $zona = Zonas::where('id', $request->id)->first();
        if($zona == null){
            return ['success' => 0];
        }

        // no se guarda si alguna area se encima con otra zona (el cliente quedaria en dos zonas)
        $areasNuevas = array_map(fn($a) => array_map(fn($p) => [(float) $p[0], (float) $p[1]], $a), array_values($request->areas));
        $otras = $this->areasPorZona(ZonasPoligono::where('id_zonas', '!=', $zona->id));
        $chocan = [];
        foreach ($otras as $idZona => $lista){
            foreach ($lista as $puntosOtra){
                foreach ($areasNuevas as $puntos){
                    if(count($puntosOtra) >= self::MINIMO_PUNTOS && Geometria::seEnciman($puntos, $puntosOtra)){
                        $chocan[$idZona] = true;
                        continue 3;
                    }
                }
            }
        }
        if($chocan){
            $nombres = Zonas::whereIn('id', array_keys($chocan))->pluck('nombre', 'id');
            return ['success' => 2, 'zonas' => collect(array_keys($chocan))->map(fn($id) => ($nombres[$id] ?? '') . ' #' . $id)->values()];
        }

        DB::transaction(function () use ($request, $zona) {
            // el orden de los puntos es el orden de los id (la app los lee asi)
            ZonasPoligono::where('id_zonas', $zona->id)->delete();

            $filas = [];
            foreach (array_values($request->areas) as $i => $puntos){
                foreach ($puntos as $p){
                    $filas[] = [
                        'id_zonas' => $zona->id,
                        'parte' => $i + 1,
                        'latitud' => (string) $p[0],
                        'longitud' => (string) $p[1],
                    ];
                }
            }
            ZonasPoligono::insert($filas);

            // centro de la zona = promedio de los vertices (solo informativo; las apps no lo usan)
            $zona->latitud = (string) round(array_sum(array_map(fn($f) => (float) $f['latitud'], $filas)) / count($filas), 8);
            $zona->longitud = (string) round(array_sum(array_map(fn($f) => (float) $f['longitud'], $filas)) / count($filas), 8);
            $zona->save();
        });

        return ['success' => 1];
    }

    // borra todos los puntos; la zona se oculta porque sin poligono la app no puede usarla
    public function borrarPoligonos(Request $request){

        $validator = Validator::make($request->all(), ['id' => 'required|integer']);
        if($validator->fails()){ return ['success' => 0]; }

        DB::transaction(function () use ($request) {
            ZonasPoligono::where('id_zonas', $request->id)->delete();
            Zonas::where('id', $request->id)->update(['activo' => 0]);
        });

        return ['success'=> 1];
    }
}
