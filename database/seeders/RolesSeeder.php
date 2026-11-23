<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // ADMINISTRADOR: ve todo el panel, incluidos roles y permisos
        $roleAdmin = Role::create(['name' => 'Admin']);

        // REALIZA TODAS LAS FUNCIONES MENOS LOS ROLES Y PERMISOS
        $roleEditor = Role::create(['name' => 'Editor']);


        // Roles y Permisos
        Permission::create(['name' => 'sidebar.roles.y.permisos', 'description' => 'Vista para permisos'])->syncRoles($roleAdmin);


        Permission::create(['name' => 'sidebar.zonas', 'description' => 'Vista para creacion de zonas'])->syncRoles([$roleAdmin, $roleEditor]);

        Permission::create(['name' => 'sidebar.ordenes', 'description' => 'Vista para ver ordenes'])->syncRoles([$roleAdmin, $roleEditor]);

        Permission::create(['name' => 'sidebar.usuarios', 'description' => 'Vista para ver usuarios de todos'])->syncRoles([$roleAdmin, $roleEditor]);

        Permission::create(['name' => 'sidebar.notificaciones', 'description' => 'vista para notificaciones'])->syncRoles([$roleAdmin, $roleEditor]);

        Permission::create(['name' => 'sidebar.reportes', 'description' => 'vista para reportes'])->syncRoles([$roleAdmin, $roleEditor]);













    }
}
