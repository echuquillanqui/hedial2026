<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuas', function (Blueprint $table) {
            $table->foreignId('generated_by')->nullable()->after('responsible_user_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fuas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generated_by');
        });
    }
};
