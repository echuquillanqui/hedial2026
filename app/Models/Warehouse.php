<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'sede_id',
        'name',
        'is_principal',
        'is_active',
    ];

    protected $casts = [
        'is_principal' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function outgoingRequests()
    {
        return $this->hasMany(WarehouseRequest::class, 'from_warehouse_id');
    }

    public function incomingRequests()
    {
        return $this->hasMany(WarehouseRequest::class, 'to_warehouse_id');
    }
}
