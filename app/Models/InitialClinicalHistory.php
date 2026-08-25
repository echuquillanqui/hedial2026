<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InitialClinicalHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'recorded_at' => 'date',
        'first_hemodialysis_date' => 'date',
        'comorbidities' => 'array',
        'immunizations' => 'array',
        'residual_diuresis' => 'decimal:2',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function nephrologist(): BelongsTo { return $this->belongsTo(User::class, 'nephrologist_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function laboratoryResults(): BelongsToMany
    {
        return $this->belongsToMany(
            LaboratoryOrderItem::class,
            'initial_history_laboratory_results',
            'initial_clinical_history_id',
            'laboratory_order_item_id'
        )->withTimestamps();
    }
}
