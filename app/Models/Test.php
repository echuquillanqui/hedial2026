<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'name',
        'unit',
        'reference_value',
        'type',
        'frequency',
        'is_fissal',
    ];

    protected $casts = ['is_fissal' => 'boolean'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(TestOption::class);
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'profile_test')->withTimestamps();
    }
}
