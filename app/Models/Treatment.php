<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;

    protected $table = 'treatments';

    protected $fillable = [
        'order_id', 'hora', 'pa', 'fc', 'qb', 'cnd', 'ra', 'rv', 'ptm', 'observacion'
    ];

    public function order() 
    {
        return $this->belongsTo(Order::class);
    }
}
