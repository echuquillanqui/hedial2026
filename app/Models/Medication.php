<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medication extends Model
{
    protected $guarded = [];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(NephrologyConsultation::class, 'nephrology_consultation_id');
    }
}
