<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NurseModuleAssignment extends Model
{
    use HasFactory;

    public const ALL_MODULES = 0;

    private const ALL_MODULES_DATE = '2026-09-09';

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

    public static function allModulesEnabledToday(): bool
    {
        return today()->toDateString() === self::ALL_MODULES_DATE;
    }

    public function includesAllModules(): bool
    {
        return $this->module === self::ALL_MODULES;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }
}
