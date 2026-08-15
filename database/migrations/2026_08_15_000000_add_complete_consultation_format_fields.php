<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nephrology_consultations', function (Blueprint $table) {
            $table->time('consultation_time')->nullable()->after('consultation_date');
            $table->date('dialysis_start_date')->nullable()->after('consultation_time');
            $table->string('disease_duration', 80)->nullable()->after('dialysis_start_date');
            $table->string('etiology')->nullable()->after('history');
            $table->string('vascular_access')->nullable()->after('etiology');
            $table->string('symptoms')->nullable()->after('vascular_access');
            $table->decimal('height', 5, 2)->nullable()->after('weight');
            $table->unsignedSmallInteger('respiratory_rate')->nullable()->after('heart_rate');
            $table->decimal('bmi', 5, 2)->nullable()->after('oxygen_saturation');
            $table->decimal('diuresis', 8, 2)->nullable()->after('bmi');
            $table->text('lung_exam')->nullable()->after('physical_exam');
            $table->text('cardiac_exam')->nullable()->after('lung_exam');
            $table->string('dialysis_prescription')->nullable()->after('treatment_plan');
            $table->decimal('dialysis_hours', 4, 1)->nullable()->after('dialysis_prescription');
            $table->decimal('filter_area', 4, 2)->nullable()->after('dialysis_hours');
            $table->boolean('anemia_treatment')->nullable()->after('filter_area');
            $table->decimal('hemoglobin', 5, 2)->nullable()->after('anemia_treatment');
            $table->string('epoetin_dose')->nullable()->after('hemoglobin');
            $table->string('hydroxocobalamin_dose')->nullable()->after('epoetin_dose');
            $table->string('iron_dose')->nullable()->after('hydroxocobalamin_dose');
            $table->boolean('bone_mineral_treatment')->nullable()->after('iron_dose');
            $table->boolean('antihypertensive_treatment')->nullable()->after('bone_mineral_treatment');
            $table->text('other_treatment')->nullable()->after('antihypertensive_treatment');
        });
    }

    public function down(): void
    {
        Schema::table('nephrology_consultations', function (Blueprint $table) {
            $table->dropColumn([
                'consultation_time', 'dialysis_start_date', 'disease_duration', 'etiology', 'vascular_access',
                'symptoms', 'height', 'respiratory_rate', 'bmi', 'diuresis', 'lung_exam', 'cardiac_exam',
                'dialysis_prescription', 'dialysis_hours', 'filter_area', 'anemia_treatment', 'hemoglobin',
                'epoetin_dose', 'hydroxocobalamin_dose', 'iron_dose', 'bone_mineral_treatment',
                'antihypertensive_treatment', 'other_treatment',
            ]);
        });
    }
};
