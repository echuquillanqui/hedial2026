<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStockEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id', 'warehouse_material_id', 'warehouse_supplier_id', 'quantity',
        'expiration_date', 'batch_number', 'document_number', 'received_by', 'notes',
    ];

    protected $casts = ['quantity' => 'decimal:2', 'expiration_date' => 'date'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function material() { return $this->belongsTo(WarehouseMaterial::class, 'warehouse_material_id'); }
    public function supplier() { return $this->belongsTo(WarehouseSupplier::class, 'warehouse_supplier_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
}
