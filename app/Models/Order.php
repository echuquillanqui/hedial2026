<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Medical;
use App\Models\Patient;
use App\Models\Nurse;
use App\Models\Treatment;
use App\Models\ExtraMaterial;
use App\Models\HemodialysisMaterialConsumption;
use App\Services\MultisectorialOrderService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'fecha_orden' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'es_covid' => 'boolean',
    ];

    protected $fillable = [
        'sede_id',
        'patient_id',
        'assigned_professional_id',
        'created_by',
        'codigo_unico',
        'sala',
        'turno',
        'es_covid',
        'attention_type',
        'status',
        'laboratory_period',
        'horas_dialisis',
        'fecha_orden',
        'due_date',
        'period_key',
        'completed_at',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    // Relación con el paciente (BelongsTo)
    public function patient() 
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function assignedProfessional()
    {
        return $this->belongsTo(User::class, 'assigned_professional_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getScheduleStatusAttribute(): string
    {
        if ($this->status === MultisectorialOrderService::COMPLETED || $this->completed_at) {
            return 'REALIZADA';
        }

        if ($this->due_date?->lt(today())) {
            return 'VENCIDA';
        }

        if ($this->due_date && $this->due_date->lte(today()->addDays(30))) {
            return 'PRÓXIMA';
        }

        return 'PENDIENTE';
    }

    public function medical() 
    {
        return $this->hasOne(Medical::class, 'order_id');
    }

    public function laboratoryOrder()
    {
        return $this->hasOne(LaboratoryOrder::class);
    }

    public function fua()
    {
        return $this->hasOne(Fua::class);
    }

    public function nephrologyConsultation()
    {
        return $this->hasOne(NephrologyConsultation::class);
    }

    public function nurse() 
    {
        return $this->hasOne(Nurse::class, 'order_id');
    }

    public function treatments()
    {
        // Asegúrate de que sea hasMany (una orden tiene muchos tratamientos)
        return $this->hasMany(Treatment::class, 'order_id');
    }

    public function extraMaterials()
    {
        return $this->hasMany(ExtraMaterial::class, 'order_id');
    }

    public function hemodialysisMaterialConsumptions()
    {
        return $this->hasMany(HemodialysisMaterialConsumption::class, 'order_id');
    }
}
