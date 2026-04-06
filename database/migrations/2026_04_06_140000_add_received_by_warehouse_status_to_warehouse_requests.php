<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE warehouse_requests MODIFY COLUMN status ENUM('draft','submitted','received_by_warehouse','approved','rejected','partially_dispatched','dispatched','partially_received','received','cancelled') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('warehouse_requests')
            ->where('status', 'received_by_warehouse')
            ->update(['status' => 'submitted']);

        DB::statement("ALTER TABLE warehouse_requests MODIFY COLUMN status ENUM('draft','submitted','approved','rejected','partially_dispatched','dispatched','partially_received','received','cancelled') NOT NULL DEFAULT 'draft'");
    }
};
