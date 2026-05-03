<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Test;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hematologia = Area::where('name', 'Hematología')->firstOrFail();
        $bioquimica = Area::where('name', 'Bioquímica')->firstOrFail();
        $inmunologia = Area::where('name', 'Inmunología')->firstOrFail();

        Test::updateOrCreate(
            ['name' => 'Hemoglobina', 'area_id' => $hematologia->id],
            ['unit' => 'g/dL', 'reference_value' => '12-16', 'type' => 'number']
        );

        Test::updateOrCreate(
            ['name' => 'Leucocitos', 'area_id' => $hematologia->id],
            ['unit' => 'x10^3/uL', 'reference_value' => '4.5-11', 'type' => 'number']
        );

        Test::updateOrCreate(
            ['name' => 'Glucosa', 'area_id' => $bioquimica->id],
            ['unit' => 'mg/dL', 'reference_value' => '70-100', 'type' => 'number']
        );

        Test::updateOrCreate(
            ['name' => 'VIH', 'area_id' => $inmunologia->id],
            ['unit' => null, 'reference_value' => null, 'type' => 'select']
        );
    }
}
