<?php

namespace App\Console\Commands;

use App\Jobs\EnviarNotificacionRestaurante;
use App\Models\Ordenes;
use App\Models\UsuariosServicios;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Recordatorio al restaurante de ordenes sin responder.
 *
 * Corre cada minuto (routes/console.php). Al guardar la orden ya se envia el primer aviso
 * (Job NotificarNuevaOrden); si pasado 1 minuto el restaurante no la ha iniciado ni cancelado,
 * se vuelve a avisar una vez por minuto, hasta MAX_RECORDATORIOS veces por orden (min 1, 2 y 3).
 *
 * En el servidor basta con un cron que ejecute el scheduler cada minuto:
 *   * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
 */
class VerificarOrdenes extends Command
{
    protected $signature = 'ordenes:verificar';

    protected $description = 'Recuerda al restaurante (push) las ordenes sin responder: 1 vez por minuto, maximo 3 por orden';

    private const MAX_RECORDATORIOS = 3;

    private const ZONA = 'America/El_Salvador';

    public function handle(): int
    {
        $ahora = Carbon::now(self::ZONA);

        // pendientes con al menos 1 minuto de creadas y que aun no llegan al limite.
        // La ventana de 1 hora evita avisar de ordenes viejas olvidadas (ej. al desplegar).
        $pendientes = Ordenes::where('estado_iniciada', 0)
            ->where('estado_cancelada', 0)
            ->where('conteo_notificacion', '<', self::MAX_RECORDATORIOS)
            ->where('fecha_orden', '<=', $ahora->copy()->subMinute())
            ->where('fecha_orden', '>=', $ahora->copy()->subHour())
            ->orderBy('id')
            ->get(['id', 'id_servicio']);

        if ($pendientes->isEmpty()) {
            return self::SUCCESS;
        }

        // un solo aviso por restaurante, aunque tenga varias ordenes pendientes
        foreach ($pendientes->groupBy('id_servicio') as $idServicio => $ordenes) {

            // se cuenta el intento aunque el restaurante no tenga telefonos vinculados,
            // asi nunca pasa del maximo
            Ordenes::whereIn('id', $ordenes->pluck('id'))->increment('conteo_notificacion');

            $idsUsuarios = UsuariosServicios::idsActivos($idServicio);
            if (empty($idsUsuarios)) {
                continue;
            }

            if ($ordenes->count() === 1) {
                $titulo = 'Orden #' . $ordenes->first()->id . ' sin responder';
                $mensaje = 'Revisar App';
            } else {
                $titulo = 'Tienes ' . $ordenes->count() . ' órdenes sin responder';
                $mensaje = 'Órdenes #' . $ordenes->pluck('id')->implode(', #') . '. Revisar App';
            }

            dispatch(new EnviarNotificacionRestaurante($idsUsuarios, $titulo, $mensaje));

            Log::info("Recordatorio a servicio $idServicio: ordenes " . $ordenes->pluck('id')->implode(','));
        }

        return self::SUCCESS;
    }
}
