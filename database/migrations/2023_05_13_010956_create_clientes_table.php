<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('usuario', 20)->unique();
            $table->string('correo', 100)->unique()->nullable();
            $table->string('codigo_correo',10)->nullable();

            // recuperacion de contrasena por correo: vencimiento del codigo, intentos fallidos
            // y token de un solo uso que se entrega al verificar el codigo
            $table->dateTime('codigo_fecha')->nullable();
            $table->unsignedTinyInteger('codigo_intentos')->default(0);
            $table->string('token_recuperacion', 64)->nullable();
            $table->string('password', 255);

            // fecha de registro del cliente
            $table->dateTime('fecha');

            // para bloquear usuario
            $table->boolean('activo');

            // token para enviar notificaciones por one signal
            $table->string('token_fcm', 100)->nullable();


            // para saber de que app se registro
            $table->string('appregistro', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clientes');
    }
}
