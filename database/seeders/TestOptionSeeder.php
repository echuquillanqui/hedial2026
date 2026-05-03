<?php

namespace Database\Seeders;

use App\Models\Test;
use App\Models\TestOption;
use Illuminate\Database\Seeder;

class TestOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vih = Test::where('name', 'VIH')->firstOrFail();

        TestOption::updateOrCreate(
            ['test_id' => $vih->id, 'value' => 'POS'],
            ['label' => 'Positivo']
        );

        TestOption::updateOrCreate(
            ['test_id' => $vih->id, 'value' => 'NEG'],
            ['label' => 'Negativo']
        );
    }
}
