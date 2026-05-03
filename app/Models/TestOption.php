<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'label',
        'value',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
