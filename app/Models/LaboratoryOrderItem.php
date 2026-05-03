<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'laboratory_order_id',
        'test_id',
        'result_value',
        'result_notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaboratoryOrder::class, 'laboratory_order_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}

