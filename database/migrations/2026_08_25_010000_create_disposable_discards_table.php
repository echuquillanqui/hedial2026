<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nurses', function (Blueprint $table) {
            $table->text('transfusions')->nullable()->after('otros_medicamentos');
            $table->text('dressings')->nullable()->after('transfusions');
        });
        Schema::create('disposable_discards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 20);
            $table->dateTime('discarded_at');
            $table->string('lot_number', 80)->nullable();
            $table->string('discard_reason', 120);
            $table->string('final_condition', 120)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'category'], 'discard_order_category_unq');
            $table->index(['discarded_at', 'category'], 'discard_date_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposable_discards');
        Schema::table('nurses', fn (Blueprint $table) => $table->dropColumn(['transfusions', 'dressings']));
    }
};
