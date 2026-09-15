<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyNursingAnnex extends Model
{
    protected $fillable = ['sede_id', 'work_date', 'frequency', 'module', 'code', 'automatic_values', 'values', 'generated_by'];

    protected $casts = [
        'work_date' => 'date',
        'automatic_values' => 'array',
        'values' => 'array',
    ];

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
