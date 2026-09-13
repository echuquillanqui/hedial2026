<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_catalog', function (Blueprint $table) {
            $table->text('indication')->nullable()->after('frequency');
        });

        $indications = [
            'Losartan potásico 50 mg TAB' => '1 tableta cada 12 horas. En algunos pacientes: cada 24 horas.',
            'Amlodipino (como besilato) 10 mg TAB' => '1 tableta cada 24 horas.',
            'Nifedipino de 30 mg TAB o CAP' => '1 tableta cada 8, 12 o 24 horas, según el paciente.',
            'Metildopa 250 mg TAB' => '1 tableta cada 8, 12 o 24 horas, según el paciente.',
            'Atenolol 100 mg TAB' => '1 tableta cada 24 horas.',
            'Enalapril maleato 10 mg TAB' => '1 tableta cada 12 horas. En algunos casos: cada 24 horas.',
            'Tiamina clorhidrato 100 mg TAB' => '1 tableta cada 12 o 24 horas, según el paciente.',
            'Piridoxina clorhidrato 50 mg TAB' => '1 tableta cada 24 horas.',
            'Ácido fólico 500 mcg (0,5 mg) TAB' => '1 tableta cada 24 horas.',
            'Carbonato de Calcio 1,25 g (equivalente a 500 mg de Calcio) TAB' => '1 tableta por vía oral cada 8 horas.',
            'Sevelamero clorhidrato o carbonato 800 mg TAB' => '1 tableta después del desayuno, almuerzo y cena.',
        ];

        foreach ($indications as $name => $indication) {
            DB::table('medication_catalog')->where('name', $name)->update([
                'indication' => $indication,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('medication_catalog', function (Blueprint $table) {
            $table->dropColumn('indication');
        });
    }
};
