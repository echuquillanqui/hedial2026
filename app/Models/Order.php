<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Medical;
use App\Models\Patient;
use App\Models\Nurse;
use App\Models\Treatment;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'codigo_unico',
        'sala',
        'turno',
        'es_covid',
        'horas_dialisis',
        'fecha_orden'
    ];

    // Relación con el paciente (BelongsTo)
    public function patient() 
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function medical() 
    {
        return $this->hasOne(Medical::class, 'order_id');
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
}
