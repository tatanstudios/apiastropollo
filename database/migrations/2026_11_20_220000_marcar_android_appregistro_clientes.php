<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Las apps 2.x y 3.x solo guardaban la version ("2.0.0") sin el sistema.
// Todos esos registros son de Android: se guardan como "android 2.0.0".
// Desde ahora el registro detecta el sistema solo (ApiRegistroController::versionConSistema).
return new class extends Migration
{
    public function up(): void
    {
        DB::table('clientes')
            ->whereNotIn('id', [1, 2, 3])
            ->where('appregistro', 'REGEXP', '^[0-9]')
            ->update(['appregistro' => DB::raw("CONCAT('android ', appregistro)")]);
    }

    public function down(): void
    {
        // no se puede saber cuales venian sin sistema
    }
};
