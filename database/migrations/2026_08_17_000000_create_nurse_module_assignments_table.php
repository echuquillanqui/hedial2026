<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nurse_module_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedTinyInteger('module');
            $table->timestamps();

            $table->unique(['user_id', 'sede_id', 'work_date'], 'nurse_module_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nurse_module_assignments');
    }
};
