<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE patients MODIFY modulo ENUM('1', '2', '3', '4', 'AISLADO') NULL");
            DB::statement('ALTER TABLE nurse_module_assignments MODIFY module VARCHAR(20) NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('patients')->where('modulo', 'AISLADO')->update(['modulo' => null]);
            DB::table('nurse_module_assignments')->where('module', 'AISLADO')->delete();
            DB::statement("ALTER TABLE patients MODIFY modulo ENUM('1', '2', '3', '4') NULL");
            DB::statement('ALTER TABLE nurse_module_assignments MODIFY module TINYINT UNSIGNED NOT NULL');
        }
    }
};
