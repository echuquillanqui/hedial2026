<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientSectorization extends Model
{
    protected $fillable = [
        'patient_id',
        'sequence',
        'effective_from',
        'module',
        'puesto',
        'numero_maquina',
    ];

    protected $casts = [
        'effective_from' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
