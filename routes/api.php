<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Registro\ApiRegistroController;
use App\Http\Controllers\Api\Cliente\ApiClienteController;
use App\Http\Controllers\Api\Cliente\ApiDireccionesController;
use App\Http\Controllers\Api\Cliente\ApiMenuController;
use App\Http\Controllers\Api\Carrito\ApiCarritoComprasController;
use App\Http\Controllers\Api\Procesar\ApiProcesarController;
use App\Http\Controllers\Api\Ordenes\ApiOrdenesController;
use App\Http\Controllers\Api\Ordenes\ApiOrdenesRestauranteController;


/*
|--------------------------------------------------------------------------
| API de las apps (cliente y restaurante)
|--------------------------------------------------------------------------
|
| Rutas publicas: login, registro, recuperacion de contrasena y modo invitado.
| El resto exige un token de Sanctum (sin vencimiento) en el encabezado
| "Authorization: Bearer <token>", entregado al iniciar sesion o registrarse.
|
| El middleware 'propio' (App\Http\Middleware\ApiPropietario):
|   propio:cliente,clienteid   -> exige token de la app cliente y reemplaza "clienteid" por el id del token
|   propio:restaurante,id      -> exige token de la app restaurante y reemplaza "id" por el id del token
|   orden=id                   -> el campo "id" es una orden que debe ser del cliente
| Ademas comprueba que ordenes, carrito, direcciones, categorias y productos sean del dueno del token.
|
*/


// ============================== PUBLICAS ==============================

Route::post('cliente/registro', [ApiRegistroController::class, 'registroCliente']);
Route::post('cliente/login', [ApiClienteController::class, 'loginCliente']);
Route::post('restaurante/login', [ApiClienteController::class, 'loginRestaurante']);

// recuperacion de contrasena: maximo 10 peticiones por minuto por IP
Route::middleware('throttle:10,1')->group(function () {
    Route::post('cliente/enviar/codigo-correo', [ApiClienteController::class, 'enviarCodigoCorreo']);
    Route::post('cliente/verificar/codigo-correo-password', [ApiClienteController::class, 'verificarCodigoCorreoPassword']);
    Route::post('cliente/actualizar/password', [ApiClienteController::class, 'actualizarPasswordClienteCorreo']);
});

// zonas de entrega (mapa) y modo invitado: sucursales y menu sin sesion
Route::get('cliente/listado/zonas/poligonos', [ApiDireccionesController::class, 'puntosZonaPoligonos']);
Route::get('cliente/invitado/sucursales', [ApiMenuController::class, 'listadoSucursalesInvitado']);
Route::post('cliente/invitado/menu', [ApiMenuController::class, 'listadoMenuInvitado']);

// productos del menu (solo lectura, id = categoria o producto): publicos para que el invitado
// pueda ver el menu completo; agregar al carrito si pide sesion
Route::post('cliente/listado/productos/servicios', [ApiMenuController::class, 'listaDeTodosLosProductosServicio']);
Route::post('cliente/informacion/producto/individual', [ApiMenuController::class, 'informacionProductoIndividual']);


// ============================== APP CLIENTE (token) ==============================

Route::middleware('auth:sanctum')->group(function () {

    // ---- rutas donde "id" es el cliente ----
    Route::middleware('propio:cliente,id')->group(function () {
        // direcciones
        Route::post('cliente/listado/direcciones', [ApiDireccionesController::class, 'listadoDeDirecciones']);
        Route::post('cliente/nueva/direccion', [ApiDireccionesController::class, 'nuevaDireccionCliente']);
        Route::post('cliente/direcciones/elegir/direccion', [ApiClienteController::class, 'seleccionarDireccionParaOrdenes']);
        Route::post('cliente/eliminar/direccion/seleccionada', [ApiClienteController::class, 'eliminarDireccionSeleccionadaCliente']);

        // menu principal y categorias segun la direccion del cliente
        Route::post('cliente/lista/servicios-bloque', [ApiMenuController::class, 'listadoMenuPrincipal']);
        Route::post('cliente/listado/todas/categorias', [ApiMenuController::class, 'listaDeTodasLasCategorias']);

        // historial
        Route::post('cliente/historial/listado/ordenes', [ApiOrdenesController::class, 'historialOrdenesCliente']);

        // perfil
        Route::post('cliente/informacion/personal', [ApiClienteController::class, 'informacionCliente']);
        Route::post('cliente/actualizar/correo', [ApiClienteController::class, 'actualizarCorreoCliente']);
        Route::post('cliente/actualizar/correo/v2', [ApiClienteController::class, 'actualizarCorreoClienteVersion2']);
        Route::post('cliente/informacion/restaurante/horario', [ApiClienteController::class, 'informacionHorarioRestaurante']);
        Route::post('cliente/perfil/actualizar/contrasena', [ApiClienteController::class, 'actualizarPasswordClientePerfil']);
        Route::post('cliente/opcion/perfil/carrito', [ApiClienteController::class, 'infoBorrarCarritoComprasCliente']);
        Route::post('cliente/opcion/perfil/carrito/guardar', [ApiClienteController::class, 'actualizarOpcionCarritoCliente']);
    });

    // ---- rutas donde "clienteid" es el cliente ----
    Route::middleware('propio:cliente,clienteid')->group(function () {
        // carrito de compras
        Route::post('cliente/carrito/producto/agregar', [ApiCarritoComprasController::class, 'agregarProductoCarritoTemporal']);
        Route::post('cliente/carrito/ver/orden', [ApiCarritoComprasController::class, 'verCarritoDeCompras']);
        Route::post('cliente/carrito/borrar/orden', [ApiCarritoComprasController::class, 'borrarCarritoDeCompras']);
        Route::post('cliente/carrito/eliminar/producto', [ApiCarritoComprasController::class, 'borrarProductoDelCarrito']);
        Route::post('cliente/carrito/ver/producto', [ApiCarritoComprasController::class, 'verProductoCarritoEditar']);
        Route::post('cliente/carrito/cambiar/cantidad', [ApiCarritoComprasController::class, 'editarCantidadProducto']);
        Route::post('cliente/carrito/ver/proceso-orden', [ApiCarritoComprasController::class, 'verOrdenAProcesarCliente']);
        Route::post('cliente/verificar/cupon', [ApiCarritoComprasController::class, 'verificarCupon']);

        // envio de la orden
        Route::post('cliente/proceso/enviar/orden', [ApiProcesarController::class, 'enviarOrdenRestaurante']);

        // ordenes activas
        Route::post('cliente/ordenes/listado/activas', [ApiOrdenesController::class, 'verListadoOrdenesActivasCliente']);
    });

    // ---- rutas de una orden del cliente (se valida que la orden sea suya) ----
    Route::middleware('propio:cliente')->group(function () {
        Route::post('cliente/orden/informacion/estado', [ApiOrdenesController::class, 'informacionOrdenIndividual']);
        Route::post('cliente/listado/productos/ordenes', [ApiOrdenesController::class, 'listadoProductosOrdenes']);
        Route::post('cliente/listado/productos/ordenes-individual', [ApiOrdenesController::class, 'infoProductoOrdenadoIndividual']);
        Route::post('cliente/proceso/cancelar/orden', [ApiOrdenesController::class, 'cancelarOrdenPorCliente']);
        Route::post('cliente/ocultar/mi/orden', [ApiOrdenesController::class, 'ocultameOrdenCliente']);


        // cerrar sesion en este dispositivo
        Route::post('cliente/cerrar-sesion', [ApiClienteController::class, 'cerrarSesion']);
    });

    // notificacion al restaurante despues de enviar la orden ("id" es la orden del cliente)
    Route::post('cliente/proceso/orden/notificacion', [ApiProcesarController::class, 'notificacionOrdenParaRestaurante'])
        ->middleware('propio:cliente,orden=id');


    // ============================== APP RESTAURANTE (token) ==============================

    // ---- rutas donde "id" es el usuario del restaurante ----
    Route::middleware('propio:restaurante,id')->group(function () {
        Route::post('restaurante/nuevas/ordenes', [ApiOrdenesRestauranteController::class, 'nuevasOrdenes']);
        Route::post('restaurante/completadashoy/ordenes', [ApiOrdenesRestauranteController::class, 'completadasHoyOrdenes']);
        Route::post('restaurante/canceladashoy/ordenes', [ApiOrdenesRestauranteController::class, 'canceladasHoyOrdenes']);
        Route::post('restaurante/listado/categorias', [ApiOrdenesRestauranteController::class, 'listadoDeCategorias']);
        Route::post('restaurante/historial/ordenes', [ApiOrdenesRestauranteController::class, 'historialOrdenesRestaurantes']);
        Route::post('restaurante/notificacion/modo/prueba', [ApiOrdenesRestauranteController::class, 'enviarNotificacionPropia']);
    });

    // ---- ordenes, categorias y productos del restaurante (se valida que sean de su sucursal) ----
    Route::middleware('propio:restaurante')->group(function () {
        Route::post('restaurante/listado/producto/orden', [ApiOrdenesRestauranteController::class, 'listadoProductosOrden']);
        Route::post('restaurante/listado/productos/ordenes-individual', [ApiOrdenesRestauranteController::class, 'infoProductoOrdenadoIndividual']);
        Route::post('restaurante/proceso/orden/iniciar-orden', [ApiOrdenesRestauranteController::class, 'iniciarOrdenPorRestaurante']);
        Route::post('restaurante/cancelar/orden', [ApiOrdenesRestauranteController::class, 'cancelarOrden']);
        Route::post('restaurante/actualizar/estado/categorias', [ApiOrdenesRestauranteController::class, 'actualizarEstadoCategoria']);
        Route::post('restaurante/categoria/listado/productos', [ApiOrdenesRestauranteController::class, 'listadoDeProductosPorCategoria']);
        Route::post('restaurante/actualizar/estado/producto', [ApiOrdenesRestauranteController::class, 'actualizarEstadoProducto']);

        // cerrar sesion en este dispositivo
        Route::post('restaurante/cerrar-sesion', [ApiClienteController::class, 'cerrarSesion']);
    });
});
