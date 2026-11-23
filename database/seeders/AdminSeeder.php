<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Administrador principal
        $admin = Administrador::firstOrCreate(
            ['usuario' => 'Admin'],
            [
                'nombre' => 'Administrador',
                'password' => bcrypt('prueba'),
                'activo' => '1',
                'correo' => 'tatangamess@gmail.com',
                'token_correo' => null,
                'token_fecha' => null
            ]
        );

        $admin->syncRoles(['Admin']);

        // Usuario de operaciones
        $roles = Administrador::firstOrCreate(
            ['usuario' => 'roles'],
            [
                'nombre' => 'Roles',
                'password' => bcrypt('1234'),
                'activo' => '1',
                'correo' => '',
                'token_correo' => null,
                'token_fecha' => null
            ]
        );

        $roles->syncRoles(['RolesControl']);
    }
}
