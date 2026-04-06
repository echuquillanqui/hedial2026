<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('warehouse_materials', function (Blueprint $table) {
            $table->foreignId('warehouse_material_category_id')
                ->nullable()
                ->after('unit')
                ->constrained('warehouse_material_categories')
                ->nullOnDelete();
        });

        $now = now();
        $categoryId = DB::table('warehouse_material_categories')->insertGetId([
            'name' => 'GENERAL',
            'description' => 'Categoría creada por defecto para material existente.',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('warehouse_materials')
            ->whereNull('warehouse_material_category_id')
            ->update(['warehouse_material_category_id' => $categoryId]);
    }

    public function down(): void
    {
        Schema::table('warehouse_materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_material_category_id');
        });

        Schema::dropIfExists('warehouse_material_categories');
    }
};
