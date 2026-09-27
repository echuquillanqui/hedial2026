<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DialysisSupplyLot extends Model
{
    protected $guarded = [];
    protected $casts = ['measurement' => 'decimal:1', 'valid_from' => 'date', 'valid_until' => 'date', 'is_active' => 'boolean'];

    public function scopeEffectiveOn(Builder $query, mixed $date): Builder
    {
        return $query->where('is_active', true)->whereDate('valid_from', '<=', $date)->whereDate('valid_until', '>=', $date);
    }
}
