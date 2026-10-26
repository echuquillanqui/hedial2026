<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuaCorrection extends Model
{
    protected $table = 'fua_corrections';

    protected $fillable = [
        'order_id', 'original_fua_id', 'responsible_user_id', 'generated_by',
        'series', 'correlative', 'number', 'status', 'reason', 'observations', 'observation_preset',
        'show_doctor', 'show_stamp', 'show_medications', 'show_procedures', 'show_hemodialysis', 'show_laboratories', 'show_diagnoses',
    ];

    protected $casts = [
        'show_doctor' => 'boolean',
        'show_stamp' => 'boolean',
        'show_medications' => 'boolean',
        'show_procedures' => 'boolean',
        'show_hemodialysis' => 'boolean',
        'show_laboratories' => 'boolean',
        'show_diagnoses' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function originalFua(): BelongsTo
    {
        return $this->belongsTo(Fua::class, 'original_fua_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
