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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();

            // Relación con la orden (Indispensable para vincular el monitoreo)
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade');

            // Campos de monitoreo (Basados en la imagen de control horario)
            $table->time('hora')->nullable(); // Columna HR
            $table->string('pa')->nullable(); // Columna PA (Presión Arterial)
            $table->integer('fc')->nullable(); // Columna FC (Frecuencia Cardíaca)
            $table->string('qb')->nullable(); // Columna QB (Flujo de Bomba)
            $table->integer('cnd')->nullable(); // Columna CND (Conductividad)
            $table->integer('ra')->nullable(); // Columna RA (Presión Arterial Máquina)
            $table->integer('rv')->nullable(); // Columna RV (Presión Venosa Máquina)
            $table->integer('ptm')->nullable(); // Columna PTM (Presión Transmembrana)
            
            $table->text('observacion')->nullable(); // Columna Observación (derecha)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
