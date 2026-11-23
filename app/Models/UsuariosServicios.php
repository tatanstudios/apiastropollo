<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// Usuario de la app del restaurante. Se autentica en la API con un token de Sanctum (habilidad "restaurante").
class UsuariosServicios extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'usuarios_servicios';
    public $timestamps = false;

    protected $hidden = ['password'];

    // un restaurante puede tener varios usuarios: las notificaciones van a todos los activos
    public static function idsActivos($idsServicios): array
    {
        $idsServicios = is_array($idsServicios) ? $idsServicios : [$idsServicios];
        return static::whereIn('id_servicios', $idsServicios)
            ->where('bloqueado', 0)
            ->pluck('id')->unique()->values()->all();
    }

    // solo apps viejas (id de suscripcion guardado); la app nueva usa external_id
    public static function tokensActivos($idServicio): array
    {
        return static::where('id_servicios', $idServicio)
            ->where('bloqueado', 0)
            ->whereNotNull('token_fcm')
            ->where('token_fcm', '!=', '')
            ->pluck('token_fcm')->unique()->values()->all();
    }
}
