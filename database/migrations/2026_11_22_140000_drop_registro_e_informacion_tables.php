<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Tablas de depuracion del cron (log por minuto activado con informacion.crono_activo).
// Nada las leia; se eliminan junto con sus modelos.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('registro');
        Schema::dropIfExists('informacion');
    }

    public function down(): void
    {
        // sin vuelta atras: eran tablas sin uso
    }
};
