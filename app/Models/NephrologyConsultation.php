<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Filter consultations according to a finalized hemodialysis session on
     * the consultation date. Nursing closure is the system's attendance proof.
     */
    public function scopeWhereDialysisAttendance(Builder $query, string $attendance): Builder
    {
        $method = $attendance === 'attended' ? 'whereExists' : 'whereNotExists';

        return $query->{$method}(fn ($dialysis) => $dialysis
            ->selectRaw('1')
            ->from('orders as dialysis_orders')
            ->whereColumn('dialysis_orders.patient_id', 'nephrology_consultations.patient_id')
            ->whereColumn('dialysis_orders.fecha_orden', 'nephrology_consultations.consultation_date')
            ->where('dialysis_orders.attention_type', Fua::HEMODIALYSIS)
            ->whereExists(fn ($nurse) => $nurse
                ->selectRaw('1')
                ->from('nurses')
                ->whereColumn('nurses.order_id', 'dialysis_orders.id')
                ->whereNotNull('nurses.enfermero_que_finaliza_id')));
    }
}
