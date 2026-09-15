<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->nullable()->unique();
            $table->string('name');
            $table->unsignedInteger('reference_quantity');
            $table->string('frequency', 30)->default('Mensual');
            $table->timestamps();
        });

        $medications = [
            ['Hierro (como sacarato) 20 mg Fe/mL INY 5 mL AMP', 4],
            ['Epoetina alfa (Eritropoyetina) 2000 UI/mL INY 1 mL', 12],
            ['Epoetina alfa (Eritropoyetina) 4000 UI/mL INY 1 mL', 6],
            ['Vitamina B12 Hidroxocobalamina 1 mg/mL INY 1 mL AMP', 12],
            ['Vitamina B – complejo B TAB o CAP', 30],
            ['Piridoxina clorhidrato 50 mg TAB', 30],
            ['Tiamina clorhidrato 100 mg TAB', 30],
            ['Ácido fólico 500 mcg (0,5 mg) TAB', 30],
            ['Sevelamero clorhidrato o carbonato 800 mg TAB', 90],
            ['Carbonato de Calcio 1,25 g (equivalente a 500 mg de Calcio) TAB', 90],
            ['Calcitriol 1 mcg/mL INY AMP', 13],
            ['Calcitriol 0,25 mcg (ug) TAB', 60],
            ['Enalapril maleato 10 mg TAB', 60],
            ['Captopril 25 mg TAB', 90],
            ['Amlodipino (como besilato) 10 mg TAB', 90],
            ['Nifedipino 10 mg TAB o CAP', 90],
            ['Nifedipino de 30 mg TAB o CAP', 60],
            ['Metildopa 250 mg TAB', 90],
            ['Atenolol 100 mg TAB', 30],
            ['Losartan potásico 50 mg TAB', 60],
        ];

        $now = now();
        DB::table('medication_catalog')->insert(array_map(fn (array $item) => [
            'name' => $item[0], 'reference_quantity' => $item[1], 'frequency' => 'Mensual',
            'created_at' => $now, 'updated_at' => $now,
        ], $medications));
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_catalog');
    }
};
