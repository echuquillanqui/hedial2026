<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'sede_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'operational_area_user')->withTimestamps();
    }

    public function warehouseRequests(): HasMany
    {
        return $this->hasMany(WarehouseRequest::class);
    }
}
