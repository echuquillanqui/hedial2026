<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseRequestStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_request_id',
        'from_status',
        'to_status',
        'changed_by',
        'comment',
    ];

    public function request()
    {
        return $this->belongsTo(WarehouseRequest::class, 'warehouse_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
