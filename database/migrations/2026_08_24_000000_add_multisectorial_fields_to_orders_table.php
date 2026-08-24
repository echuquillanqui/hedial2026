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
        // MySQL may use the composite unique index created in up() to support
        // the patient_id foreign key. Give that constraint a replacement index
        // before removing the composite one, otherwise rollback fails with 1553.
        Schema::table('orders', function (Blueprint $table) {
            $table->index('patient_id', 'orders_patient_id_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_patient_attention_period_unique');
            $table->dropIndex('orders_sede_attention_professional_index');
            $table->dropForeign(['assigned_professional_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'assigned_professional_id', 'created_by', 'status', 'due_date',
                'period_key', 'completed_at',
            ]);
        });
    }
};
