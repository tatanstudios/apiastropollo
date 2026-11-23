<?php

namespace App\Jobs;

use App\Models\Ordenes;
use App\Models\Servicios;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp al numero del restaurante (servicios.whatsapp): "Nueva orden #X, revisar App".
 * Se envia UNA sola vez por orden, al guardarla (no en los recordatorios de cada minuto).
 *
 * Va separado del push (NotificarNuevaOrden) para que, en una cola real, si uno de los
 * dos falla y se reintenta, el otro no se repita.
 */
class EnviarWhatsAppNuevaOrden implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $idOrden)
    {
    }

    public function handle(): void
    {
        if (!WhatsAppService::configurado()) {
            return;
        }

        $orden = Ordenes::find($this->idOrden);
        if (!$orden) {
            return;
        }

        $numero = Servicios::where('id', $orden->id_servicio)->value('whatsapp');
        if (blank($numero)) {
            return;
        }

        $resultado = WhatsAppService::enviarNuevaOrden($numero, $orden->id);

        if ($resultado !== 'enviado') {
            Log::info("WhatsApp orden #{$orden->id}: $resultado");
        }

        // solo los fallos temporales se reintentan (30s, 60s), y solo con una cola real
        $enColaReal = $this->job !== null && $this->job->getConnectionName() !== 'sync';
        if ($resultado === 'error' && $enColaReal && $this->attempts() < $this->tries) {
            $this->release(30 * $this->attempts());
        }
    }
}
