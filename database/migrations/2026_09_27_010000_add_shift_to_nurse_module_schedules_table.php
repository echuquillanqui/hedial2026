<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->dropUnique(['sede_id', 'module']);
            $table->unsignedTinyInteger('shift')->default(1)->after('module');
            $table->unique(['sede_id', 'module', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->dropUnique(['sede_id', 'module', 'shift']);
            $table->dropColumn('shift');
            $table->unique(['sede_id', 'module']);
        });
    }
};
