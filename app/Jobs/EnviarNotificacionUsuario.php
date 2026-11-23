<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\OneSignalService;

class EnviarNotificacionUsuario implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    // ids de clientes: se envia por external_id (app nueva) y por token_fcm (apps viejas)
    protected $idsClientes;
    protected $titulo;
    protected $descripcion;

    public function __construct(array $idsClientes, $titulo, $descripcion)
    {
        $this->idsClientes = $idsClientes;
        $this->titulo = $titulo;
        $this->descripcion = $descripcion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        OneSignalService::enviarClientes(
            $this->idsClientes,
            $this->titulo,
            $this->descripcion,
            config('googleapi.IdGrupoPasivoCliente')
        );
    }
}
