<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_module_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained()->cascadeOnDelete();
            $table->string('module', 20);
            $table->unsignedTinyInteger('shift');
            $table->time('start_from');
            $table->time('start_to');
            $table->time('finish_from');
            $table->time('finish_to');
            $table->unsignedSmallInteger('interval_minutes')->default(5);
            $table->timestamps();

            $table->unique(['sede_id', 'module', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_module_schedules');
    }
};
