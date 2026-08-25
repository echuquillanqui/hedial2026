<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class MachineDisinfection extends Model{protected $guarded=[];protected $casts=['work_date'=>'date'];public function machine(){return $this->belongsTo(DialysisMachine::class,'dialysis_machine_id');}public function responsible(){return $this->belongsTo(User::class,'responsible_id');}}
