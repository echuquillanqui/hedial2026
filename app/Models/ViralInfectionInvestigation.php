<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class ViralInfectionInvestigation extends Model{protected $guarded=[];protected $casts=['investigated_at'=>'date','symptom_onset'=>'date'];public function patient(){return $this->belongsTo(Patient::class);}public function laboratoryResults(){return $this->belongsToMany(LaboratoryOrderItem::class,'viral_investigation_laboratory_results');}}
