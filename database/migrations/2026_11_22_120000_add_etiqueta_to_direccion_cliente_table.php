<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etiqueta de la dirección elegida por el cliente (Casa, Trabajo u otra).
 * "nombre" sigue siendo la persona que recibe: es lo que se copia a la orden y ve el restaurante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direccion_cliente', function (Blueprint $table) {
            $table->string('etiqueta', 50)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('direccion_cliente', function (Blueprint $table) {
            $table->dropColumn('etiqueta');
        });
    }
};
