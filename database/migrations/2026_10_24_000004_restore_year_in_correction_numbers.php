<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('fua_corrections')->get() as $correction) {
            $prefix = trim((string) $correction->series, '-');
            if (! preg_match('/^\d+-\d{2}$/', $prefix)) {
                continue;
            }

            $number = $prefix.'-S'.str_pad((string) $correction->correlative, 7, '0', STR_PAD_LEFT);
            if ($number !== $correction->number) {
                DB::table('fua_corrections')->where('id', $correction->id)->update(['number' => $number]);
            }
        }
    }

    public function down(): void
    {
        // Keep corrected official numbers; reverting would restore malformed FUAs.
    }
};
