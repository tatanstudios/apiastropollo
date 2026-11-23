<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            // numero de WhatsApp del restaurante para el aviso de orden nueva.
            // Solo digitos con codigo de pais, sin "+" (ej. 50377778888). null = no se envia.
            $table->string('whatsapp', 20)->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('whatsapp');
        });
    }
};
