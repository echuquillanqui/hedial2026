<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'warehouse_material_id',
        'movement_type',
        'qty',
        'reference_type',
        'reference_id',
        'performed_by',
        'notes',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function material()
    {
        return $this->belongsTo(WarehouseMaterial::class, 'warehouse_material_id');
    }
}
