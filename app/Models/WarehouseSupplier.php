<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseSupplier extends Model
{
    use HasFactory;

    protected $fillable = ['business_name', 'tax_id', 'contact_name', 'phone', 'email', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function entries()
    {
        return $this->hasMany(WarehouseStockEntry::class);
    }
}
