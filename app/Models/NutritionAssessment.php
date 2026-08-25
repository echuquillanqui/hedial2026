<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionAssessment extends Model
{
    protected $guarded = [];
    protected $casts = ['assessment_date' => 'date'];

    public function order() { return $this->belongsTo(Order::class); }
    public function medical() { return $this->belongsTo(Medical::class); }
    public function nephrologyConsultation() { return $this->belongsTo(NephrologyConsultation::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function laboratoryResults() { return $this->belongsToMany(LaboratoryOrderItem::class, 'nutrition_assessment_laboratory_results'); }
    public function misAssessment() { return $this->hasOne(MisAssessment::class); }
}
