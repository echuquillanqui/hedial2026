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
        Schema::create('extra_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->date('usage_date');
            $table->string('material_name');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['usage_date', 'patient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_materials');
    }
};
