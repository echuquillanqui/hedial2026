<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tests')
            ->where('name', 'Albúmina')
            ->where('is_fissal', true)
            ->update(['frequency' => 'T']);
    }

    public function down(): void
    {
        DB::table('tests')
            ->where('name', 'Albúmina')
            ->where('is_fissal', true)
            ->update(['frequency' => 'B']);
    }
};
