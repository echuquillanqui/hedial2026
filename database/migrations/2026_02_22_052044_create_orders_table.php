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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients'); // Relación con pacientes
            $table->string('codigo_unico')->unique(); // Ej: ORD-20231027-X8S
            $table->string('sala'); // Ej: Sala A, Sala B, VIP
            $table->string('turno'); // Ej: Mañana, Tarde, Noche
            $table->boolean('es_covid')->default(false);
            $table->decimal('horas_dialisis', 3, 1);
            $table->date('fecha_orden'); // La fecha para la que es la orden
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
