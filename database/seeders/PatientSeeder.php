<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Sede;
use Illuminate\Database\Seeder;
use RuntimeException;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sedeIds = Sede::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id');

        if ($sedeIds->isEmpty()) {
            throw new RuntimeException('Debe ejecutar SedeSeeder antes de PatientSeeder.');
        }

        Patient::factory()
            ->count(50)
            ->sequence(fn ($sequence) => [
                'sede_id' => $sedeIds[$sequence->index % $sedeIds->count()],
            ])
            ->create();
    }
}
