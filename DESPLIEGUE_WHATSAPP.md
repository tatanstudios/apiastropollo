# Subir el aviso de WhatsApp a producción

Servidor: `https://astropollo.tatancloud.com`
Probado en local el 25/11/2026: el token, el número +503 7027 8741 y la plantilla `nueva_orden` (es, Utilidad, APPROVED) funcionan. El envío de prueba a +503 75825072 respondió `enviado`.

> Hazlo en un horario con pocas órdenes. Los pasos 1 a 7 se hacen en el servidor.

---

## 0. Antes de empezar (en el servidor)

```bash
cd /ruta/del/proyecto
php artisan migrate:status        # anota qué migraciones salen "Pending"
php artisan down                  # opcional: modo mantenimiento mientras subes
```

## 1. Respaldo de la base de datos (obligatorio)

```bash
mysqldump -u USUARIO -p NOMBRE_BD > respaldo_antes_whatsapp_2026-11-25.sql
```

Una de las migraciones **borra tablas** (ver paso 4). Sin este respaldo no hay vuelta atrás.

## 2. Subir el código

Sube la carpeta `apiastropollo` completa **excepto**:

- `.env` (el del servidor es otro: **no lo sobrescribas**)
- `vendor/`
- `storage/` (logs e imágenes del servidor)
- `node_modules/` y `bootstrap/cache/*.php`

Archivos de WhatsApp, para revisar que se subieron:

| Archivo | Qué hace |
|---|---|
| `app/Services/WhatsAppService.php` | Envío a la API de Meta |
| `app/Services/WhatsAppBolson.php` | Saldo (bolsón) de mensajes |
| `app/Jobs/EnviarWhatsAppNuevaOrden.php` | Aviso al guardar una orden |
| `app/Jobs/NotificarNuevaOrden.php` | Despacha el push y el WhatsApp |
| `app/Http/Controllers/Api/Procesar/ApiProcesarController.php` | Llama a `NotificarNuevaOrden::despachar()` |
| `app/Http/Controllers/Backend/WhatsApp/WhatsAppController.php` | Panel → WhatsApp (saldo e historial) |
| `app/Http/Controllers/Backend/WhatsApp/BorrarVentasController.php` | **Temporal**: borrar órdenes de prueba |
| `app/Http/Controllers/Api/Ordenes/ApiWhatsAppRestauranteController.php` | API para la app del restaurante |
| `app/Http/Controllers/Backend/Configuracion/ServiciosController.php` | Campo WhatsApp del servicio |
| `resources/views/backend/admin/whatsapp/*` | Vistas del panel WhatsApp |
| `resources/views/backend/admin/configuracion/servicios/*` | Formulario de servicios |
| `config/services.php` | Sección `whatsapp` |
| `config/adminlte.php` | Menú → WhatsApp |
| `routes/web.php`, `routes/api.php` | Rutas nuevas |
| `database/migrations/2026_11_2*` | Migraciones nuevas |

Los timestamps muestran que se tocaron muchos más archivos desde el 22/11. Si no sabes con certeza qué versión tiene el servidor, sube todo el proyecto con las exclusiones de arriba.

## 3. Dependencias

```bash
composer install --no-dev --optimize-autoloader
```

## 4. Migraciones

```bash
php artisan migrate --force
```

Revisa las que estaban pendientes en el paso 0:

- `2026_11_22_130000_add_whatsapp_to_servicios_table`: agrega la columna `servicios.whatsapp`.
- `2026_11_24_100000_create_whatsapp_bolson_tables`: crea `whatsapp_bolson` con saldo **0** y `whatsapp_movimientos`.
- `2026_11_22_140000_drop_registro_e_informacion_tables`: **⚠ BORRA** las tablas `registro` e `informacion`. Eran tablas de depuración del cron que nada leía. Por eso el respaldo del paso 1.
- Las demás del 20 al 22/11 (modificadores, cierres programados, etc.), si aparecen como pendientes.

## 5. Variables en el `.env` del servidor

Agrégalas al final. El token es el mismo que usas en local; cópialo directo de un archivo al otro, no por chat ni por correo.

```
WHATSAPP_TOKEN=el_token_permanente
WHATSAPP_PHONE_NUMBER_ID=1505390002652203
WHATSAPP_API_VERSION=v23.0
WHATSAPP_PLANTILLA_ORDEN=nueva_orden
WHATSAPP_IDIOMA=es
WHATSAPP_CODIGO_PAIS=503
```

## 6. Limpiar y regenerar cachés

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up                    # si usaste "down" en el paso 0
```

Si usas una cola real (`QUEUE_CONNECTION` distinto de `sync`), reinicia los workers:

```bash
php artisan queue:restart
```

## 7. Cron (si no estaba)

Revisa que exista esta línea en `crontab -e`. Es para los recordatorios de cada minuto:

```
* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Configurar desde el panel (navegador)

1. **Roles y Permisos → WhatsApp**: **agrega mensajes al bolsón**, por ejemplo 100.
   **Sin esto no se envía nada**: el saldo arranca en 0 y cada orden registra `sin_saldo` en el log.
2. **Restaurantes / Servicios**: en el servicio que vas a probar, llena el campo **WhatsApp** con `75825072`.

## 9. Probar

1. Haz una orden desde la app cliente para ese servicio.
2. Te debe llegar *"El pedido número #… ha sido recib…"* desde el +503 7027 8741.
3. En **Roles y Permisos → WhatsApp** el saldo baja 1 y la orden aparece en el historial.

### Si no llega, mira `storage/logs/laravel.log` en el servidor

| Mensaje en el log | Causa |
|---|---|
| `WhatsApp orden #X: sin_saldo` | El bolsón está en 0 (paso 8.1) |
| `WhatsApp orden #X: numero_invalido` | El campo WhatsApp del servicio está mal escrito |
| `WhatsApp orden #X: rechazado` + `envio con errores` | Meta lo rechazó: revisa el token o la plantilla en el `.env` |
| Nada de WhatsApp | Falta el `.env` (paso 5), `config:cache` (paso 6) o el campo WhatsApp del servicio |

---

## Después

- **Quitar la ruta temporal** `POST /admin/ventas/borrar` (`routes/web.php` línea 71-72) y su controlador, una vez que borres las órdenes de prueba. Permite borrar todas las ventas.
- Opcional: activar la **alerta de gasto** en Meta Business → Facturación.
