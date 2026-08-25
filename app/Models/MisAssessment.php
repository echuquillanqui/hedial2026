<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MisAssessment extends Model
{
    protected $guarded = [];
    protected $casts = ['assessed_at' => 'date'];
    public function nutritionAssessment() { return $this->belongsTo(NutritionAssessment::class); }
    public function albuminResult() { return $this->belongsTo(LaboratoryOrderItem::class, 'albumin_result_id'); }
    public function transferrinResult() { return $this->belongsTo(LaboratoryOrderItem::class, 'transferrin_result_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
