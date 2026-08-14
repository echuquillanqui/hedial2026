<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nephrology_consultations', function (Blueprint $table) {
            $table->json('diagnoses')->nullable()->after('diagnosis');
            $table->json('auxiliary_exams')->nullable()->after('treatment_plan');
            $table->date('next_laboratory_date')->nullable()->after('auxiliary_exams');
            $table->date('next_appointment_date')->nullable()->after('next_laboratory_date');
        });
    }

    public function down(): void
    {
        Schema::table('nephrology_consultations', function (Blueprint $table) {
            $table->dropColumn(['diagnoses', 'auxiliary_exams', 'next_laboratory_date', 'next_appointment_date']);
        });
    }
};
