<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medical extends Model
{
    use HasFactory;

    // app/Models/Medical.php
    protected $table = 'medicals';
    
    protected $fillable = [
        'order_id', 'hora_inicial', 'peso_inicial', 'pa_inicial', 'frecuencia_cardiaca', 
        'so2', 'fio2', 'temperatura', 'problemas_clinicos', 'evaluacion', 'indicaciones', 
        'signos_sintomas', 'epo2000', 'epo4000', 'hierro', 'vitamina_b12', 'calcitriol', 
        'heparina', 'hora_hd', 'peso_seco', 'uf', 'qb', 'qd', 'bicarbonato', 'na_inicial', 
        'cnd', 'na_final', 'perfil_na', 'area_filtro', 'membrana', 'perfil_uf', 
        'evaluacion_final', 'hora_final', 'usuario_que_inicia_hd', 'usuario_que_finaliza_hd'
    ];

    // Haz lo mismo para Nurse y Treatment agregando 'order_id' a su $fillable

    // Relación con la orden original
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Usuario que inició el procedimiento
    public function usuarioInicia()
    {
        return $this->belongsTo(User::class, 'usuario_que_inicia_hd');
    }

    // Usuario que finalizó el procedimiento
    public function usuarioFinaliza()
    {
        return $this->belongsTo(User::class, 'usuario_que_finaliza_hd');
    }
}
