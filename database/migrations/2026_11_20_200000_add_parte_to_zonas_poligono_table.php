<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// una zona puede tener varias areas (poligonos separados); los puntos que ya existen quedan en el area 1
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zonas_poligono', function (Blueprint $table) {
            $table->unsignedSmallInteger('parte')->default(1)->after('id_zonas');
        });
    }

    public function down(): void
    {
        Schema::table('zonas_poligono', function (Blueprint $table) {
            $table->dropColumn('parte');
        });
    }
};
