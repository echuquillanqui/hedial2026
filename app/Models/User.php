<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'license_number',     // Número de colegiatura
        'specialty_number',   // RNE o número de especialidad
        'profession',         // Profesión (Médico, Enfermero, etc.)
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Referencias donde es el Responsable de la RF 
    public function referralsAsResponsible(): HasMany
    {
        return $this->hasMany(Referral::class, 'referral_responsible_id');
    }

    // Referencias donde es el Responsable del Establecimiento 
    public function referralsAsFacilityHead(): HasMany
    {
        return $this->hasMany(Referral::class, 'facility_responsible_id');
    }

    // Referencias donde es el Personal que acompaña 
    public function referralsAsEscort(): HasMany
    {
        return $this->hasMany(Referral::class, 'escort_staff_id');
    }

    // Referencias donde es el Personal que recibe 
    public function referralsAsReceiver(): HasMany
    {
        return $this->hasMany(Referral::class, 'receiving_staff_id');
    }

    /**
     * Sesiones de hemodiálisis que este usuario ha iniciado.
     */
    public function sesionesIniciadas()
    {
        return $this->hasMany(Medical::class, 'usuario_que_inicia_hd');
    }

    /**
     * Sesiones de hemodiálisis que este usuario ha finalizado.
     */
    public function sesionesFinalizadas()
    {
        return $this->hasMany(Medical::class, 'usuario_que_finaliza_hd');
    }

    public function controlesIniciados()
    {
        return $this->hasMany(Nurse::class, 'enfermero_que_inicia_id');
    }

    /**
     * Registros de enfermería finalizados por este usuario
     */
    public function controlesFinalizados()
    {
        return $this->hasMany(Nurse::class, 'enfermero_que_finaliza_id');
    }
}
