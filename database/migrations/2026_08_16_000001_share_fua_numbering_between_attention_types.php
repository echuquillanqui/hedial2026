<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('fua_configurations')->orderBy('id')->each(function ($configuration) {
            $nextNumber = max(
                (int) $configuration->hemodialysis_next_number,
                (int) $configuration->nephrology_next_number
            );

            DB::table('fua_configurations')->where('id', $configuration->id)->update([
                'nephrology_series' => $configuration->hemodialysis_series,
                'hemodialysis_next_number' => $nextNumber,
                'nephrology_next_number' => $nextNumber,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // No es posible reconstruir de forma segura dos contadores independientes.
    }
};
