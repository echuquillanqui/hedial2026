<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fua_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('original_fua_id')->constrained('fuas')->cascadeOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('series');
            $table->unsignedBigInteger('correlative');
            $table->string('number')->unique();
            $table->string('status')->default('GENERATED');
            $table->timestamps();
            $table->unique(['series', 'correlative']);
            $table->unique('original_fua_id');
        });

        // Move legacy correction rows out of the ordinary FUA numbering table.
        if (Schema::hasColumn('fuas', 'corrects_fua_id')) {
            $legacyCorrections = DB::table('fuas')->where('type', 'CORRECTION')->get();
            foreach ($legacyCorrections as $correction) {
                if (! $correction->corrects_fua_id) {
                    continue;
                }

                DB::table('fua_corrections')->insert([
                    'id' => $correction->id,
                    'order_id' => $correction->order_id,
                    'original_fua_id' => $correction->corrects_fua_id,
                    'responsible_user_id' => $correction->responsible_user_id ?? null,
                    'generated_by' => $correction->generated_by ?? null,
                    'series' => $correction->series,
                    'correlative' => $correction->correlative,
                    'number' => $correction->number,
                    'status' => $correction->status,
                    'created_at' => $correction->created_at,
                    'updated_at' => $correction->updated_at,
                ]);
            }
            DB::table('fuas')->where('type', 'CORRECTION')->delete();
        }
    }

    public function down(): void
    {
        // Restore corrections only when the old table's unique constraints can
        // accept them; otherwise stop before dropping any correction data.
        foreach (DB::table('fua_corrections')->get() as $correction) {
            if (DB::table('fuas')->where('series', $correction->series)
                ->where('correlative', $correction->correlative)->exists()) {
                throw new RuntimeException('No se puede revertir: la serie y correlativo de una subsanación chocan con una FUA ordinaria.');
            }
        }

        foreach (DB::table('fua_corrections')->get() as $correction) {
            DB::table('fuas')->insert([
                'id' => $correction->id,
                'order_id' => $correction->order_id,
                'responsible_user_id' => $correction->responsible_user_id,
                'generated_by' => $correction->generated_by,
                'type' => 'CORRECTION',
                'series' => $correction->series,
                'correlative' => $correction->correlative,
                'number' => $correction->number,
                'corrects_fua_id' => $correction->original_fua_id,
                'status' => $correction->status,
                'created_at' => $correction->created_at,
                'updated_at' => $correction->updated_at,
            ]);
        }
        Schema::dropIfExists('fua_corrections');
    }
};
