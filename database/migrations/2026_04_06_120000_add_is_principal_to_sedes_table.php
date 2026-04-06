<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->boolean('is_principal')->default(false)->after('is_active');
        });

        $principalSedeId = DB::table('warehouses')->where('is_principal', true)->value('sede_id');

        if ($principalSedeId) {
            DB::table('sedes')->where('id', $principalSedeId)->update(['is_principal' => true]);
        } else {
            $firstSedeId = DB::table('sedes')->orderBy('id')->value('id');
            if ($firstSedeId) {
                DB::table('sedes')->where('id', $firstSedeId)->update(['is_principal' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->dropColumn('is_principal');
        });
    }
};
