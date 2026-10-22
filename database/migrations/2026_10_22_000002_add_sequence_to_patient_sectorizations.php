<?php

use App\Support\DailyHemodialysisSequence;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patient_sectorizations')) {
            return;
        }

        if (! Schema::hasColumn('patient_sectorizations', 'sequence')) {
            Schema::table('patient_sectorizations', function (Blueprint $table) {
                $table->string('sequence', 5)->nullable()->after('patient_id');
            });
        }

        DB::table('patient_sectorizations')
            ->whereNull('sequence')
            ->orderBy('id')
            ->chunkById(500, function ($assignments) {
                foreach ($assignments as $assignment) {
                    $sequence = DailyHemodialysisSequence::forDate(Carbon::parse($assignment->effective_from));
                    if ($sequence) {
                        DB::table('patient_sectorizations')
                            ->where('id', $assignment->id)
                            ->update(['sequence' => $sequence]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('patient_sectorizations') && Schema::hasColumn('patient_sectorizations', 'sequence')) {
            Schema::table('patient_sectorizations', function (Blueprint $table) {
                $table->dropColumn('sequence');
            });
        }
    }
};
