<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Eq5dAssessment extends Model { protected $guarded=[]; protected $casts=['assessed_at'=>'date']; public function psychologyAssessment(){return $this->belongsTo(PsychologyAssessment::class);} public function getHealthStateAttribute(): string{return implode('',[$this->mobility,$this->self_care,$this->usual_activities,$this->pain_discomfort,$this->anxiety_depression]);} }
