<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_request_id',
        'warehouse_material_id',
        'qty_requested',
        'qty_approved',
        'qty_sent',
        'qty_received',
        'dispatch_status',
        'not_sent_reason',
        'notes',
    ];

    public function request()
    {
        return $this->belongsTo(WarehouseRequest::class, 'warehouse_request_id');
    }

    public function material()
    {
        return $this->belongsTo(WarehouseMaterial::class, 'warehouse_material_id');
    }
}
