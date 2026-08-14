<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fua_configurations', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('ipress_name');
            $table->string('company_name')->nullable()->after('logo_path');
            $table->string('company_address')->nullable()->after('company_name');
            $table->string('company_phone', 50)->nullable()->after('company_address');
            $table->string('consultation_reason')->default('Control mensual')->after('diagnosis_name');
            $table->text('default_anamnesis')->nullable()->after('consultation_reason');
            $table->string('default_etiology')->default('Nefropatía diabética')->after('default_anamnesis');
            $table->string('default_vascular_access')->default('Fístula arteriovenosa')->after('default_etiology');
            $table->string('secondary_diagnosis_code', 20)->default('D63.8')->after('default_vascular_access');
            $table->string('secondary_diagnosis_name')->default('Anemia en otras enfermedades crónicas clasificadas en otra parte')->after('secondary_diagnosis_code');
        });
    }

    public function down(): void
    {
        Schema::table('fua_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path', 'company_name', 'company_address', 'company_phone',
                'consultation_reason', 'default_anamnesis', 'default_etiology',
                'default_vascular_access', 'secondary_diagnosis_code',
                'secondary_diagnosis_name',
            ]);
        });
    }
};
