<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fua_corrections', function (Blueprint $table) {
            $table->string('observation_preset')->nullable()->after('observations');
            $table->boolean('show_hemodialysis')->default(true)->after('show_medications');
            $table->boolean('show_laboratories')->default(true)->after('show_hemodialysis');
        });

        DB::table('fua_corrections')->update([
            'show_hemodialysis' => DB::raw('show_procedures'),
            'show_laboratories' => DB::raw('show_procedures'),
        ]);
    }

    public function down(): void
    {
        Schema::table('fua_corrections', function (Blueprint $table) {
            $table->dropColumn(['observation_preset', 'show_hemodialysis', 'show_laboratories']);
        });
    }
};
