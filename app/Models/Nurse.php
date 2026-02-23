<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nurse extends Model
{
    use HasFactory;

    protected $table = 'nurses';

    protected $fillable = [
        'order_id', 'frecuencia_hd', 'numero_hd', 'puesto', 'numero_maquina', 'marca_modelo',
        'aspecto_dializador', 'filtro', 'pa_inicial', 'pa_final', 'peso_inicial', 'peso_final',
        'uf', 'acceso_venoso', 'acceso_arterial', 'epo2000', 'epo4000', 'hierro', 
        'vitamina_b12', 'calcitriol', 'otros_medicamentos', 's', 'o', 'a', 'p', 
        'observacion_final', 'enfermero_que_inicia_id', 'enfermero_que_finaliza_id'
    ];

    // Al editar Nurse, podemos disparar la actualización hacia Medical
    public function updateMedicalData()
    {
        $this->order->medical->update([
            'pa_inicial' => $this->pa_inicial,
            'peso_inicial' => $this->peso_inicial,
            'uf' => $this->uf,
        ]);
    }
    // Relación con la orden
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relación con el enfermero que inicia
    public function enfermeroInicia()
    {
        return $this->belongsTo(User::class, 'enfermero_que_inicia_id');
    }

    // Relación con el enfermero que finaliza
    public function enfermeroFinaliza()
    {
        return $this->belongsTo(User::class, 'enfermero_que_finaliza_id');
    }

    public function treatments() 
    {
        return $this->hasMany(Treatment::class, 'order_id', 'order_id');
    }
}
