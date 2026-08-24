<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HemodialysisConsent extends Model
{
    protected $guarded = [];

    protected $casts = ['consented_at' => 'datetime', 'accepted' => 'boolean'];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function sede(): BelongsTo { return $this->belongsTo(Sede::class); }
    public function physician(): BelongsTo { return $this->belongsTo(User::class, 'physician_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
