<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// Cliente de la app. Se autentica en la API con un token de Sanctum (habilidad "cliente").
class Clientes extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'clientes';
    public $timestamps = false;

    protected $hidden = ['password', 'token_recuperacion'];
}
