<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('tax_id', 20)->unique();
            $table->string('contact_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_stock_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('warehouse_material_id')->constrained('warehouse_materials')->cascadeOnDelete();
            $table->foreignId('warehouse_supplier_id')->constrained('warehouse_suppliers')->restrictOnDelete();
            $table->decimal('quantity', 14, 2);
            $table->date('expiration_date');
            $table->string('batch_number', 100)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_entries');
        Schema::dropIfExists('warehouse_suppliers');
    }
};
