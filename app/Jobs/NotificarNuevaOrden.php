<?php

namespace App\Jobs;

use App\Models\Ordenes;
use App\Models\UsuariosServicios;
use App\Services\OneSignalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa al restaurante que entro una orden nueva.
 *
 * Se despacha desde el servidor justo despues de guardar la orden (ApiProcesarController),
 * asi la app cliente ya no tiene que hacer una segunda peticion para pedir el aviso.
 *
 * - Con QUEUE_CONNECTION=sync corre despues de enviar la respuesta al cliente (no lo hace esperar).
 * - Con una cola real (database/redis + worker) reintenta si OneSignal falla.
 * - Si el aviso falla, la orden NO se ve afectada: ya esta guardada.
 *
 * El WhatsApp va en su propio Job (EnviarWhatsAppNuevaOrden), despachado junto a este.
 */
class NotificarNuevaOrden implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $idOrden)
    {
    }

    /**
     * Despacha los avisos de orden nueva (push + WhatsApp) sin hacer esperar al cliente,
     * sea cual sea la cola configurada.
     */
    public static function despachar(int $idOrden): void
    {
        if (config('queue.default') === 'sync') {
            static::dispatchAfterResponse($idOrden);
            EnviarWhatsAppNuevaOrden::dispatchAfterResponse($idOrden);
        } else {
            static::dispatch($idOrden);
            EnviarWhatsAppNuevaOrden::dispatch($idOrden);
        }
    }

    public function handle(): void
    {
        $orden = Ordenes::find($this->idOrden);
        if (!$orden) {
            return;
        }

        $idsUsuarios = UsuariosServicios::idsActivos($orden->id_servicio);
        if (empty($idsUsuarios)) {
            return;
        }

        $resultado = OneSignalService::enviarUsuariosRestaurante(
            $idsUsuarios,
            'Nueva Orden #' . $orden->id,
            'Revisar Pedido',
            config('googleapi.CanalRestaurante')
        );

        // en una cola real, si OneSignal fallo se vuelve a intentar (30s, 60s)
        if ($resultado === 'error' && $this->enColaReal() && $this->attempts() < $this->tries) {
            $this->release(30 * $this->attempts());
        }
    }

    private function enColaReal(): bool
    {
        return $this->job !== null && $this->job->getConnectionName() !== 'sync';
    }
}
