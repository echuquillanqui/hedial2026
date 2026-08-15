<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NephrologyConsultation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'consultation_date' => 'date',
        'dialysis_start_date' => 'date',
        'anemia_treatment' => 'boolean',
        'bone_mineral_treatment' => 'boolean',
        'antihypertensive_treatment' => 'boolean',
        'diagnoses' => 'array',
        'auxiliary_exams' => 'array',
        'next_laboratory_date' => 'date',
        'next_appointment_date' => 'date',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function doctor(): BelongsTo { return $this->belongsTo(User::class, 'doctor_id'); }
    public function sede(): BelongsTo { return $this->belongsTo(Sede::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function medications(): HasMany { return $this->hasMany(Medication::class); }
}
