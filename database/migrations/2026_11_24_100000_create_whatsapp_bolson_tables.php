<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bolson de mensajes de WhatsApp: un saldo global que se descuenta con cada aviso
     * de orden nueva enviado. Con saldo 0 no se envia nada.
     */
    public function up(): void
    {
        // una sola fila (id = 1)
        Schema::create('whatsapp_bolson', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('disponibles')->default(0);
            $table->unsignedBigInteger('enviados')->default(0);
        });

        DB::table('whatsapp_bolson')->insert(['id' => 1, 'disponibles' => 0, 'enviados' => 0]);

        // historial: lo que agrega/quita un administrador y cada mensaje enviado
        Schema::create('whatsapp_movimientos', function (Blueprint $table) {
            $table->id();
            // agregar | quitar | envio
            $table->string('tipo', 10);
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('saldo_anterior');
            $table->unsignedInteger('saldo_nuevo');
            $table->bigInteger('id_administrador')->unsigned()->nullable();
            $table->bigInteger('id_servicio')->unsigned()->nullable();
            $table->bigInteger('id_orden')->unsigned()->nullable();
            $table->string('nota', 150)->nullable();
            $table->dateTime('fecha');

            $table->index(['tipo', 'fecha']);
            $table->index('id_servicio');
            $table->foreign('id_administrador')->references('id')->on('administrador')->nullOnDelete();
            $table->foreign('id_servicio')->references('id')->on('servicios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_movimientos');
        Schema::dropIfExists('whatsapp_bolson');
    }
};
