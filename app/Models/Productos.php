<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productos extends Model
{
    use HasFactory;
    protected $table = 'productos';
    public $timestamps = false;

    protected $fillable = [
        'posicion'
    ];

    /** Bloques de modificadores (opciones que elige el cliente). */
    public function modificadores()
    {
        return $this->hasMany(ModificadorGrupo::class, 'id_productos');
    }
}
