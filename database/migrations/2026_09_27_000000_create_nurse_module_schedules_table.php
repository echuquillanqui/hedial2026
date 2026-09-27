<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nurse_module_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained()->cascadeOnDelete();
            $table->string('module', 20);
            $table->json('start_times');
            $table->timestamps();

            $table->unique(['sede_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nurse_module_schedules');
    }
};
