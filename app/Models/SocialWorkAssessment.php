<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SocialWorkAssessment extends Model { protected $guarded=[]; protected $casts=['assessment_date'=>'date']; public function order(){return $this->belongsTo(Order::class);} }
