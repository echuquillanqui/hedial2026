<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_request_items', function (Blueprint $table) {
            $table->enum('receive_status', ['pending', 'partial', 'complete', 'not_received'])
                ->default('pending')
                ->after('dispatch_status');
            $table->string('not_received_reason')->nullable()->after('not_sent_reason');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_request_items', function (Blueprint $table) {
            $table->dropColumn(['receive_status', 'not_received_reason']);
        });
    }
};
