<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class WaterQualityRecord extends Model{protected $guarded=[];protected $casts=['measured_at'=>'datetime'];public function responsible(){return $this->belongsTo(User::class,'responsible_id');}}
