<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class MedicalModuleSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'sede_id',
        'module',
        'shift',
        'start_from',
        'start_to',
        'finish_from',
        'finish_to',
        'interval_minutes',
    ];

    protected $casts = ['shift' => 'integer', 'interval_minutes' => 'integer'];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function startSlots(): Collection
    {
        return $this->slotsBetween($this->start_from, $this->start_to);
    }

    public function finishSlots(): Collection
    {
        return $this->slotsBetween($this->finish_from, $this->finish_to);
    }

    private function slotsBetween(?string $from, ?string $to): Collection
    {
        if (! $from || ! $to || $this->interval_minutes < 1) {
            return collect();
        }

        $toMinutes = static function (string $time): int {
            [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

            return ($hours * 60) + $minutes;
        };

        $start = $toMinutes($from);
        $end = $toMinutes($to);
        if ($end < $start) {
            $end += 1440;
        }

        return collect(range($start, $end, $this->interval_minutes))
            ->map(fn (int $minutes) => sprintf('%02d:%02d', intdiv($minutes % 1440, 60), $minutes % 60));
    }
}
