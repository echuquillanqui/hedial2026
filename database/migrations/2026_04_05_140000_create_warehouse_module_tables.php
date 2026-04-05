<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->string('name');
            $table->boolean('is_principal')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['sede_id']);
        });

        Schema::create('warehouse_materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('unit', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('warehouse_material_id')->constrained('warehouse_materials')->cascadeOnDelete();
            $table->decimal('current_qty', 14, 2)->default(0);
            $table->decimal('min_qty', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'warehouse_material_id'], 'warehouse_stock_unique');
        });

        Schema::create('warehouse_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
                'partially_dispatched',
                'dispatched',
                'partially_received',
                'received',
                'cancelled'
            ])->default('draft');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_request_id')->constrained('warehouse_requests')->cascadeOnDelete();
            $table->foreignId('warehouse_material_id')->constrained('warehouse_materials')->cascadeOnDelete();
            $table->decimal('qty_requested', 14, 2);
            $table->decimal('qty_approved', 14, 2)->default(0);
            $table->decimal('qty_sent', 14, 2)->default(0);
            $table->decimal('qty_received', 14, 2)->default(0);
            $table->enum('dispatch_status', ['pending', 'partial', 'complete', 'not_sent'])->default('pending');
            $table->string('not_sent_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_request_id')->constrained('warehouse_requests')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('warehouse_material_id')->constrained('warehouse_materials')->cascadeOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment']);
            $table->decimal('qty', 14, 2);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_movements');
        Schema::dropIfExists('warehouse_request_status_logs');
        Schema::dropIfExists('warehouse_request_items');
        Schema::dropIfExists('warehouse_requests');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('warehouse_materials');
        Schema::dropIfExists('warehouses');
    }
};
