<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(Test::class, 'profile_test')->withTimestamps();
    }
}
