<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MODIFICADORES DE PRODUCTO (sin inventario)
 *
 * modificador_grupo  -> bloque de opciones de un producto (ej. "Elegir sabor")
 * modificador_opcion -> cada opción del bloque (ej. "Naranja", "Mandarina")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modificador_grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_productos')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->boolean('requerido')->default(false);
            $table->unsignedInteger('minimo')->default(0); // unidades mínimas a elegir en el bloque
            $table->unsignedInteger('maximo')->default(1); // unidades máximas a elegir en el bloque
            $table->integer('posicion')->default(0);
        });

        Schema::create('modificador_opcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_grupo')->constrained('modificador_grupo')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->decimal('precio', 10, 2)->default(0); // precio extra por unidad
            $table->unsignedInteger('maximo')->default(1); // veces que se puede elegir esta opción
            $table->boolean('obligatorio')->default(false); // siempre va incluida
            $table->boolean('una_sola')->default(false); // si se elige, no se combina con otras del bloque
            $table->boolean('activo')->default(true);
            $table->integer('posicion')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modificador_opcion');
        Schema::dropIfExists('modificador_grupo');
    }
};
