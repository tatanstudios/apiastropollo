<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorio de ordenes sin responder (cada minuto, maximo 3 por orden).
// En el servidor se activa con UN cron:
//   * * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1
// withoutOverlapping: si una ejecucion se tarda (OneSignal lento) no se encima la siguiente.
Schedule::command('ordenes:verificar')->everyMinute()->withoutOverlapping(5);
