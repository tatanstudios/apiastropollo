<?php

namespace App\Http\Middleware;

use App\Models\CarritoExtra;
use App\Models\CarritoTemporal;
use App\Models\Categorias;
use App\Models\Clientes;
use App\Models\DireccionCliente;
use App\Models\Ordenes;
use App\Models\OrdenesDescripcion;
use App\Models\Productos;
use App\Models\SubCategorias;
use App\Models\UsuariosServicios;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege las rutas de las apps (se usa despues de auth:sanctum).
 *
 * Uso en rutas:  ->middleware('propio:cliente,clienteid')   o   ->middleware('propio:restaurante,id')
 *
 * 1. Exige un token de la app correcta (habilidad "cliente" o "restaurante") de un usuario activo.
 * 2. Identidad: los campos indicados (id, clienteid...) se reemplazan por el id del dueno del token,
 *    asi la app ya no puede actuar en nombre de otro usuario cambiando el id que envia.
 *    "orden=campo" indica que ese campo es un id de orden que debe ser del cliente.
 * 3. Propiedad: si la peticion trae ids de ordenes, productos de orden, carrito, direcciones,
 *    categorias o productos, comprueba que pertenezcan al dueno del token.
 */
class ApiPropietario
{
    public function handle(Request $request, Closure $next, string $tipo, string ...$campos): Response
    {
        $usuario = $request->user();
        $token = $usuario?->currentAccessToken();

        $valido = $usuario && $token && $token->can($tipo) && match ($tipo) {
            'cliente' => $usuario instanceof Clientes && (int) $usuario->activo === 1,
            'restaurante' => $usuario instanceof UsuariosServicios && (int) $usuario->bloqueado === 0,
            default => false,
        };

        if (!$valido) {
            return response()->json(['success' => 401, 'mensaje' => 'Sesión no válida, inicie sesión de nuevo'], 401);
        }

        // 2. identidad desde el token
        $camposOrden = [];
        $identidad = [];
        foreach ($campos as $campo) {
            if (str_starts_with($campo, 'orden=')) {
                $camposOrden[] = substr($campo, 6);
            } else {
                $identidad[$campo] = (string) $usuario->id;
            }
        }
        $request->merge($identidad);

        // 3. propiedad de los recursos
        $propio = $tipo === 'cliente'
            ? $this->esDelCliente($request, $usuario, $camposOrden)
            : $this->esDelRestaurante($request, $usuario);

        if (!$propio) {
            return response()->json(['success' => 403, 'mensaje' => 'No autorizado'], 403);
        }

        return $next($request);
    }

    private function esDelCliente(Request $request, Clientes $cliente, array $camposOrden): bool
    {
        foreach (array_merge(['ordenid', 'idorden'], $camposOrden) as $campo) {
            if ($request->filled($campo)
                && !Ordenes::where('id', $request->input($campo))->where('id_cliente', $cliente->id)->exists()) {
                return false;
            }
        }

        if ($request->filled('idordendescrip')) {
            $idOrden = OrdenesDescripcion::where('id', $request->input('idordendescrip'))->value('id_ordenes');
            if (!$idOrden || !Ordenes::where('id', $idOrden)->where('id_cliente', $cliente->id)->exists()) {
                return false;
            }
        }

        if ($request->filled('carritoid')) {
            $idCarrito = CarritoExtra::where('id', $request->input('carritoid'))->value('id_carrito_temporal');
            if (!$idCarrito || !CarritoTemporal::where('id', $idCarrito)->where('id_clientes', $cliente->id)->exists()) {
                return false;
            }
        }

        if ($request->filled('dirid')
            && !DireccionCliente::where('id', $request->input('dirid'))->where('id_cliente', $cliente->id)->exists()) {
            return false;
        }

        return true;
    }

    private function esDelRestaurante(Request $request, UsuariosServicios $usuario): bool
    {
        $servicio = $usuario->id_servicios;

        if ($request->filled('idorden')
            && !Ordenes::where('id', $request->input('idorden'))->where('id_servicio', $servicio)->exists()) {
            return false;
        }

        if ($request->filled('idordendescrip')) {
            $idOrden = OrdenesDescripcion::where('id', $request->input('idordendescrip'))->value('id_ordenes');
            if (!$idOrden || !Ordenes::where('id', $idOrden)->where('id_servicio', $servicio)->exists()) {
                return false;
            }
        }

        if ($request->filled('idcategoria')
            && !Categorias::where('id', $request->input('idcategoria'))->where('id_servicios', $servicio)->exists()) {
            return false;
        }

        if ($request->filled('idproducto')) {
            $idSub = Productos::where('id', $request->input('idproducto'))->value('id_subcategorias');
            $idCategoria = $idSub ? SubCategorias::where('id', $idSub)->value('id_categorias') : null;
            if (!$idCategoria || !Categorias::where('id', $idCategoria)->where('id_servicios', $servicio)->exists()) {
                return false;
            }
        }

        return true;
    }
}
