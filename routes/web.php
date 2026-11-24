<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\Login\LoginController;
use App\Http\Controllers\Controles\ControlController;
use App\Http\Controllers\Backend\Roles\RolesController;
use App\Http\Controllers\Backend\Perfil\PerfilController;
use App\Http\Controllers\Backend\Roles\PermisoController;
use App\Http\Controllers\Backend\Configuracion\ZonasController;
use App\Http\Controllers\Backend\Configuracion\ServiciosController;
use App\Http\Controllers\Backend\Configuracion\CategoriasController;
use App\Http\Controllers\Backend\Configuracion\ProductosController;
use App\Http\Controllers\Backend\Configuracion\SliderController;
use App\Http\Controllers\Backend\Configuracion\CuponesController;
use App\Http\Controllers\Backend\Clientes\ClientesController;
use App\Http\Controllers\Backend\Ordenes\OrdenesController;
use App\Http\Controllers\Backend\Configuracion\NotificacionesController;
use App\Http\Controllers\Backend\Configuracion\ModificadoresController;
use App\Http\Controllers\Backend\Reportes\ReportesController;


Route::get('/', [LoginController::class,'index'])->name('login');

Route::post('admin/login', [LoginController::class, 'login']);
Route::post('admin/logout', [LoginController::class, 'logout'])->name('admin.logout');


Route::get('/formulario/eliminacioncuenta', [LoginController::class,'vistaEliminacionCuenta']);
Route::view('/terminos', 'cuenta.terminos');



// --- CONTROL WEB ---

Route::get('/panel', [ControlController::class,'indexRedireccionamiento'])->name('admin.panel');

// --- ROLES ---

Route::get('/admin/roles/index', [RolesController::class,'index'])->name('admin.roles.index');
Route::get('/admin/roles/tabla', [RolesController::class,'tablaRoles']);
Route::get('/admin/roles/lista/permisos/{id}', [RolesController::class,'vistaPermisos']);
Route::get('/admin/roles/permisos/tabla/{id}', [RolesController::class,'tablaRolesPermisos']);
Route::post('/admin/roles/permiso/borrar', [RolesController::class, 'borrarPermiso']);
Route::post('/admin/roles/permiso/agregar', [RolesController::class, 'agregarPermiso']);
Route::get('/admin/roles/permisos/lista', [RolesController::class,'listaTodosPermisos']);
Route::get('/admin/roles/permisos-todos/tabla', [RolesController::class,'tablaTodosPermisos']);
Route::post('/admin/roles/borrar-global', [RolesController::class, 'borrarRolGlobal']);
Route::post('/admin/roles/editar', [RolesController::class, 'editarRol']);
Route::get('/admin/roles/permisos/disponibles/{id}', [RolesController::class, 'permisosDisponibles']);

// --- PERMISOS A USUARIOS ---

Route::get('/admin/permisos/index', [PermisoController::class,'index'])->name('admin.permisos.index');
Route::get('/admin/permisos/tabla', [PermisoController::class,'tablaUsuarios']);
Route::post('/admin/permisos/nuevo-usuario', [PermisoController::class, 'nuevoUsuario']);
Route::post('/admin/permisos/info-usuario', [PermisoController::class, 'infoUsuario']);
Route::post('/admin/permisos/editar-usuario', [PermisoController::class, 'editarUsuario']);
Route::post('/admin/permisos/nuevo-rol', [PermisoController::class, 'nuevoRol']);
Route::post('/admin/permisos/extra-nuevo', [PermisoController::class, 'nuevoPermisoExtra']);
Route::post('/admin/permisos/extra-borrar', [PermisoController::class, 'borrarPermisoGlobal']);


// --- PERFIL ---
Route::get('/admin/editar-perfil/index', [PerfilController::class,'indexEditarPerfil'])->name('admin.perfil');
Route::post('/admin/editar-perfil/actualizar', [PerfilController::class, 'editarUsuario']);
Route::post('/admin/editar-perfil/correo', [PerfilController::class, 'editarCorreo']);


// --- VISTA PARA INGRESAR CORREO ---
Route::get('/admin/ingreso/de/correo', [LoginController::class,'indexIngresoDeCorreo']);
Route::post('/admin/enviar/correo/password', [LoginController::class, 'enviarCorreoAdministrador']);


// VISTA AQUI SE INGRESA LA NUEVA CONTRASEÑA PORQUE EL LINK ES VALIDO
Route::get('/admin/resetear/contrasena/administrador/{token}', [LoginController::class,'indexIngresoNuevaPasswordLink']);

// VISTA SIN TOKEN PARA REDIRECCION
Route::get('/admin/resetear/contrasena/administrador', [LoginController::class,'indexIngresoNuevaPasswordLinkRedireccion']);


// ACTUALIZACION DE CONTRASENA

Route::post('/admin/administrador/actualizacion/password', [LoginController::class, 'actualizarPasswordAdministrador']);






// --- SIN PERMISOS VISTA 403 ---
Route::get('sin-permisos', [ControlController::class,'indexSinPermiso'])->name('no.permisos.index');


// --- ZONAS ---
Route::get('/admin/zonas/mapa/zona', [ZonasController::class,'index'])->name('index.vistas.zonas');
Route::get('/admin/restaurantes/{id}/zonas', [ZonasController::class,'indexRestaurante'])->name('index.zonas.restaurante');
Route::get('/admin/zonas/tablas/zona', [ZonasController::class,'tablaZonas']);
Route::post('/admin/zonas/registro/nueva', [ZonasController::class,'nuevaZona']);
Route::post('/admin/zonas/informacion-zona', [ZonasController::class,'informacionZona']);
Route::post('/admin/zonas/editar-zona', [ZonasController::class,'editarZona']);
Route::get('/admin/zonas/ver-mapa/{id}', [ZonasController::class,'verMapa']);
Route::post('/admin/zonas/estado', [ZonasController::class,'cambiarEstado']);
Route::post('/admin/zonas/borrar', [ZonasController::class,'borrarZona']);
Route::post('/admin/zonas/cerrar-varias', [ZonasController::class,'cerrarVarias']);
Route::get('/admin/zonas/cierres', [ZonasController::class,'listaCierres']);
Route::post('/admin/zonas/cierres/nuevo', [ZonasController::class,'nuevoCierre']);
Route::post('/admin/zonas/cierres/borrar', [ZonasController::class,'borrarCierre']);
Route::get('/admin/zonas/mapa-general', [ZonasController::class,'mapaGeneral']);


// --- POLIGONO ---
Route::get('/admin/zonas/poligono/{id}', [ZonasController::class,'indexPoligono']);
Route::post('/admin/zonas/poligono/listado-nuevo', [ZonasController::class,'nuevoPoligono']);
Route::post('/admin/zonas/poligono/borrar', [ZonasController::class,'borrarPoligonos']);

// --- SERVICIOS ---
Route::get('/admin/servicios/listado', [ServiciosController::class,'index'])->name('index.servicios.listado');
Route::get('/admin/servicios/listado/tabla', [ServiciosController::class,'serviciosTabla']);
Route::post('/admin/servicios/registrar/nuevo', [ServiciosController::class,'registrarServicio']);
Route::post('/admin/servicios/informacion', [ServiciosController::class,'informacionServicio']);
Route::post('/admin/servicios/editar-servicio', [ServiciosController::class,'editarServicios']);




// --- CATEGORIAS de servicio---
Route::get('/admin/categorias/listado/{id}', [CategoriasController::class,'index']);
Route::get('/admin/categorias/listado/tabla/{id}', [CategoriasController::class,'categoriasTabla']);
Route::post('/admin/categorias/nuevo', [CategoriasController::class,'nuevaCategorias']);
Route::post('/admin/categorias/informacion', [CategoriasController::class,'informacionCategorias']);
Route::post('/admin/categorias/editar', [CategoriasController::class,'editarCategorias']);
Route::post('/admin/categorias/ordenar', [CategoriasController::class,'ordenarCategorias']);




// --- SUB CATEGORIAS de servicio---
Route::get('/admin/sub/categorias/listado/{id}', [CategoriasController::class,'indexSubCategorias']);
Route::get('/admin/sub/categorias/listado/tabla/{id}', [CategoriasController::class,'subCategoriasTabla']);
Route::post('/admin/sub/categorias/nuevo', [CategoriasController::class,'nuevaSubCategorias']);
Route::post('/admin/sub/categorias/informacion', [CategoriasController::class,'informacionSubCategorias']);
Route::post('/admin/sub/categorias/editar', [CategoriasController::class,'editarSubCategorias']);
Route::post('/admin/sub/categorias/ordenar', [CategoriasController::class,'ordenarSubCategorias']);





/// --- ZONAS SERVICIO ---
// el restaurante de cada zona ahora se asigna desde Zonas (la tabla las agrupa por restaurante)
Route::redirect('/admin/zonasservicio/listado', '/admin/servicios/listado')->name('index.zonas.servicio.listado');


// --- PRODUCTOS ---
Route::get('/admin/productos/listado/{id}', [ProductosController::class,'index']);
Route::get('/admin/productos/listado/tabla/{id}', [ProductosController::class,'productosTabla']);
Route::post('/admin/productos/nuevo', [ProductosController::class,'nuevoProducto']);
Route::post('/admin/productos/informacion', [ProductosController::class,'informacionProductos']);
Route::post('/admin/productos/editar', [ProductosController::class,'editarProductos']);
Route::post('/admin/productos/ordenar', [ProductosController::class,'ordenarProductos']);

// --- MODIFICADORES DE PRODUCTO ---
Route::get('/admin/productos/modificadores/{id}', [ModificadoresController::class,'index']);
Route::get('/admin/productos/modificadores/informacion/{id}', [ModificadoresController::class,'informacion']);
Route::post('/admin/productos/modificadores/guardar', [ModificadoresController::class,'guardar']);

// --- BANNER de restaurante ---
Route::get('/admin/slider/listado/{id}', [SliderController::class,'index']);
Route::get('/admin/slider/listado/tabla/{id}', [SliderController::class,'sliderTabla']);
Route::post('/admin/slider/nuevo', [SliderController::class,'nuevaSlider']);
Route::post('/admin/slider/borrar', [SliderController::class,'borrarSliders']);
Route::post('/admin/slider/informacion', [SliderController::class,'informacionSlider']);
Route::post('/admin/slider/editar', [SliderController::class,'editarSlider']);
Route::post('/admin/slider/ordenar', [SliderController::class,'ordenarSliders']);

// --- CUPONES (producto gratis; se entra desde Restaurantes) ---
Route::get('/admin/cupones/listado', [CuponesController::class,'index'])->name('index.cupones.listado');
Route::get('/admin/cupones/listado/tabla', [CuponesController::class,'cuponesTabla']);
Route::post('/admin/cupones/informacion', [CuponesController::class,'informacionCupon']);
Route::post('/admin/cupones/guardar', [CuponesController::class,'guardarCupon']);
Route::post('/admin/cupones/activo', [CuponesController::class,'cambiarActivo']);
// la pagina vieja de cupones por restaurante ahora esta dentro de Cupones
Route::redirect('/admin/cupones/servicio/progratis/{id}', '/admin/cupones/listado');





// --- PRODUCTOS POPULARES DEL SERVICIO ----
Route::get('/admin/productos/servicio/principales/{id}', [CategoriasController::class,'indexServiciosProductosPrincipales']);
Route::get('/admin/productos/servicio/principales/tabla/{id}', [CategoriasController::class,'tablaServiciosProductosPrincipales']);
Route::post('/admin/productos/servicio/principales/nuevo', [CategoriasController::class,'nuevoProductosPrincipales']);
Route::post('/admin/productos/servicio/principales/borrar', [CategoriasController::class,'borrarProductosPrincipales']);
Route::post('/admin/productos/servicio/principales/ordenar', [CategoriasController::class,'ordenarProductosPopulares']);





// --- LISTA DE CLIENTES REGISTRADOS ----
Route::get('/admin/clientes/listado', [ClientesController::class,'index'])->name('index.clientes.listado');
Route::get('/admin/clientes/listado/tabla', [ClientesController::class,'tablaClientes']);
Route::post('/admin/clientes/informacion/editar', [ClientesController::class,'editarCliente']);


// ***********  ORDENES (una sola pagina con pestañas) **************
Route::get('/admin/ordenes', [OrdenesController::class,'index'])->name('index.ordenes');
Route::get('/admin/ordenes/tabla', [OrdenesController::class,'tabla']);
Route::post('/admin/ordenes/detalle', [OrdenesController::class,'detalle']);
Route::post('/admin/ordenes/borrar', [OrdenesController::class,'borrar']);

// direcciones antiguas -> la pagina nueva en su pestaña
Route::redirect('/admin/ordenes/pendientes/listado', '/admin/ordenes?ver=pendientes');
Route::redirect('/admin/ordenes/iniciadashoy/listado', '/admin/ordenes?ver=iniciadas');
Route::redirect('/admin/ordenes/canceladashoy/listado', '/admin/ordenes?ver=canceladas');
Route::redirect('/admin/ordenes/todas/listado', '/admin/ordenes?ver=todas');







// --- USUARIOS PARA MANEJAR EL RESTAURANTE ---

Route::get('/admin/restaurantes/usuario', [ServiciosController::class,'indexUsuariosRestaurantes'])->name('index.usuarios.restaurantes');
Route::get('/admin/restaurantes/usuario/tabla', [ServiciosController::class,'tablaUsuariosRestaurantes']);
Route::post('/admin/restaurantes/usuario/nuevo', [ServiciosController::class,'registrarUsuarioRestaurante']);
Route::post('/admin/restaurantes/usuario/bloquear', [ServiciosController::class,'bloquearUsuarioRestaurante']);
Route::post('/admin/restaurantes/usuario/informacion', [ServiciosController::class,'informacionUsuarioRestaurante']);
Route::post('/admin/restaurantes/usuario/actualizar', [ServiciosController::class,'actualizarUsuarioRestaurante']);









// --- NOTIFICACIONES ---
Route::get('/admin/notificaciones/porrestaurantes', [NotificacionesController::class,'indexNotificacionPorRestaurante'])->name('index.notificaciones.restaurantes');
Route::post('/admin/notificaciones/enviar/porservicio', [NotificacionesController::class,'enviarNotificacionPorServicio']);


// VISTA BUSCAR UNA DIRECCION Y ESE CLIENTE ENVIARLA NOTIFICACION
Route::get('/admin/notificaciones/vista/porcliente', [NotificacionesController::class,'indexListaDireccioneNotificacion'])->name('index.notificaciones.porcliente');
Route::get('/admin/notificaciones/vista/porcliente/tabla', [NotificacionesController::class,'tablaClientesDireccionesNotificacion']);
Route::post('/admin/notificacion/cliente/informacion', [NotificacionesController::class,'informacionCliente']);
Route::post('/admin/notificaciones/enviar/porcliente', [NotificacionesController::class,'enviarNotiPorCliente']);




// --- REPORTES (una sola pagina; el reporte de clientes por restaurante quedo dentro) ---
Route::get('/admin/reportes', [ReportesController::class,'index'])->name('index.reportes')->middleware('can:sidebar.reportes');
Route::get('/admin/reportes/datos', [ReportesController::class,'datos'])->middleware('can:sidebar.reportes');
Route::redirect('/admin/clientes/reporte/primera/ubicacion', '/admin/reportes')->name('index.clientes.reporte.primera.direccion');






































