<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('area_id');
            $table->unsignedInteger('fua_quantity')->default(1)->after('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'fua_quantity']);
        });
    }
};
