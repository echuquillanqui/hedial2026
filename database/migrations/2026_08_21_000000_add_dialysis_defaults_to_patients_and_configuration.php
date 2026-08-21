<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('peso_seco', 5, 2)->nullable()->after('modulo');
            $table->string('acceso_arterial')->nullable()->after('peso_seco');
            $table->string('acceso_venoso')->nullable()->after('acceso_arterial');
        });
        Schema::table('fua_configurations', function (Blueprint $table) {
            $table->string('dialysis_equipment')->default('FRESENIUS/4008S')->after('company_phone');
        });
    }

    public function down(): void
    {
        Schema::table('patients', fn (Blueprint $table) => $table->dropColumn(['peso_seco', 'acceso_arterial', 'acceso_venoso']));
        Schema::table('fua_configurations', fn (Blueprint $table) => $table->dropColumn('dialysis_equipment'));
    }
};
