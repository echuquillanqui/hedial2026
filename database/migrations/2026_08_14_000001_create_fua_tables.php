<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fua_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('ipress_code')->nullable();
            $table->string('ipress_name')->nullable();
            $table->string('diagnosis_code')->default('N18.0');
            $table->string('diagnosis_name')->default('ENFERMEDAD RENAL CRONICA 5 EN HEMODIALISIS');
            $table->string('responsible_name')->nullable();
            $table->string('responsible_document')->nullable();
            $table->string('responsible_college_number')->nullable();
            $table->string('responsible_specialty')->default('NEFROLOGIA');
            $table->string('hemodialysis_series')->default('0000247');
            $table->unsignedBigInteger('hemodialysis_next_number')->default(1);
            $table->string('nephrology_series')->default('8181-23');
            $table->unsignedBigInteger('nephrology_next_number')->default(1);
            $table->string('correction_series')->default('0000181-26');
            $table->unsignedBigInteger('correction_next_number')->default(1);
            $table->unsignedTinyInteger('number_length')->default(7);
            $table->timestamps();
        });

        DB::table('fua_configurations')->insert([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->string('attention_type')->default('HEMODIALYSIS')->after('es_covid');
        });

        Schema::create('fuas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('series');
            $table->unsignedBigInteger('correlative');
            $table->string('number')->unique();
            $table->foreignId('corrects_fua_id')->nullable()->constrained('fuas')->nullOnDelete();
            $table->string('status')->default('GENERATED');
            $table->timestamps();

            $table->unique(['series', 'correlative']);
            $table->unique(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuas');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('attention_type'));
        Schema::dropIfExists('fua_configurations');
    }
};
