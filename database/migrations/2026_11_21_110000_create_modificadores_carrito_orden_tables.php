<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opciones de modificadores elegidas por el cliente.
 *
 * carrito_extra_opcion       -> opciones elegidas para una fila del carrito
 * ordenes_descripcion_opcion -> copia de las opciones al enviar la orden
 *                               (no cambia si luego se editan los modificadores del producto)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrito_extra_opcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carrito_extra')->constrained('carrito_extra')->cascadeOnDelete();
            $table->foreignId('id_opcion')->constrained('modificador_opcion')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
        });

        Schema::create('ordenes_descripcion_opcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ordenes_descripcion')->constrained('ordenes_descripcion')->cascadeOnDelete();
            $table->unsignedBigInteger('id_opcion')->nullable(); // referencia informativa, sin llave
            $table->string('grupo', 100);
            $table->string('opcion', 100);
            $table->decimal('precio', 10, 2)->default(0); // precio extra por unidad al momento de ordenar
            $table->unsignedInteger('cantidad')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_descripcion_opcion');
        Schema::dropIfExists('carrito_extra_opcion');
    }
};
