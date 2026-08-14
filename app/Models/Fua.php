<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fua extends Model
{
    public const HEMODIALYSIS = 'HEMODIALYSIS';
    public const NEPHROLOGY = 'NEPHROLOGY';
    public const CORRECTION = 'CORRECTION';

    protected $fillable = ['order_id', 'type', 'series', 'correlative', 'number', 'corrects_fua_id', 'status'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function correctedFua(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_fua_id');
    }
}
