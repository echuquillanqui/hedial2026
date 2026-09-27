<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NurseModuleSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'sede_id',
        'module',
        'start_times',
    ];

    protected $casts = [
        'start_times' => 'array',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }
}
