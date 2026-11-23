<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CierresProgramados extends Model
{
    protected $table = 'cierres_programados';
    public $timestamps = false;

    // ¿el cierre aplica en esta fecha? (los anuales comparan solo mes-dia, y pueden cruzar fin de año)
    public function aplicaEn(Carbon $fecha): bool
    {
        if(!$this->anual){
            $dia = $fecha->toDateString();
            return $this->fecha_inicio <= $dia && $dia <= $this->fecha_fin;
        }

        $md = $fecha->format('m-d');
        $inicio = substr($this->fecha_inicio, 5, 5);
        $fin = substr($this->fecha_fin, 5, 5);

        return $inicio <= $fin
            ? ($inicio <= $md && $md <= $fin)
            : ($md >= $inicio || $md <= $fin); // ej.: 31 dic al 2 ene
    }

    // cierre vigente hoy para un restaurante (incluye los que son para todos), o null
    public static function vigenteHoy(?int $idServicio): ?self
    {
        $hoy = Carbon::now('America/El_Salvador');

        return self::where(function ($q) use ($idServicio) {
                $q->whereNull('id_servicios');
                if($idServicio){ $q->orWhere('id_servicios', $idServicio); }
            })
            ->orderBy('id')
            ->get()
            ->first(fn($c) => $c->aplicaEn($hoy));
    }
}
