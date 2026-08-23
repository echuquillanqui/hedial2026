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
        'automatic_consumption',
        'quantity_per_session',
        'warehouse_material_category_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'automatic_consumption' => 'boolean',
        'quantity_per_session' => 'decimal:2',
    ];

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function category()
    {
        return $this->belongsTo(WarehouseMaterialCategory::class, 'warehouse_material_category_id');
    }

    public function stockEntries()
    {
        return $this->hasMany(WarehouseStockEntry::class, 'warehouse_material_id');
    }
}
