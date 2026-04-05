<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('id')->constrained('sedes')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('id')->constrained('sedes')->nullOnDelete();
        });

        // Retrocompatibilidad: copiar sede del paciente a órdenes existentes.
        DB::table('orders')
            ->join('patients', 'patients.id', '=', 'orders.patient_id')
            ->update(['orders.sede_id' => DB::raw('patients.sede_id')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
        });
    }
};
