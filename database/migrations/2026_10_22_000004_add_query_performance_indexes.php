<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['sede_id', 'fecha_orden'], 'orders_sede_fecha_idx');
            $table->index(['patient_id', 'sede_id', 'fecha_orden'], 'orders_patient_sede_fecha_idx');
        });

        Schema::table('patient_sectorizations', function (Blueprint $table) {
            $table->index(['patient_id', 'sequence', 'effective_from'], 'patient_sectorization_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_sede_fecha_idx');
            $table->dropIndex('orders_patient_sede_fecha_idx');
        });

        Schema::table('patient_sectorizations', function (Blueprint $table) {
            $table->dropIndex('patient_sectorization_lookup_idx');
        });
    }
};
