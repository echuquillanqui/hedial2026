<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HemodialysisMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'stock',
        'quantity_per_order',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'quantity_per_order' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function consumptions()
    {
        return $this->hasMany(HemodialysisMaterialConsumption::class, 'hemodialysis_material_id');
    }
}
