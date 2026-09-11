<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_nursing_annexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('frequency', 10);
            $table->string('module', 20);
            $table->string('code', 50)->unique();
            $table->json('automatic_values');
            $table->json('values');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sede_id', 'work_date', 'frequency', 'module'], 'daily_nursing_annex_scope_unique');
            $table->index(['sede_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_nursing_annexes');
    }
};
