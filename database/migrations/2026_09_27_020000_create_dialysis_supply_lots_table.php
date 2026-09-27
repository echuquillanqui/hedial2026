<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dialysis_supply_lots', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20);
            $table->decimal('measurement', 3, 1)->nullable();
            $table->string('lot_number', 80);
            $table->date('valid_from');
            $table->date('valid_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['category', 'measurement', 'valid_from', 'valid_until'], 'supply_lot_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialysis_supply_lots');
    }
};
