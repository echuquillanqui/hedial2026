<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class DialysisMachine extends Model{protected $guarded=[];protected $casts=['is_active'=>'boolean'];public function sede(){return $this->belongsTo(Sede::class);}public function disinfections(){return $this->hasMany(MachineDisinfection::class);}}
