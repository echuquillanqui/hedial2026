<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->enum('frequency', ['M', 'B', 'T', 'S'])->nullable()->after('type')->index();
            $table->boolean('is_fissal')->default(false)->after('frequency')->index();
        });

        Schema::table('laboratory_orders', function (Blueprint $table) {
            $table->foreignId('patient_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->enum('period', ['M', 'B', 'T', 'S'])->default('M')->after('requested_by');
            $table->date('sampled_at')->nullable()->after('period');
            $table->string('provenance')->default('FISSAL')->after('sampled_at');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_orders', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn(['patient_id', 'period', 'sampled_at', 'provenance']);
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn(['frequency', 'is_fissal']);
        });
    }
};
