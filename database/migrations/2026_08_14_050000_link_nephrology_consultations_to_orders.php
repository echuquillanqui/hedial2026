<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nephrology_consultations', function (Blueprint $table) {
            $table->foreignId('order_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('orders')
                ->cascadeOnDelete();
        });

        // Recupera también las consultas generadas antes de que ambos módulos
        // quedaran vinculados, para que no desaparezcan del índice clínico.
        DB::table('orders')
            ->where('attention_type', 'NEPHROLOGY')
            ->orderBy('id')
            ->chunkById(100, function ($orders) {
                $now = now();
                $consultations = $orders->map(fn ($order) => [
                    'order_id' => $order->id,
                    'sede_id' => $order->sede_id,
                    'patient_id' => $order->patient_id,
                    'consultation_date' => $order->fecha_orden,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('nephrology_consultations')->insertOrIgnore($consultations);
            });
    }

    public function down(): void
    {
        Schema::table('nephrology_consultations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
