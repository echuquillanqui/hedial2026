<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fua_corrections', function (Blueprint $table) {
            $table->string('reason')->default('OTRO')->after('number');
            $table->text('observations')->nullable()->after('reason');
            $table->boolean('show_doctor')->default(true)->after('observations');
            $table->boolean('show_stamp')->default(true)->after('show_doctor');
            $table->boolean('show_medications')->default(true)->after('show_stamp');
            $table->boolean('show_procedures')->default(true)->after('show_medications');
            $table->boolean('show_diagnoses')->default(true)->after('show_procedures');
        });
    }

    public function down(): void
    {
        Schema::table('fua_corrections', function (Blueprint $table) {
            $table->dropColumn(['reason', 'observations', 'show_doctor', 'show_stamp', 'show_medications', 'show_procedures', 'show_diagnoses']);
        });
    }
};
