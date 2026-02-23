<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    // Permite asignación masiva de todos los campos definidos como nullables en la migración
    protected $guarded = [];

    /**
     * Un paciente puede tener múltiples hojas de referencia.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function latestMedicalRecord()
    {
        return $this->hasOneThrough(
            Medical::class, 
            Order::class, 
            'patient_id', // Llave foránea en orders
            'order_id',   // Llave foránea en medical
            'id',         // Llave local en patients
            'id'          // Llave local en orders
        );
    }
}
