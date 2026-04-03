<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HemodialysisMaterialConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'hemodialysis_material_id',
        'order_id',
        'patient_id',
        'consumed_at',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'consumed_at' => 'date',
        'quantity' => 'decimal:2',
    ];

    public function material()
    {
        return $this->belongsTo(HemodialysisMaterial::class, 'hemodialysis_material_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
