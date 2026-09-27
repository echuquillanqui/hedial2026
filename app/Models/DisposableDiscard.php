<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisposableDiscard extends Model
{
    public const DIALYZER = 'DIALYZER';
    public const BLOOD_LINES = 'BLOOD_LINES';
    protected $guarded = [];
    protected $casts = [
        'discarded_at' => 'datetime',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];
    public function order() { return $this->belongsTo(Order::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
