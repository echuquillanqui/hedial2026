<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PsychologyAssessment extends Model { protected $guarded=[]; protected $casts=['assessment_date'=>'date']; public function order(){return $this->belongsTo(Order::class);} public function eq5dAssessment(){return $this->hasOne(Eq5dAssessment::class);} }
