<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Hematología', 'Bioquímica', 'Inmunología'] as $area) {
            Area::updateOrCreate(['name' => $area]);
        }
    }
}
