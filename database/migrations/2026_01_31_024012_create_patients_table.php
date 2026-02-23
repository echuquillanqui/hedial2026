<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            
            // Identificadores
            $table->string('dni')->nullable()->unique(); 
            $table->string('affiliation_code')->nullable(); // Código SIS
            $table->string('medical_history_number')->nullable();
            
            // Nombres
            $table->string('first_name')->nullable();
            $table->string('other_names')->nullable();
            $table->string('surname')->nullable(); // Apellido Paterno
            $table->string('last_name')->nullable(); // Apellido Materno
            
            // Seguro y Régimen
            $table->boolean('is_insured')->nullable()->default(true);
            $table->enum('insurance_regime', ['SUBSIDIADO', 'SEMICONTRIBUTIVO'])->default('SUBSIDIADO');
            $table->enum('insurance_type', ['ESSALUD', 'SIS', 'SALUDPOL'])->nullable();
            
            // Datos Personales
            $table->enum('gender', ['F', 'M'])->nullable();
            $table->date('birth_date')->nullable();
            $table->integer('age')->nullable();
            
            // Ubicación
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('department')->nullable();

            $table->enum('secuencia', ['L-M-V', 'M-J-S'])->nullable();
            $table->enum('turno', ['1', '2', '3', '4'])->nullable();
            $table->enum('modulo', ['1', '2', '3', '4'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
