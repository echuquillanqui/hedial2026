<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PatientClinicalEvent extends Model{const HOSPITALIZED='HOSPITALIZED';const DISCHARGED='DISCHARGED';protected $guarded=[];protected $casts=['occurred_at'=>'datetime'];public function patient(){return $this->belongsTo(Patient::class);}public function recorder(){return $this->belongsTo(User::class,'recorded_by');}}
