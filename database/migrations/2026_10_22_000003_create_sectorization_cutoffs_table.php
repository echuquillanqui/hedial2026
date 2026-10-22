<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectorization_cutoffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->string('sequence', 5);
            $table->date('effective_from');
            $table->timestamps();
            $table->unique(['sede_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectorization_cutoffs');
    }
};
