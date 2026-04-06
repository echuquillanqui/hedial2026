<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'warehouse_material_category_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function category()
    {
        return $this->belongsTo(WarehouseMaterialCategory::class, 'warehouse_material_category_id');
    }
}

