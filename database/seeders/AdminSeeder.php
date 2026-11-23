<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Administrador::create([
            'nombre' => 'Administrador',
            'usuario' => 'Admin',
            'password' => bcrypt('prueba'),
            'activo' => '1',
            'correo' => 'tatangamess@gmail.com',
            'token_correo' => null,
            'token_fecha' => null
        ])->assignRole('Admin');

        Administrador::create([
            'nombre' => 'Roles',
            'usuario' => 'roles',
            'password' => bcrypt('1234'),
            'activo' => '1',
            'correo' => '',
            'token_correo' => null,
            'token_fecha' => null
        ])->assignRole('Editor');






    }
}
