<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_materials', function (Blueprint $table) {
            $table->boolean('automatic_consumption')->default(false)->after('unit');
            $table->decimal('quantity_per_session', 10, 2)->default(0)->after('automatic_consumption');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_materials', function (Blueprint $table) {
            $table->dropColumn(['automatic_consumption', 'quantity_per_session']);
        });
    }
};
