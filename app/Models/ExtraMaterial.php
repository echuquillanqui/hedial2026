<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'order_id',
        'usage_date',
        'material_name',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
