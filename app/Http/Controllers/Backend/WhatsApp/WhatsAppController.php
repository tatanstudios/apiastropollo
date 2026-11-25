<?php

namespace App\Http\Controllers\Backend\WhatsApp;

use App\Http\Controllers\Backend\Roles\PermisoController;
use App\Http\Controllers\Controller;
use App\Services\WhatsAppBolson;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Panel de WhatsApp (solo usuarios con permiso de Roles y Permisos):
 * numeros de cada restaurante, saldo del bolson de mensajes y su historial.
 */
class WhatsAppController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
        $this->middleware('can:' . PermisoController::PERMISO_ROLES);
    }

    public function index(){
        $configurado = WhatsAppService::configurado();
        return view('backend.admin.whatsapp.vistawhatsapp', compact('configurado'));
    }

    // saldo actual (para refrescar la tarjeta sin recargar la pagina)
    public function resumen(){
        $bolson = DB::table('whatsapp_bolson')->where('id', WhatsAppBolson::FILA)->first();

        return [
            'success' => 1,
            'disponibles' => (int) ($bolson->disponibles ?? 0),
            'enviados' => (int) ($bolson->enviados ?? 0),
            'enviados_hoy' => DB::table('whatsapp_movimientos')->where('tipo', 'envio')
                ->whereDate('fecha', now()->toDateString())->count(),
        ];
    }

    // restaurantes con su numero y cuantos mensajes se les han enviado
    public function tablaNumeros(){
        $enviados = DB::table('whatsapp_movimientos')
            ->where('tipo', 'envio')
            ->whereNotNull('id_servicio')
            ->groupBy('id_servicio')
            ->select('id_servicio', DB::raw('COUNT(*) as total'), DB::raw('MAX(fecha) as ultimo'))
            ->get()
            ->keyBy('id_servicio');

        $restaurantes = DB::table('servicios')->orderBy('nombre')->get(['id', 'nombre', 'whatsapp'])
            ->map(function ($s) use ($enviados) {
                $s->enviados = $enviados[$s->id]->total ?? 0;
                $s->ultimo = $enviados[$s->id]->ultimo ?? null;
                return $s;
            });

        return view('backend.admin.whatsapp.tablanumeros', compact('restaurantes'));
    }

    // historial: 'ajustes' (lo que hicieron los administradores) o 'envios'
    public function tablaHistorial(Request $request){
        $ver = $request->ver === 'envios' ? 'envios' : 'ajustes';

        $movimientos = DB::table('whatsapp_movimientos as m')
            ->leftJoin('administrador as a', 'a.id', '=', 'm.id_administrador')
            ->leftJoin('servicios as s', 's.id', '=', 'm.id_servicio')
            ->when($ver === 'envios',
                fn ($q) => $q->where('m.tipo', 'envio'),
                fn ($q) => $q->whereIn('m.tipo', ['agregar', 'quitar']))
            ->orderByDesc('m.id')
            ->limit(1000)
            ->get(['m.*', 'a.nombre as administrador', 's.nombre as restaurante']);

        return view('backend.admin.whatsapp.tablahistorial', compact('movimientos', 'ver'));
    }

    // agregar o quitar mensajes del bolson
    public function ajustar(Request $request){
        $validar = Validator::make($request->all(), [
            'tipo' => 'required|in:agregar,quitar',
            'cantidad' => 'required|integer|min:1|max:1000000',
            'nota' => 'nullable|string|max:150',
        ]);

        if($validar->fails()){ return ['success' => 0]; }

        $saldo = WhatsAppBolson::ajustar($request->tipo, (int) $request->cantidad, Auth::id(), $request->nota);

        if($saldo === null){
            // quitar mas de lo disponible
            return ['success' => 1, 'disponibles' => WhatsAppBolson::disponibles()];
        }

        return ['success' => 2, 'disponibles' => $saldo];
    }
}
