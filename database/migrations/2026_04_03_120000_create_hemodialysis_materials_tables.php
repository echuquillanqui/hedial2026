<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hemodialysis_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit', 30)->default('unidad');
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('quantity_per_order', 10, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hemodialysis_material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hemodialysis_material_id')->constrained('hemodialysis_materials')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('consumed_at');
            $table->decimal('quantity', 10, 2);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['consumed_at', 'patient_id']);
            $table->unique(['hemodialysis_material_id', 'order_id']);
        });

        DB::table('hemodialysis_materials')->insert([
            ['name' => 'Líneas de sangre (circuito extracorpóreo)', 'unit' => 'kit', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Líquido de diálisis (ácido + bicarbonato)', 'unit' => 'set', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Agujas de fístula o kit de conexión para catéter', 'unit' => 'kit', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Suero fisiológico (purgado y retorno)', 'unit' => 'unidad', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jeringas y agujas de varios tamaños', 'unit' => 'set', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gasas estériles y apósitos adhesivos', 'unit' => 'set', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desinfectante (alcohol, clorhexidina o povidona)', 'unit' => 'unidad', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Guantes estériles y mascarillas', 'unit' => 'set', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Esparadrapo (cinta médica)', 'unit' => 'rollo', 'stock' => 0, 'quantity_per_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hemodialysis_material_consumptions');
        Schema::dropIfExists('hemodialysis_materials');
    }
};
