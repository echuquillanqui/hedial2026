<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Test;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perfilHematologico = Profile::updateOrCreate(['name' => 'Perfil Hematológico']);
        $perfilBioquimico = Profile::updateOrCreate(['name' => 'Perfil Bioquímico']);

        $hemoglobina = Test::where('name', 'Hemoglobina')->firstOrFail();
        $leucocitos = Test::where('name', 'Leucocitos')->firstOrFail();
        $glucosa = Test::where('name', 'Glucosa')->firstOrFail();

        $perfilHematologico->tests()->syncWithoutDetaching([$hemoglobina->id, $leucocitos->id]);
        $perfilBioquimico->tests()->syncWithoutDetaching([$glucosa->id]);
    }
}
