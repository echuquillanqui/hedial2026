<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nutrition_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('medical_id')->nullable()->constrained('medicals')->nullOnDelete();
            $table->foreignId('nephrology_consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessment_date');
            foreach (['reason', 'appetite', 'dietary_intake', 'gastrointestinal_symptoms', 'functional_capacity', 'physical_findings', 'nutritional_diagnosis', 'intervention_plan', 'recommendations', 'observations'] as $column) $table->text($column)->nullable();
            $table->timestamps();
        });

        Schema::create('nutrition_assessment_laboratory_results', function (Blueprint $table) {
            $table->unsignedBigInteger('nutrition_assessment_id');
            $table->unsignedBigInteger('laboratory_order_item_id');
            $table->foreign('nutrition_assessment_id', 'nutrition_lab_assessment_fk')->references('id')->on('nutrition_assessments')->cascadeOnDelete();
            $table->foreign('laboratory_order_item_id', 'nutrition_lab_result_fk')->references('id')->on('laboratory_order_items')->restrictOnDelete();
            $table->primary(['nutrition_assessment_id', 'laboratory_order_item_id'], 'nutrition_lab_result_primary');
        });

        Schema::create('mis_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_assessment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('albumin_result_id')->nullable()->constrained('laboratory_order_items')->restrictOnDelete();
            $table->foreignId('transferrin_result_id')->nullable()->constrained('laboratory_order_items')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessed_at');
            foreach (['weight_change_score', 'dietary_intake_score', 'gastrointestinal_score', 'functional_capacity_score', 'comorbidity_score', 'fat_stores_score', 'muscle_wasting_score'] as $column) $table->unsignedTinyInteger($column);
            $table->unsignedTinyInteger('bmi_score')->nullable();
            $table->unsignedTinyInteger('albumin_score')->nullable();
            $table->unsignedTinyInteger('transferrin_score')->nullable();
            $table->unsignedTinyInteger('total_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mis_assessments');
        Schema::dropIfExists('nutrition_assessment_laboratory_results');
        Schema::dropIfExists('nutrition_assessments');
    }
};
