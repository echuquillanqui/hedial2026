<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('requested_by')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('laboratory_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_order_id')->constrained('laboratory_orders')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->string('result_value')->nullable();
            $table->text('result_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['laboratory_order_id', 'test_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_order_items');
        Schema::dropIfExists('laboratory_orders');
    }
};
