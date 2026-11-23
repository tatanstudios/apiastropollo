<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// el telefono de la direccion puede ser de otro pais: con codigo de pais llega hasta 15 digitos (+ el signo "+")
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direccion_cliente', function (Blueprint $table) {
            $table->string('telefono', 20)->change();
        });
        Schema::table('ordenes_direcciones', function (Blueprint $table) {
            $table->string('telefono', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('direccion_cliente', function (Blueprint $table) {
            $table->string('telefono', 10)->change();
        });
        Schema::table('ordenes_direcciones', function (Blueprint $table) {
            $table->string('telefono', 10)->change();
        });
    }
};
