<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cierres programados por fecha: un restaurante (o todos) no recibe ordenes
     * entre fecha_inicio y fecha_fin. Si "anual" = 1 se repite cada año (solo cuenta mes y dia).
     */
    public function up(): void
    {
        Schema::create('cierres_programados', function (Blueprint $table) {
            $table->id();
            // null = todos los restaurantes
            $table->bigInteger('id_servicios')->unsigned()->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('anual')->default(0);
            $table->string('mensaje', 100);
            $table->dateTime('fecha_registro');

            $table->foreign('id_servicios')->references('id')->on('servicios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_programados');
    }
};
