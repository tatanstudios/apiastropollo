<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Administrador extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles;

    protected $table = 'administrador';
    public $timestamps = false;

    // AdminLTE muestra Auth::user()->name en la barra superior; la tabla usa "nombre"
    public function getNameAttribute(): ?string
    {
        return $this->nombre;
    }

    // texto debajo del nombre en el menu de usuario de AdminLTE: el rol
    public function adminlte_desc(): string
    {
        return $this->getRoleNames()->implode(', ');
    }

    // boton "Perfil" del menu de usuario de AdminLTE
    public function adminlte_profile_url(): string
    {
        return 'admin/editar-perfil/index';
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    /*protected $fillable = [
        'name',
        'email',
        'password',
    ];*/

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    /* protected $hidden = [
         'password',
         'remember_token',
     ];*/

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    /*protected $casts = [
        'email_verified_at' => 'datetime',
    ];*/



}
