<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use the existing composite unique index to support the
        // sede_id foreign key, so give the constraint a temporary index before
        // replacing that unique index.
        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->index('sede_id', 'nurse_module_schedules_sede_id_temporary_index');
        });

        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->dropUnique(['sede_id', 'module']);
            $table->unsignedTinyInteger('shift')->default(1)->after('module');
            $table->unique(['sede_id', 'module', 'shift']);
        });

        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->dropIndex('nurse_module_schedules_sede_id_temporary_index');
        });
    }

    public function down(): void
    {
        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->index('sede_id', 'nurse_module_schedules_sede_id_temporary_index');
        });

        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->dropUnique(['sede_id', 'module', 'shift']);
            $table->dropColumn('shift');
            $table->unique(['sede_id', 'module']);
        });

        Schema::table('nurse_module_schedules', function (Blueprint $table) {
            $table->dropIndex('nurse_module_schedules_sede_id_temporary_index');
        });
    }
};
