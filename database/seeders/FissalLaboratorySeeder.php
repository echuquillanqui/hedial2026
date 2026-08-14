<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Test;
use Illuminate\Database\Seeder;

class FissalLaboratorySeeder extends Seeder
{
    public function run(): void
    {
        $hematology = Area::firstOrCreate(['name' => 'Hematología']);
        $biochemistry = Area::firstOrCreate(['name' => 'Bioquímica']);
        $serology = Area::firstOrCreate(['name' => 'Serología e inmunología']);

        $tests = [
            [$hematology, 'Hematocrito', '%', 'Varones: 42.0 - 54.0 / Mujeres: 37.0 - 48.0', 'M', 'number'],
            [$hematology, 'Hemoglobina', 'g/dL', 'Varones: 14.0 - 18.0 / Mujeres: 12.0 - 16.0', 'M', 'number'],
            [$biochemistry, 'Urea pre diálisis', 'mg/dL', '10 - 50', 'M', 'number'],
            [$biochemistry, 'Urea post diálisis', 'mg/dL', '10 - 50', 'M', 'number'],
            [$biochemistry, 'Cloro', 'mmol/L', '98 - 107', 'M', 'number'],
            [$biochemistry, 'Sodio', 'mmol/L', '135 - 148', 'M', 'number'],
            [$biochemistry, 'Potasio', 'mmol/L', '3.5 - 5.3', 'M', 'number'],
            [$biochemistry, 'Calcio total', 'mg/dL', '8.8 - 10.2', 'M', 'number'],
            [$biochemistry, 'Aspartato aminotransferasa (AST/TGO)', 'U/L', 'Varones: < 50 / Mujeres: < 35', 'B', 'number'],
            [$biochemistry, 'Alanina aminotransferasa (ALT/TGP)', 'U/L', 'Varones: < 50 / Mujeres: < 35', 'B', 'number'],
            [$biochemistry, 'Albúmina', 'g/dL', '3.97 - 4.94', 'B', 'number'],
            [$biochemistry, 'Fósforo inorgánico (fosfato)', 'mg/dL', '2.5 - 5.6', 'T', 'number'],
            [$biochemistry, 'Fosfatasa alcalina', 'U/L', 'Varones: 40 - 129 / Mujeres: 35 - 104', 'T', 'number'],
            [$biochemistry, 'Hierro', 'µg/dL', '59 - 158', 'T', 'number'],
            [$biochemistry, 'Ferritina', 'ng/mL', '30 - 400', 'T', 'number'],
            [$biochemistry, 'Transferrina', 'mg/dL', '120 - 400', 'T', 'number'],
            [$biochemistry, 'Parathormona (PTH)', 'pg/mL', 'Adultos: 10 - 65 / Niños: 9 - 52', 'T', 'number'],
            [$serology, 'Anticuerpos VIH 1 y VIH 2', 'COI', '< 0.90 no reactivo', 'S', 'text'],
            [$serology, 'Sífilis (anticuerpo no treponémico)', 'COI', 'No reactivo', 'S', 'text'],
            [$serology, 'Antígeno de superficie hepatitis B (HBsAg)', 'COI', '< 0.90 negativo / > 1 positivo', 'S', 'text'],
            [$serology, 'Anticuerpo de superficie hepatitis B (anti-HBs)', 'COI', '< 10 negativo / > 10 positivo', 'S', 'text'],
            [$serology, 'Anticuerpo core total hepatitis B (anti-HBc)', 'COI', '< 1 positivo / > 1 negativo', 'S', 'text'],
            [$serology, 'Anticuerpo hepatitis C (anti-HCV)', 'COI', '< 0.90 negativo / > 1 positivo', 'S', 'text'],
            [$serology, 'Anticuerpo HTLV 1', 'COI', '< 1 no reactivo / > 1 reactivo', 'S', 'text'],
        ];

        foreach ($tests as [$area, $name, $unit, $reference, $frequency, $type]) {
            Test::updateOrCreate(['name' => $name], [
                'area_id' => $area->id,
                'unit' => $unit,
                'reference_value' => $reference,
                'frequency' => $frequency,
                'is_fissal' => true,
                'type' => $type,
            ]);
        }
    }
}
