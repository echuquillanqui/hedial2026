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
        'patient_id',
        'requested_by',
        'period',
        'sampled_at',
        'provenance',
        'status',
    ];

    protected $casts = ['sampled_at' => 'date'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LaboratoryOrderItem::class);
    }
}
