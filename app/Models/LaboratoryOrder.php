<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaboratoryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_name',
        'requested_by',
        'status',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(LaboratoryOrderItem::class);
    }
}

