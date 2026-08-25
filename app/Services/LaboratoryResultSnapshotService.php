<?php

namespace App\Services;

use App\Models\LaboratoryOrderItem;
use App\Models\Patient;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class LaboratoryResultSnapshotService
{
    public function latestValidFor(Patient $patient, CarbonInterface $clinicalDate): Collection
    {
        return LaboratoryOrderItem::query()
            ->with(['test.area', 'order'])
            ->whereNotNull('result_value')
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '<=', $clinicalDate->toDateString())
            ->whereHas('order', fn ($order) => $order
                ->where('patient_id', $patient->id)
                ->where(function ($dates) use ($clinicalDate) {
                    $dates->whereDate('sampled_at', '<=', $clinicalDate->toDateString())
                        ->orWhere(fn ($fallback) => $fallback->whereNull('sampled_at')
                            ->whereDate('created_at', '<=', $clinicalDate->toDateString()));
                }))
            ->get()
            ->sortByDesc(fn (LaboratoryOrderItem $item) => $item->order->sampled_at
                ?? $item->completed_at
                ?? $item->created_at)
            ->unique('test_id')
            ->values();
    }
}
