<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nephrology_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('consultation_date');
            $table->string('blood_pressure', 20)->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->unsignedTinyInteger('oxygen_saturation')->nullable();
            $table->text('reason')->nullable();
            $table->text('current_illness')->nullable();
            $table->text('history')->nullable();
            $table->text('physical_exam')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nephrology_consultation_id')->constrained()->cascadeOnDelete();
            $table->string('fua_code', 30)->nullable();
            $table->string('description');
            $table->string('c', 50)->nullable();
            $table->decimal('prescribed_quantity', 8, 2)->default(0);
            $table->decimal('delivered_quantity', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
        Schema::dropIfExists('nephrology_consultations');
    }
};
