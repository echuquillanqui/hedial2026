<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_code',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'dispatched_by',
        'dispatched_at',
        'received_by',
        'received_at',
        'observations',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(WarehouseRequestItem::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(WarehouseRequestStatusLog::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
