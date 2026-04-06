<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'is_principal',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_principal' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function warehouse()
    {
        return $this->hasOne(Warehouse::class);
    }
}
