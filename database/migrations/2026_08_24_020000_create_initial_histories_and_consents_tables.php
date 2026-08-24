<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initial_clinical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('nephrologist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('recorded_at');
            $table->text('personal_history')->nullable();
            $table->text('family_history')->nullable();
            $table->text('ckd_etiology')->nullable();
            $table->date('first_hemodialysis_date')->nullable();
            $table->json('comorbidities')->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->text('transfusion_history')->nullable();
            $table->decimal('residual_diuresis', 10, 2)->nullable();
            $table->text('allergies')->nullable();
            $table->text('previous_renal_therapy')->nullable();
            $table->json('immunizations')->nullable();
            $table->text('clinical_exam')->nullable();
            $table->text('vascular_access_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('initial_history_laboratory_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initial_clinical_history_id')
                ->constrained(indexName: 'initial_history_lab_history_fk')
                ->cascadeOnDelete();
            $table->foreignId('laboratory_order_item_id')
                ->constrained(indexName: 'initial_history_lab_order_item_fk')
                ->restrictOnDelete();
            $table->timestamps();
            $table->unique(['initial_clinical_history_id', 'laboratory_order_item_id'], 'initial_history_lab_unique');
        });

        Schema::create('hemodialysis_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('physician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consented_at');
            $table->string('version', 30);
            $table->boolean('accepted');
            $table->string('representative_name')->nullable();
            $table->string('representative_document', 30)->nullable();
            $table->string('representative_relationship', 80)->nullable();
            $table->string('patient_signature_path')->nullable();
            $table->string('fingerprint_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'consented_at', 'version'], 'consent_patient_date_version_unique');
            $table->index(['sede_id', 'consented_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hemodialysis_consents');
        Schema::dropIfExists('initial_history_laboratory_results');
        Schema::dropIfExists('initial_clinical_histories');
    }
};
