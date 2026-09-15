<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('digital_seal_path')->nullable()->after('profession');
        });

        Schema::table('laboratory_orders', function (Blueprint $table) {
            $table->foreignId('validated_by_user_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('digital_seal_path');
        });
    }
};
