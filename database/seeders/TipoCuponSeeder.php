<?php

namespace Database\Seeders;

use App\Models\TipoCupon;
use Illuminate\Database\Seeder;

class TipoCuponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // solo existe el cupon de producto gratis
        TipoCupon::create([
            'nombre' => 'Producto Gratis',
        ]);
    }
}
