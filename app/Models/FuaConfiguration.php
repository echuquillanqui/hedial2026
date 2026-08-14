<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuaConfiguration extends Model
{
    protected $guarded = [];

    public static function global(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
