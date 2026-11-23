<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ordenes extends Model
{
    use HasFactory;
    protected $table = 'ordenes';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'id_servicio',
        'id_zona',
        'nota_orden',
        'total_orden',
        'fecha_orden',
        'fecha_estimada',
        'estado_iniciada',
        'fecha_iniciada',
        'estado_cancelada',
        'fecha_cancelada',
        'nota_cancelada',
        'id_cupones',
        'mensaje_cupon',
        'visible',
        'id_cupones_copia',
        'cancelado_por',
        'conteo_notificacion',
    ];

}
