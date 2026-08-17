<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NurseModuleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sede_id',
        'work_date',
        'module',
    ];

    protected $casts = [
        'work_date' => 'date',
        'module' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }
}
