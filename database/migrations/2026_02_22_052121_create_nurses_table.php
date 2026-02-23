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
        Schema::create('nurses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            
            // Sesión y Puesto
            $table->string('frecuencia_hd')->nullable(); // Ej: L-M-V
            $table->integer('numero_hd')->nullable(); // El contador de 1 a 13
            $table->string('puesto')->nullable();
            $table->string('numero_maquina')->nullable(); // Igual al puesto por lógica
            $table->string('marca_modelo')->default('FRESENIUS/4008S');
            
            // Estado Físico y Filtro
            $table->string('aspecto_dializador')->nullable();
            $table->string('filtro')->nullable();
            $table->string('pa_inicial')->nullable(); // Se jalará de medicals
            $table->string('pa_final')->nullable();
            $table->decimal('peso_inicial', 5, 2)->nullable(); // De medicals
            $table->decimal('peso_final', 5, 2)->nullable();
            $table->string('uf')->nullable(); // De medicals

            // Accesos Vasculares
            // Opciones: CVCLP, FAV, INJ, CVCL, CVCT
            $table->string('acceso_venoso')->nullable();
            $table->string('acceso_arterial')->nullable();

            // Medicamentos (Copia editable de Medicals)
            $table->string('epo2000')->nullable();
            $table->string('epo4000')->nullable();
            $table->string('vitamina_b12')->nullable();
            $table->string('hierro')->nullable();
            $table->string('calcitriol')->nullable();
            $table->text('otros_medicamentos')->nullable();

            // Notas de Enfermería (SOAP)
            $table->text('s')->nullable(); // Subjetivo
            $table->text('o')->nullable(); // Objetivo
            $table->text('a')->nullable(); // Análisis
            $table->text('p')->nullable(); // Plan
            $table->text('observacion_final')->nullable();

            // Personal Responsable
            $table->foreignId('enfermero_que_inicia_id')->nullable()->constrained('users');
            $table->foreignId('enfermero_que_finaliza_id')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurses');
    }
};
