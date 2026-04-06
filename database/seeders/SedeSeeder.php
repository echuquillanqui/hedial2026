<?php

namespace Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;

class SedeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sedes = [
            ['name' => 'Lima', 'code' => 'LIM', 'is_principal' => false],
            ['name' => 'Huancayo', 'code' => 'HYO', 'is_principal' => true],
            ['name' => 'Pasco', 'code' => 'PSC', 'is_principal' => false],
            ['name' => 'San Ramon', 'code' => 'SRM', 'is_principal' => false],
            ['name' => 'Huancavelica', 'code' => 'HVC', 'is_principal' => false],
        ];

        Sede::query()->update(['is_principal' => false]);

        foreach ($sedes as $data) {
            Sede::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'code' => $data['code'],
                    'is_active' => true,
                    'is_principal' => $data['is_principal'],
                ]
            );
        }
    }
}
