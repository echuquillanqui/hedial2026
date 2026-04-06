<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('code', 30)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['sede_id', 'name']);
        });

        Schema::create('operational_area_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_area_id')->constrained('operational_areas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['operational_area_id', 'user_id']);
        });

        Schema::table('warehouse_requests', function (Blueprint $table) {
            $table->foreignId('operational_area_id')->nullable()->after('to_warehouse_id')->constrained('operational_areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operational_area_id');
        });

        Schema::dropIfExists('operational_area_user');
        Schema::dropIfExists('operational_areas');
    }
};
