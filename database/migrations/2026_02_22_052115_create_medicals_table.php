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
        Schema::create('medicals', function (Blueprint $table) {
           
            $table->id();

            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade');

            $table->time('hora_inicial')->nullable();
            $table->decimal('peso_inicial', 5, 2)->nullable();
            $table->string('pa_inicial')->nullable();
            $table->integer('frecuencia_cardiaca')->nullable();
            $table->integer('so2')->nullable();
            $table->decimal('fio2', 5, 2)->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            
            $table->text('problemas_clinicos')->nullable();
            $table->text('evaluacion')->nullable();
            $table->text('indicaciones')->nullable();
            $table->text('signos_sintomas')->nullable();

            $table->string('epo2000')->nullable();
            $table->string('epo4000')->nullable();
            $table->string('hierro')->nullable();
            $table->string('vitamina_b12')->nullable();
            $table->string('calcitriol')->nullable();
            $table->string('heparina')->nullable();

            $table->decimal('hora_hd', 3, 1);
            $table->decimal('peso_seco', 5, 2)->nullable();
            $table->string('uf')->nullable();
            $table->integer('qb')->nullable();
            $table->integer('qd')->nullable();
            $table->integer('bicarbonato')->nullable();
            $table->integer('na_inicial')->nullable();
            $table->decimal('cnd', 5, 2)->nullable();
            $table->integer('na_final')->nullable();
            $table->string('perfil_na')->nullable();
            $table->string('area_filtro')->nullable();
            $table->string('membrana')->nullable();
            $table->string('perfil_uf')->nullable();

            $table->text('evaluacion_final')->nullable();
            $table->time('hora_final')->nullable();

            $table->foreignId('usuario_que_inicia_hd')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('restrict');

            $table->foreignId('usuario_que_finaliza_hd')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicals');
    }
};
