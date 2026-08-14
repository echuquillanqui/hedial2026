<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('laboratory_period', ['M', 'B', 'T', 'S'])
                ->nullable()
                ->after('es_covid');
        });

        Schema::table('laboratory_orders', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('id')
                ->unique()->constrained('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('laboratory_period');
        });
    }
};
