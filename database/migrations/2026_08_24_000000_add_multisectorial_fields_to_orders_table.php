<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('assigned_professional_id')->nullable()->after('patient_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('assigned_professional_id')
                ->constrained('users')->nullOnDelete();
            $table->string('status', 30)->nullable()->after('attention_type')->index();
            $table->date('due_date')->nullable()->after('fecha_orden')->index();
            $table->string('period_key', 20)->nullable()->after('due_date');
            $table->timestamp('completed_at')->nullable()->after('period_key');

            $table->unique(
                ['patient_id', 'attention_type', 'period_key'],
                'orders_patient_attention_period_unique'
            );
            $table->index(
                ['sede_id', 'attention_type', 'assigned_professional_id'],
                'orders_sede_attention_professional_index'
            );
        });
    }

    public function down(): void
    {
        // MySQL can reuse either composite index for the foreign keys whose
        // columns are at the beginning of the index. Keep patient_id and
        // sede_id indexed because their original foreign keys remain after
        // this migration rolls back.
        foreach (['patient_id', 'sede_id'] as $column) {
            $index = "orders_{$column}_index";

            // A failed rollback may already have created one replacement index.
            if (! Schema::hasIndex('orders', $index)) {
                Schema::table('orders', function (Blueprint $table) use ($column, $index) {
                    $table->index($column, $index);
                });
            }
        }

        // Remove these foreign keys in a separate ALTER before dropping the
        // composite index. Otherwise MySQL rejects the rollback with error 1553
        // while an index is supporting a foreign key.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['assigned_professional_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_patient_attention_period_unique');
            $table->dropIndex('orders_sede_attention_professional_index');
            $table->dropColumn([
                'assigned_professional_id', 'created_by', 'status', 'due_date',
                'period_key', 'completed_at',
            ]);
        });
    }
};
