<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_stock_entries', function (Blueprint $table) {
            $table->date('valid_from')->nullable()->after('quantity');
            $table->index(['warehouse_id', 'valid_from', 'expiration_date'], 'warehouse_entries_validity_index');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_stock_entries', function (Blueprint $table) {
            $table->dropIndex('warehouse_entries_validity_index');
            $table->dropColumn('valid_from');
        });
    }
};
