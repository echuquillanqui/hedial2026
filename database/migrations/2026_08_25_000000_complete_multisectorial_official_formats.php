<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hemodialysis_consents', fn (Blueprint $table) => $table->string('representative_signature_path')->nullable()->after('patient_signature_path'));
        Schema::table('patients', function (Blueprint $table) { $table->string('province')->nullable()->after('district'); $table->string('phone', 30)->nullable()->after('department'); });
        Schema::table('nutrition_assessments', function (Blueprint $table) {
            $table->text('clinical_history')->nullable()->after('assessment_date');
            $table->text('nutritional_history')->nullable()->after('clinical_history');
            $table->text('general_recommendations')->nullable()->after('nutritional_diagnosis');
            $table->text('dietary_recommendations')->nullable()->after('general_recommendations');
        });
        Schema::create('psychology_assessments', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessment_date');
            foreach (['consultation_reason','behavior_observation','psychological_tests','psychological_diagnosis','treatment_plan','recommendations'] as $column) $table->text($column)->nullable();
            $table->timestamps();
        });
        Schema::create('eq5d_assessments', function (Blueprint $table) {
            $table->id(); $table->foreignId('psychology_assessment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->date('assessed_at');
            foreach (['mobility','self_care','usual_activities','pain_discomfort','anxiety_depression'] as $column) $table->unsignedTinyInteger($column);
            $table->unsignedTinyInteger('health_scale'); $table->timestamps();
        });
        Schema::create('social_work_assessments', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->date('assessment_date');
            foreach (['social_evaluation','family_evaluation','housing_evaluation','employment_evaluation','economic_evaluation','social_diagnosis','general_measures','specific_measures'] as $column) $table->text($column)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('social_work_assessments'); Schema::dropIfExists('eq5d_assessments'); Schema::dropIfExists('psychology_assessments');
        Schema::table('nutrition_assessments', fn (Blueprint $table) => $table->dropColumn(['clinical_history','nutritional_history','general_recommendations','dietary_recommendations']));
        Schema::table('hemodialysis_consents', fn (Blueprint $table) => $table->dropColumn('representative_signature_path'));
        Schema::table('patients', fn (Blueprint $table) => $table->dropColumn(['province','phone']));
    }
};
